<?php

namespace App\Services\Tenant\Recibos;

use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Pagamento;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReciboPdfService
{
    public function existe(?string $caminho): bool
    {
        if (! $caminho) {
            return false;
        }

        $disco = $this->disco();
        if (! $disco->exists($caminho)) {
            return false;
        }

        $ficheiro = $disco->path($caminho);
        if (! is_file($ficheiro) || ! is_readable($ficheiro)) {
            return false;
        }

        $conteudo = file_get_contents($ficheiro, false, null, 0, 5);

        return \is_string($conteudo) && str_starts_with($conteudo, '%PDF-');
    }

    public function caminhoAbsoluto(string $caminho): string
    {
        return $this->disco()->path($caminho);
    }

    public function gerar(Pagamento $pagamento, string $numeroRecibo): string
    {
        $pagamento->load(
            'aluno.instituicao',
            'aluno.user',
            'itens.itemPagavel',
            'registadoPor',
            'aluno.turmaActual.cursoClasseTurno.cursoClasse.classe',
            'aluno.turmaActual.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
            'aluno.turmaActual.cursoClasseTurno.turno',
        );

        $instituicao = $pagamento->aluno->instituicao
            ?? Instituicao::query()->findOrFail($pagamento->instituicao_id);

        $conteudo = Pdf::loadView('pdf.recibo', [
            'pagamento' => $pagamento,
            'instituicao' => $instituicao,
            'numeroRecibo' => $numeroRecibo,
        ])->output();

        if (! str_starts_with($conteudo, '%PDF-')) {
            throw new \RuntimeException("O recibo [{$numeroRecibo}] não foi gerado como PDF válido.");
        }

        $caminho = "recibos/{$pagamento->instituicao_id}/{$numeroRecibo}.pdf";
        $temporario = "{$caminho}.tmp-".Str::uuid();
        $disco = $this->disco();
        $diretorio = "recibos/{$pagamento->instituicao_id}";

        if (! $disco->makeDirectory($diretorio)) {
            throw new \RuntimeException("Não foi possível criar o diretório [{$diretorio}].");
        }

        try {
            if (! $disco->put($temporario, $conteudo)) {
                throw new \RuntimeException("Não foi possível gravar o recibo temporário [{$temporario}].");
            }

            if (! $disco->move($temporario, $caminho)) {
                throw new \RuntimeException("Não foi possível publicar o recibo [{$caminho}].");
            }
        } finally {
            if ($disco->exists($temporario)) {
                $disco->delete($temporario);
            }
        }

        return $caminho;
    }

    private function disco(): FilesystemAdapter
    {
        return Storage::disk('private');
    }
}
