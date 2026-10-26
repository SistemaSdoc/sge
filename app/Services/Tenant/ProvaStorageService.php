<?php

namespace App\Services\Tenant;

use App\Models\Tenant\PrazoProva;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProvaStorageService
{
    private const DISK = 'local';

    private const CIPHER     = 'aes-256-gcm';
    private const IV_LENGTH  = 16;
    private const TAG_LENGTH = 16;

    /**
     * Guarda o par prova + chave com compressão e encriptação.
     *
     * @return array{
     *   caminho_prova: string,
     *   caminho_chave: string,
     *   tamanho_original_bytes: int,
     *   tamanho_bytes: int,
     *   comprimido: bool,
     *   encriptado: bool,
     *   algoritmo_encriptacao: string,
     * }
     */
    public function guardarParDeFicheiros(
        PrazoProva $prazo,
        string $professorId,
        string $turmaId,
        int $versao,
        UploadedFile $arquivoProva,
        UploadedFile $arquivoChave
    ): array {
        $inicio = microtime(true);

        Log::info('[ProvaStorage] ═══ INÍCIO ═══', [
            'prazo_id'       => $prazo->id,
            'professor_id'   => $professorId,
            'turma_id'       => $turmaId,
            'versao'         => $versao,
            'prova'          => [
                'nome'   => $arquivoProva->getClientOriginalName(),
                'bytes'  => $arquivoProva->getSize(),
                'legivel'=> $this->humanSize($arquivoProva->getSize()),
                'mime'   => $arquivoProva->getMimeType(),
            ],
            'chave'          => [
                'nome'   => $arquivoChave->getClientOriginalName(),
                'bytes'  => $arquivoChave->getSize(),
                'legivel'=> $this->humanSize($arquivoChave->getSize()),
                'mime'   => $arquivoChave->getMimeType(),
            ],
        ]);

        // Processar prova
        $resultadoProva = $this->processarFicheiro($arquivoProva, 'prova');

        // Processar chave
        $resultadoChave = $this->processarFicheiro($arquivoChave, 'chave');

        // Caminhos finais (.enc)
        $baseCaminho  = "provas/prazo_{$prazo->id}/professor_{$professorId}/versao_{$versao}";
        $caminhoProva = "{$baseCaminho}/prova_" . Str::uuid() . '.enc';
        $caminhoChave = "{$baseCaminho}/chave_" . Str::uuid() . '.enc';

        // Gravar
        $this->gravarFicheiro($caminhoProva, $resultadoProva['conteudo']);
        $this->gravarFicheiro($caminhoChave, $resultadoChave['conteudo']);

        $tamanhoOriginalTotal = $resultadoProva['tamanho_original'] + $resultadoChave['tamanho_original'];
        $tamanhoFinalTotal    = strlen($resultadoProva['conteudo']) + strlen($resultadoChave['conteudo']);

        Log::info('[ProvaStorage] ═══ FIM ═══', [
            'prazo_id'      => $prazo->id,
            'professor_id'  => $professorId,
            'versao'        => $versao,
            'duracao_ms'    => round((microtime(true) - $inicio) * 1000, 2),
            'tamanho_original' => $this->humanSize($tamanhoOriginalTotal),
            'tamanho_final'    => $this->humanSize($tamanhoFinalTotal),
            'economia'         => $this->humanSize($tamanhoOriginalTotal - $tamanhoFinalTotal)
                . ' (' . round((1 - $tamanhoFinalTotal / max(1, $tamanhoOriginalTotal)) * 100, 1) . '%)',
        ]);

        return [
            'caminho_prova'           => $caminhoProva,
            'caminho_chave'           => $caminhoChave,
            'tamanho_original_bytes'  => $tamanhoOriginalTotal,
            'tamanho_bytes'           => $tamanhoFinalTotal,
            'comprimido'              => $resultadoProva['comprimido'] || $resultadoChave['comprimido'],
            'encriptado'              => true,
            'algoritmo_encriptacao'   => self::CIPHER,
        ];
    }

    /**
     * Lê e desencripta o conteúdo de um ficheiro guardado.
     */
    public function conteudo(string $caminhoFicheiro, bool $encriptado = true): ?string
    {
        $disk = Storage::disk(self::DISK);

        if (! $disk->exists($caminhoFicheiro)) {
            Log::warning('[ProvaStorage] Ficheiro não existe', ['caminho' => $caminhoFicheiro]);
            return null;
        }

        $pacote = $disk->get($caminhoFicheiro);

        if ($pacote === null) {
            return null;
        }

        // Ficheiros antigos (não encriptados) — devolve directo
        if (! $encriptado) {
            return $pacote;
        }

        try {
            return $this->desencriptar($pacote);
        } catch (\Throwable $e) {
            Log::error('[ProvaStorage] Desencriptação falhou', [
                'caminho' => $caminhoFicheiro,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Apaga ficheiros físicos de uma submissão.
     */
    public function apagar(string $caminhoFicheiro): void
    {
        $disk = Storage::disk(self::DISK);

        if ($disk->exists($caminhoFicheiro)) {
            $disk->delete($caminhoFicheiro);
            Log::info('[ProvaStorage] Ficheiro apagado', ['caminho' => $caminhoFicheiro]);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // PROCESSAMENTO (compressão + encriptação)
    // ═══════════════════════════════════════════════════════════

    private function processarFicheiro(UploadedFile $ficheiro, string $etiqueta): array
    {
        $conteudo        = file_get_contents($ficheiro->getRealPath());
        $tamanhoOriginal = strlen($conteudo);
        $extensao        = strtolower($ficheiro->getClientOriginalExtension());

        // ── Comprimir (apenas PDF) ──
        $compressao = $this->comprimir($conteudo, $extensao);
        $conteudo   = $compressao['conteudo'];
        $tamanhoComprimido = strlen($conteudo);

        Log::info("[ProvaStorage] ✓ Compressão {$etiqueta}", [
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

        // ── Encriptar ──
        $encriptacao = $this->encriptar($conteudo);
        $conteudo    = $encriptacao['pacote'];

        Log::info("[ProvaStorage] ✓ Encriptação {$etiqueta}", [
            'algoritmo'        => self::CIPHER,
            'antes_bytes'      => $tamanhoComprimido,
            'depois_bytes'     => strlen($conteudo),
            'overhead_bytes'   => strlen($conteudo) - $tamanhoComprimido,
        ]);

        return [
            'conteudo'         => $conteudo,
            'tamanho_original' => $tamanhoOriginal,
            'comprimido'       => $compressao['aplicada'],
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // COMPRESSÃO — qpdf → ghostscript → qpdf
    // ═══════════════════════════════════════════════════════════

    private function comprimir(string $conteudo, string $extensao): array
    {
        if ($extensao !== 'pdf') {
            return [
                'conteudo' => $conteudo,
                'aplicada' => false,
                'motivo'   => "Extensão '{$extensao}' não suportada para compressão",
            ];
        }

        exec('which gs 2>/dev/null', $o1, $gsExit);
        exec('which qpdf 2>/dev/null', $o2, $qpdfExit);
        $temGs   = $gsExit === 0;
        $temQpdf = $qpdfExit === 0;

        if (! $temGs && ! $temQpdf) {
            return ['conteudo' => $conteudo, 'aplicada' => false, 'motivo' => 'Nem Ghostscript nem qpdf instalados'];
        }

        try {
            $tmpOriginal = tempnam(sys_get_temp_dir(), 'prv_orig_') . '.pdf';
            $tmpQpdf1    = tempnam(sys_get_temp_dir(), 'prv_qpdf1_') . '.pdf';
            $tmpGs       = tempnam(sys_get_temp_dir(), 'prv_gs_') . '.pdf';
            $tmpFinal    = tempnam(sys_get_temp_dir(), 'prv_final_') . '.pdf';

            file_put_contents($tmpOriginal, $conteudo);
            $atual  = $tmpOriginal;
            $etapas = [];

            // 1. qpdf estrutural
            if ($temQpdf) {
                $cmd = sprintf(
                    'qpdf --compress-streams=y --object-streams=generate --recompress-flate --compression-level=9 %s %s 2>&1',
                    escapeshellarg($atual),
                    escapeshellarg($tmpQpdf1)
                );
                exec($cmd, $out, $exit);

                if ($exit === 0 && file_exists($tmpQpdf1) && filesize($tmpQpdf1) > 0) {
                    $etapas[] = sprintf('qpdf: %s → %s', $this->humanSize(filesize($atual)), $this->humanSize(filesize($tmpQpdf1)));
                    $atual    = $tmpQpdf1;
                }
            }

            // 2. Ghostscript /ebook
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
                    $etapas[] = sprintf('ghostscript: %s → %s', $this->humanSize(filesize($atual)), $this->humanSize(filesize($tmpGs)));
                    $atual    = $tmpGs;
                }
            }

            // 3. qpdf linearize
            if ($temQpdf && filesize($atual) > 0) {
                $cmd = sprintf(
                    'qpdf --linearize --object-streams=generate %s %s 2>&1',
                    escapeshellarg($atual),
                    escapeshellarg($tmpFinal)
                );
                exec($cmd, $out, $exit);

                if ($exit === 0 && file_exists($tmpFinal) && filesize($tmpFinal) > 0) {
                    $etapas[] = sprintf('linearize: %s → %s', $this->humanSize(filesize($atual)), $this->humanSize(filesize($tmpFinal)));
                    $atual    = $tmpFinal;
                }
            }

            $comprimido     = file_get_contents($atual);
            $tamanhoOriginal = strlen($conteudo);
            $tamanhoFinal    = strlen($comprimido);

            foreach ([$tmpOriginal, $tmpQpdf1, $tmpGs, $tmpFinal] as $f) {
                @unlink($f);
            }

            if ($comprimido === false || $tamanhoFinal === 0 || $tamanhoFinal >= $tamanhoOriginal) {
                return ['conteudo' => $conteudo, 'aplicada' => false, 'motivo' => 'Compressão não trouxe benefício'];
            }

            return [
                'conteudo' => $comprimido,
                'aplicada' => true,
                'motivo'   => implode(' → ', $etapas),
            ];
        } catch (\Throwable $e) {
            Log::warning('[ProvaStorage] Compressão falhou', ['error' => $e->getMessage()]);
            return ['conteudo' => $conteudo, 'aplicada' => false, 'motivo' => 'Erro: ' . $e->getMessage()];
        }
    }

    // ═══════════════════════════════════════════════════════════
    // ENCRIPTAÇÃO
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
    // GRAVAÇÃO
    // ═══════════════════════════════════════════════════════════

    private function gravarFicheiro(string $caminho, string $conteudo): void
    {
        $disk      = Storage::disk(self::DISK);
        $diretorio = dirname($caminho);

        try {
            if (! $disk->exists($diretorio)) {
                $disk->makeDirectory($diretorio, 0755, true);
            }
            $disk->put($caminho, $conteudo);

            Log::info('[ProvaStorage] ✓ Gravado', [
                'caminho' => $caminho,
                'bytes'   => strlen($conteudo),
            ]);
        } catch (\Throwable $e) {
            Log::error('[ProvaStorage] Falha ao gravar', [
                'caminho' => $caminho,
                'error'   => $e->getMessage(),
            ]);
            throw new \RuntimeException(
                'Não foi possível guardar o ficheiro: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 2) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }
}