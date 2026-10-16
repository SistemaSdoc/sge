<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Planificacao;
use App\Models\Tenant\PlanificacaoVersao;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlanificacaoStorageService
{
    /**
     * Disk usado. Podes usar 'local' ou um disk dedicado no config/filesystems.
     */
    private const DISK = 'local';

    /**
     * Guarda um ficheiro novo como próxima versão.
     */
    public function guardarNovaVersao(
        Planificacao $planificacao,
        UploadedFile $ficheiro,
        string $userId
    ): PlanificacaoVersao {
        $proximaVersao = $planificacao->versao_atual + 1;
        $extensao = strtolower($ficheiro->getClientOriginalExtension());

        $caminho = $this->construirCaminho($planificacao, $proximaVersao, $extensao);

        Storage::disk(self::DISK)->put(
            $caminho,
            file_get_contents($ficheiro->getRealPath())
        );

        $versao = $planificacao->versoes()->create([
            'versao'          => $proximaVersao,
            'caminho_ficheiro'=> $caminho,
            'nome_original'   => $ficheiro->getClientOriginalName(),
            'tamanho_bytes'   => $ficheiro->getSize(),
            'mime_type'       => $ficheiro->getMimeType() ?? 'application/octet-stream',
            'uploaded_by'     => $userId,
        ]);

        $planificacao->update(['versao_atual' => $proximaVersao]);

        return $versao;
    }

    /**
     * Caminho absoluto no disco para download/stream.
     */
    public function caminhoAbsoluto(PlanificacaoVersao $versao): ?string
    {
        $disk = Storage::disk(self::DISK);

        if (! $disk->exists($versao->caminho_ficheiro)) {
            return null;
        }

        return $disk->path($versao->caminho_ficheiro);
    }

    /**
     * Lê o conteúdo binário (para stream no browser).
     */
    public function conteudo(PlanificacaoVersao $versao): ?string
    {
        $disk = Storage::disk(self::DISK);

        if (! $disk->exists($versao->caminho_ficheiro)) {
            return null;
        }

        return $disk->get($versao->caminho_ficheiro);
    }

    /**
     * Remove os ficheiros físicos de todas as versões (ao apagar planificação).
     */
    public function apagarTodasVersoes(Planificacao $planificacao): void
    {
        $disk = Storage::disk(self::DISK);

        foreach ($planificacao->versoes as $versao) {
            if ($disk->exists($versao->caminho_ficheiro)) {
                $disk->delete($versao->caminho_ficheiro);
            }
        }
    }

    /**
     * Estrutura de pastas:
     * planificacoes/{ano_letivo_id}/{disciplina_id}/{classe_id}/{periodo_slug}/v{n}.{ext}
     */
    private function construirCaminho(
        Planificacao $planificacao,
        int $versao,
        string $extensao
    ): string {
        $periodoSlug = Str::slug($planificacao->periodo); // "1-trimestre"

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