<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Planificacao;
use App\Models\Tenant\PlanificacaoVersao;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlanificacaoStorageService
{
    private const DISK          = 'local';
    private const DISK_FALLBACK = 'local';

    private const CIPHER     = 'aes-256-gcm';
    private const IV_LENGTH  = 16;
    private const TAG_LENGTH = 16;

    // ═══════════════════════════════════════════════════════════
    // GUARDAR
    // ═══════════════════════════════════════════════════════════

    public function guardarNovaVersao(
        Planificacao $planificacao,
        UploadedFile $ficheiro,
        string $userId
    ): PlanificacaoVersao {
        $inicio = microtime(true);
        $proximaVersao = $planificacao->versao_atual + 1;
        $extensao = strtolower($ficheiro->getClientOriginalExtension());

        Log::info('[PlanificacaoStorage] ═══ INÍCIO ═══', [
            'planificacao_id'          => $planificacao->id,
            'versao'                   => $proximaVersao,
            'nome_original'            => $ficheiro->getClientOriginalName(),
            'extensao'                 => $extensao,
            'tamanho_original_bytes'   => $ficheiro->getSize(),
            'tamanho_original_legivel' => $this->humanSize($ficheiro->getSize()),
            'mime_type'                => $ficheiro->getMimeType(),
        ]);

        // ─── Ler conteúdo original ───
        $conteudo = file_get_contents($ficheiro->getRealPath());
        $tamanhoOriginal = strlen($conteudo);

        // ─── Comprimir (apenas PDF) ───
        $compressao = $this->comprimir($conteudo, $extensao, $ficheiro);
        $conteudo = $compressao['conteudo'];
        $tamanhoComprimido = strlen($conteudo);

        Log::info('[PlanificacaoStorage] ✓ Compressão', [
            'aplicada'       => $compressao['aplicada'],
            'motivo'         => $compressao['motivo'],
            'antes_bytes'    => $tamanhoOriginal,
            'antes_legivel'  => $this->humanSize($tamanhoOriginal),
            'depois_bytes'   => $tamanhoComprimido,
            'depois_legivel' => $this->humanSize($tamanhoComprimido),
            'reducao_pct'    => $tamanhoOriginal > 0
                ? round((1 - $tamanhoComprimido / $tamanhoOriginal) * 100, 2)
                : 0,
        ]);

        // ─── Encriptar ───
        $encriptacao = $this->encriptar($conteudo);
        $conteudoFinal = $encriptacao['pacote'];
        $tamanhoFinal = strlen($conteudoFinal);

        Log::info('[PlanificacaoStorage] ✓ Encriptação', [
            'algoritmo'        => self::CIPHER,
            'antes_bytes'      => $tamanhoComprimido,
            'depois_bytes'     => $tamanhoFinal,
            'overhead_bytes'   => $tamanhoFinal - $tamanhoComprimido,
            'overhead_legivel' => $this->humanSize($tamanhoFinal - $tamanhoComprimido),
        ]);

        // ─── Gravar (.enc) ───
        $caminho = $this->construirCaminho($planificacao, $proximaVersao, 'enc');
        $this->gravarFicheiro($caminho, $conteudoFinal, $planificacao);

        // ─── Metadata na BD ───
        $versao = $planificacao->versoes()->create([
            'versao'                 => $proximaVersao,
            'caminho_ficheiro'       => $caminho,
            'nome_original'          => $ficheiro->getClientOriginalName(),
            'tamanho_original_bytes' => $tamanhoOriginal,
            'tamanho_bytes'          => $tamanhoFinal,
            'mime_type'              => $ficheiro->getMimeType() ?? 'application/octet-stream',
            'comprimido'             => $compressao['aplicada'],
            'encriptado'             => true,
            'algoritmo_encriptacao'  => self::CIPHER,
            'uploaded_by'            => $userId,
        ]);

        $planificacao->update(['versao_atual' => $proximaVersao]);

        Log::info('[PlanificacaoStorage] ═══ FIM ═══', [
            'versao_id'      => $versao->id,
            'caminho'        => $caminho,
            'duracao_ms'     => round((microtime(true) - $inicio) * 1000, 2),
            'economia_total' => $tamanhoOriginal > 0
                ? $this->humanSize($tamanhoOriginal - $tamanhoComprimido)
                . ' (' . round((1 - $tamanhoComprimido / $tamanhoOriginal) * 100, 1) . '%)'
                : '0',
        ]);

        return $versao;
    }

    // ═══════════════════════════════════════════════════════════
    // COMPRESSÃO
    // ═══════════════════════════════════════════════════════════

private function comprimir(string $conteudo, string $extensao, UploadedFile $ficheiro): array
{
    if ($extensao !== 'pdf') {
        return [
            'conteudo' => $conteudo,
            'aplicada' => false,
            'motivo'   => "Extensão '{$extensao}' não suportada para compressão",
        ];
    }

    // Verificar binários disponíveis
    exec('which gs 2>/dev/null', $o1, $gsExit);
    exec('which qpdf 2>/dev/null', $o2, $qpdfExit);
    $temGs   = $gsExit === 0;
    $temQpdf = $qpdfExit === 0;

    if (! $temGs && ! $temQpdf) {
        return [
            'conteudo' => $conteudo,
            'aplicada' => false,
            'motivo'   => 'Nem Ghostscript nem qpdf instalados',
        ];
    }

    try {
        $tmpOriginal = tempnam(sys_get_temp_dir(), 'pdf_orig_') . '.pdf';
        $tmpQpdf1    = tempnam(sys_get_temp_dir(), 'pdf_qpdf1_') . '.pdf';
        $tmpGs       = tempnam(sys_get_temp_dir(), 'pdf_gs_') . '.pdf';
        $tmpFinal    = tempnam(sys_get_temp_dir(), 'pdf_final_') . '.pdf';

        file_put_contents($tmpOriginal, $conteudo);
        $atual  = $tmpOriginal;
        $etapas = [];

        // ─── ETAPA 1: qpdf — otimização estrutural (lossless) ───
        if ($temQpdf) {
            $cmd = sprintf(
                'qpdf --compress-streams=y --object-streams=generate --recompress-flate --compression-level=9 %s %s 2>&1',
                escapeshellarg($atual),
                escapeshellarg($tmpQpdf1)
            );
            exec($cmd, $out, $exit);

            if ($exit === 0 && file_exists($tmpQpdf1) && filesize($tmpQpdf1) > 0) {
                $antes  = filesize($atual);
                $depois = filesize($tmpQpdf1);
                $etapas[] = sprintf('qpdf: %s → %s', $this->humanSize($antes), $this->humanSize($depois));
                $atual    = $tmpQpdf1;
            }
        }

        // ─── ETAPA 2: Ghostscript — compressão agressiva (lossy) ───
        if ($temGs) {
            $cmd = sprintf(
                'gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook ' .
                '-dNOPAUSE -dQUIET -dBATCH ' .
                '-dDetectDuplicateImages=true -dCompressFonts=true ' .
                '-dColorImageDownsampleType=/Bicubic -dColorImageResolution=150 ' .
                '-dGrayImageDownsampleType=/Bicubic -dGrayImageResolution=150 ' .
                '-dMonoImageDownsampleType=/Bicubic -dMonoImageResolution=300 ' .
                '-sOutputFile=%s %s 2>&1',
                escapeshellarg($tmpGs),
                escapeshellarg($atual)
            );
            exec($cmd, $out, $exit);

            if ($exit === 0 && file_exists($tmpGs) && filesize($tmpGs) > 0) {
                $antes  = filesize($atual);
                $depois = filesize($tmpGs);
                $etapas[] = sprintf('ghostscript: %s → %s', $this->humanSize($antes), $this->humanSize($depois));
                $atual    = $tmpGs;
            }
        }

        // ─── ETAPA 3: qpdf — linearização final (fast web view) ───
        if ($temQpdf && filesize($atual) > 0) {
            $cmd = sprintf(
                'qpdf --linearize --object-streams=generate %s %s 2>&1',
                escapeshellarg($atual),
                escapeshellarg($tmpFinal)
            );
            exec($cmd, $out, $exit);

            if ($exit === 0 && file_exists($tmpFinal) && filesize($tmpFinal) > 0) {
                $antes  = filesize($atual);
                $depois = filesize($tmpFinal);
                $etapas[] = sprintf('linearize: %s → %s', $this->humanSize($antes), $this->humanSize($depois));
                $atual    = $tmpFinal;
            }
        }

        $comprimido     = file_get_contents($atual);
        $tamanhoOriginal = strlen($conteudo);
        $tamanhoFinal    = strlen($comprimido);

        // Limpar temporários
        foreach ([$tmpOriginal, $tmpQpdf1, $tmpGs, $tmpFinal] as $f) {
            @unlink($f);
        }

        if ($comprimido === false || $tamanhoFinal === 0 || $tamanhoFinal >= $tamanhoOriginal) {
            return [
                'conteudo' => $conteudo,
                'aplicada' => false,
                'motivo'   => 'Compressão não trouxe benefício',
            ];
        }

        return [
            'conteudo' => $comprimido,
            'aplicada' => true,
            'motivo'   => implode(' → ', $etapas),
        ];
    } catch (\Throwable $e) {
        Log::warning('[PlanificacaoStorage] Compressão multi-etapa falhou', [
            'error' => $e->getMessage(),
        ]);

        return [
            'conteudo' => $conteudo,
            'aplicada' => false,
            'motivo'   => 'Erro: ' . $e->getMessage(),
        ];
    }
}

    // ═══════════════════════════════════════════════════════════
    // ENCRIPTAÇÃO — formato: [IV 16B][TAG 16B][conteúdo]
    // ═══════════════════════════════════════════════════════════

    private function encriptar(string $conteudo): array
    {
        $chave = $this->chaveEncriptacao();
        $iv    = random_bytes(self::IV_LENGTH);
        $tag   = '';

        $encriptado = openssl_encrypt(
            $conteudo,
            self::CIPHER,
            $chave,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($encriptado === false) {
            throw new \RuntimeException('Falha na encriptação: ' . openssl_error_string());
        }

        return ['pacote' => $iv . $tag . $encriptado];
    }

    private function desencriptar(string $pacote): string
    {
        if (strlen($pacote) < self::IV_LENGTH + self::TAG_LENGTH) {
            throw new \RuntimeException('Pacote demasiado pequeno.');
        }

        $iv         = substr($pacote, 0, self::IV_LENGTH);
        $tag        = substr($pacote, self::IV_LENGTH, self::TAG_LENGTH);
        $encriptado = substr($pacote, self::IV_LENGTH + self::TAG_LENGTH);

        $plano = openssl_decrypt(
            $encriptado,
            self::CIPHER,
            $this->chaveEncriptacao(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plano === false) {
            throw new \RuntimeException('Falha na desencriptação: ' . openssl_error_string());
        }

        return $plano;
    }

    private function chaveEncriptacao(): string
    {
        return hash('sha256', config('app.key'), true);
    }

    // ═══════════════════════════════════════════════════════════
    // LEITURA
    // ═══════════════════════════════════════════════════════════

    public function conteudo(PlanificacaoVersao $versao): ?string
    {
        $pacote = $this->lerPacoteBruto($versao);
        if ($pacote === null) {
            return null;
        }

        if (! $versao->encriptado) {
            return $pacote;
        }

        try {
            return $this->desencriptar($pacote);
        } catch (\Throwable $e) {
            Log::error('[PlanificacaoStorage] Desencriptação falhou', [
                'versao_id' => $versao->id,
                'error'     => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function lerPacoteBruto(PlanificacaoVersao $versao): ?string
    {
        $disk = Storage::disk(self::DISK);
        if ($disk->exists($versao->caminho_ficheiro)) {
            return $disk->get($versao->caminho_ficheiro);
        }

        $fallback = Storage::disk(self::DISK_FALLBACK);
        if ($fallback->exists($versao->caminho_ficheiro)) {
            return $fallback->get($versao->caminho_ficheiro);
        }

        return null;
    }

    public function caminhoAbsoluto(PlanificacaoVersao $versao): ?string
    {
        $disk = Storage::disk(self::DISK);
        if ($disk->exists($versao->caminho_ficheiro)) {
            return $disk->path($versao->caminho_ficheiro);
        }

        $fallback = Storage::disk(self::DISK_FALLBACK);
        if ($fallback->exists($versao->caminho_ficheiro)) {
            return $fallback->path($versao->caminho_ficheiro);
        }

        return null;
    }

    // ═══════════════════════════════════════════════════════════
    // APAGAR
    // ═══════════════════════════════════════════════════════════

    public function apagarTodasVersoes(Planificacao $planificacao): void
    {
        $disks = [Storage::disk(self::DISK), Storage::disk(self::DISK_FALLBACK)];

        foreach ($planificacao->versoes as $versao) {
            foreach ($disks as $disk) {
                if ($disk->exists($versao->caminho_ficheiro)) {
                    $disk->delete($versao->caminho_ficheiro);
                    Log::info('[PlanificacaoStorage] Ficheiro apagado', [
                        'versao_id' => $versao->id,
                        'caminho'   => $versao->caminho_ficheiro,
                    ]);
                }
            }
        }
    }

    // ═══════════════════════════════════════════════════════════
    // GRAVAÇÃO
    // ═══════════════════════════════════════════════════════════

    private function gravarFicheiro(string $caminho, string $conteudo, Planificacao $planificacao): void
    {
        $diskPrincipal = Storage::disk(self::DISK);

        Log::info('[PlanificacaoStorage] Tentar gravar', [
            'caminho'   => $caminho,
            'disk'      => self::DISK,
            'root'      => $diskPrincipal->path(''),
            'root_real' => realpath($diskPrincipal->path('')) ?: 'desconhecido',
            'bytes'     => strlen($conteudo),
        ]);

        try {
            $diretorio = dirname($caminho);
            if (! $diskPrincipal->exists($diretorio)) {
                $diskPrincipal->makeDirectory($diretorio, 0755, true);
            }
            $diskPrincipal->put($caminho, $conteudo);

            Log::info('[PlanificacaoStorage] ✓ Gravado no disk principal', [
                'caminho'  => $caminho,
                'disk'     => self::DISK,
                'realpath' => realpath($diskPrincipal->path($caminho)) ?: 'desconhecido',
            ]);
            return;
        } catch (\Throwable $e) {
            Log::warning('[PlanificacaoStorage] Disk principal falhou — fallback', [
                'caminho' => $caminho,
                'error'   => $e->getMessage(),
            ]);
        }

        try {
            $diskFallback = Storage::disk(self::DISK_FALLBACK);
            $diretorio = dirname($caminho);
            if (! $diskFallback->exists($diretorio)) {
                $diskFallback->makeDirectory($diretorio, 0755, true);
            }
            $diskFallback->put($caminho, $conteudo);

            Log::warning('[PlanificacaoStorage] ✓ Gravado no fallback', [
                'caminho' => $caminho,
                'disk'    => self::DISK_FALLBACK,
            ]);
        } catch (\Throwable $e) {
            Log::error('[PlanificacaoStorage] ✗ Ambos os disks falharam', [
                'caminho' => $caminho,
                'error'   => $e->getMessage(),
            ]);
            throw new \RuntimeException(
                'Não foi possível guardar o ficheiro. Detalhe: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    // ═══════════════════════════════════════════════════════════
    // AUXILIARES
    // ═══════════════════════════════════════════════════════════

    private function construirCaminho(Planificacao $planificacao, int $versao, string $extensao): string
    {
        return sprintf(
            'planificacoes/%s/%s/%s/%s/v%d.%s',
            $planificacao->ano_letivo_id,
            $planificacao->disciplina_id,
            $planificacao->classe_id,
            Str::slug($planificacao->periodo),
            $versao,
            $extensao
        );
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 2) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }
}
