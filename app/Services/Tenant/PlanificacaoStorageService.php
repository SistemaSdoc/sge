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
    /**
     * Disk principal — "local" (stancl reconfigura o root por tenant).
     */
    private const DISK = 'local';

    /**
     * Disk fallback — "/tmp/sge/tenant-cs-app/app" (RAM, ignora o symlink).
     * Só é usado se o disk principal falhar com erro de I/O.
     */
    private const DISK_FALLBACK = 'tenant-storage';

    public function guardarNovaVersao(
        Planificacao $planificacao,
        UploadedFile $ficheiro,
        string $userId
    ): PlanificacaoVersao {
        $proximaVersao = $planificacao->versao_atual + 1;
        $extensao      = strtolower($ficheiro->getClientOriginalExtension());
        $caminho       = $this->construirCaminho($planificacao, $proximaVersao, $extensao);

        $conteudo = file_get_contents($ficheiro->getRealPath());

        $this->gravarFicheiro($caminho, $conteudo, $planificacao);

        $versao = $planificacao->versoes()->create([
            'versao'           => $proximaVersao,
            'caminho_ficheiro' => $caminho,
            'nome_original'    => $ficheiro->getClientOriginalName(),
            'tamanho_bytes'    => $ficheiro->getSize(),
            'mime_type'        => $ficheiro->getMimeType() ?? 'application/octet-stream',
            'uploaded_by'      => $userId,
        ]);

        $planificacao->update(['versao_atual' => $proximaVersao]);

        return $versao;
    }

    /**
     * Tenta gravar no disk principal; se falhar por erro de I/O, tenta o fallback.
     */
    private function gravarFicheiro(string $caminho, string $conteudo, Planificacao $planificacao): void
    {
        $diskPrincipal  = Storage::disk(self::DISK);
        $rootPrincipal  = $diskPrincipal->path('');
        $rootReal       = realpath($rootPrincipal) ?: $rootPrincipal;

        Log::info('[PlanificacaoStorage] Tentar gravar', [
            'caminho'      => $caminho,
            'disk'         => self::DISK,
            'root'         => $rootPrincipal,
            'root_real'    => $rootReal,
            'planificacao' => $planificacao->id,
        ]);

        try {
            // Garantir que a diretoria existe (criada recursivamente)
            $diretorio = dirname($caminho);
            if (! $diskPrincipal->exists($diretorio)) {
                $diskPrincipal->makeDirectory($diretorio, 0755, true);
            }

            $diskPrincipal->put($caminho, $conteudo);

            Log::info('[PlanificacaoStorage] Gravado com sucesso no disk principal', [
                'caminho'   => $caminho,
                'realpath'  => realpath($diskPrincipal->path($caminho)) ?: 'desconhecido',
            ]);
            return;
        } catch (\Throwable $e) {
            Log::error('[PlanificacaoStorage] Falha no disk principal — a tentar fallback', [
                'caminho' => $caminho,
                'error'   => $e->getMessage(),
                'root'    => $rootPrincipal,
                'root_real' => $rootReal,
            ]);
        }

        // Fallback: disk em /tmp directo (sem symlink)
        try {
            $diskFallback = Storage::disk(self::DISK_FALLBACK);

            $diretorio = dirname($caminho);
            if (! $diskFallback->exists($diretorio)) {
                $diskFallback->makeDirectory($diretorio, 0755, true);
            }

            $diskFallback->put($caminho, $conteudo);

            Log::warning('[PlanificacaoStorage] Gravado no fallback /tmp', [
                'caminho' => $caminho,
                'root'    => $diskFallback->path(''),
            ]);
        } catch (\Throwable $e) {
            Log::error('[PlanificacaoStorage] Fallback também falhou', [
                'caminho' => $caminho,
                'error'   => $e->getMessage(),
            ]);
            throw new \RuntimeException(
                "Não foi possível guardar o ficheiro da planificação. " .
                "Verifique o storage do tenant. Detalhe: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function caminhoAbsoluto(PlanificacaoVersao $versao): ?string
    {
        // Tenta no disk principal
        $disk = Storage::disk(self::DISK);
        if ($disk->exists($versao->caminho_ficheiro)) {
            return $disk->path($versao->caminho_ficheiro);
        }

        // Tenta no fallback
        $fallback = Storage::disk(self::DISK_FALLBACK);
        if ($fallback->exists($versao->caminho_ficheiro)) {
            return $fallback->path($versao->caminho_ficheiro);
        }

        return null;
    }

    public function conteudo(PlanificacaoVersao $versao): ?string
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

    public function apagarTodasVersoes(Planificacao $planificacao): void
    {
        $disks = [Storage::disk(self::DISK), Storage::disk(self::DISK_FALLBACK)];

        foreach ($planificacao->versoes as $versao) {
            foreach ($disks as $disk) {
                if ($disk->exists($versao->caminho_ficheiro)) {
                    $disk->delete($versao->caminho_ficheiro);
                }
            }
        }
    }

    private function construirCaminho(
        Planificacao $planificacao,
        int $versao,
        string $extensao
    ): string {
        $periodoSlug = Str::slug($planificacao->periodo);

        return sprintf(
            'planificacoes/%s/%s/%s/%s/v%d.%s',
            $planificacao->ano_letivo_id,
            $planificacao->disciplina_id,
            $planificacao->classe_id,
            $periodoSlug,
            $versao,
            $extensao
        );
    }
}