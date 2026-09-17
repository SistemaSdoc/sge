<?php

namespace App\Services\Tenant\Recibos;

use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Pagamento;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Filesystem\Filesystem;
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

        $stream = $disco->readStream($caminho);
        if (! is_resource($stream)) {
            return false;
        }

        $conteudo = fread($stream, 5);
        fclose($stream);

        return \is_string($conteudo) && str_starts_with($conteudo, '%PDF-');
    }

    public function conteudo(string $caminho): string
    {
        return $this->disco()->get($caminho);
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
            'logoBase64' => $this->logoBase64($instituicao),
        ])->output();

        if (! str_starts_with($conteudo, '%PDF-')) {
            throw new \RuntimeException("O recibo [{$numeroRecibo}] não foi gerado como PDF válido.");
        }

        $caminho = "recibos/{$pagamento->instituicao_id}/{$numeroRecibo}.pdf";
        $temporario = "{$caminho}.tmp-".Str::uuid();
        $disco = $this->disco();

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

    private function logoBase64(Instituicao $instituicao): ?string
    {
        if (! $instituicao->logo || ! Storage::disk(config('filesystems.default'))->exists($instituicao->logo)) {
            return null;
        }

        $extensao = strtolower(pathinfo($instituicao->logo, PATHINFO_EXTENSION));
        $mime = match ($extensao) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => null,
        };

        return $mime
            ? "data:{$mime};base64,".base64_encode(Storage::disk(config('filesystems.default'))->get($instituicao->logo))
            : null;
    }

    private function disco(): Filesystem
    {
        return Storage::disk('private');
    }
}
