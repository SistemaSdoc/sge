<?php

namespace App\Http\Controllers\Tenant\Colegios\Pap\Trabalho;

use App\Http\Controllers\Controller;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;
use App\Services\Tenant\Pap\Contexto\ExecutarAcaoNoGrupoPapTutelado;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrabalhoPapController extends Controller
{
    public function __construct(
        private readonly ExecutarAcaoNoGrupoPapTutelado $executarAcaoNoGrupoPapTutelado,
    ) {}

    /**
     * Descarrega uma versão do trabalho no tenant de origem.
     */
    public function download(
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap,
        int $numeroVersao,
    ): StreamedResponse {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        abort_unless($user->can('grupopap.view'), 403);

        return $this->executarAcaoNoGrupoPapTutelado->executar(
            $colegio,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
            $user,
            function (GrupoPap $grupoPap) use ($numeroVersao): StreamedResponse {
                $versao = $grupoPap->trabalhoPap?->versoes()
                    ->where('numero_versao', $numeroVersao)
                    ->firstOrFail();

                /** @var FilesystemAdapter $privateStorage */
                $privateStorage = Storage::disk('private');

                if (! $privateStorage->exists($versao->caminho_ficheiro)) {
                    abort(404, 'Ficheiro não encontrado.');
                }

                return $privateStorage->download($versao->caminho_ficheiro, $versao->nome_original);
            },
        );
    }

    /**
     * Apresenta no navegador uma versão do trabalho no tenant de origem.
     */
    public function visualizar(
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap,
        int $numeroVersao,
    ): Response {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        abort_unless($user->can('grupopap.view'), 403);

        return $this->executarAcaoNoGrupoPapTutelado->executar(
            $colegio,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
            $user,
            function (GrupoPap $grupoPap) use ($numeroVersao): Response {
                $versao = $grupoPap->trabalhoPap?->versoes()
                    ->where('numero_versao', $numeroVersao)
                    ->firstOrFail();

                /** @var FilesystemAdapter $privateStorage */
                $privateStorage = Storage::disk('private');

                if (! $privateStorage->exists($versao->caminho_ficheiro)) {
                    abort(404, 'Ficheiro não encontrado.');
                }

                return response(
                    $privateStorage->get($versao->caminho_ficheiro),
                    200,
                    [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="'.$versao->nome_original.'"',
                    ],
                );
            },
        );
    }

    /**
     * Descarrega um ficheiro de correcção no tenant de origem.
     */
    public function downloadCorrecao(
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap,
        string $feedbackId,
    ): StreamedResponse {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        abort_unless($user->can('grupopap.view'), 403);

        return $this->executarAcaoNoGrupoPapTutelado->executar(
            $colegio,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
            $user,
            function (GrupoPap $grupoPap) use ($feedbackId): StreamedResponse {
                $feedback = $grupoPap->trabalhoPap?->feedbacks()->findOrFail($feedbackId);
                /** @var FilesystemAdapter $privateStorage */
                $privateStorage = Storage::disk('private');

                if (
                    ! $feedback?->caminho_ficheiro_correcao
                    || ! $privateStorage->exists($feedback->caminho_ficheiro_correcao)
                ) {
                    abort(404, 'Ficheiro de correção não encontrado.');
                }

                return $privateStorage->download(
                    $feedback->caminho_ficheiro_correcao,
                    $feedback->nome_original_correcao ?? 'correcao.pdf',
                );
            },
        );
    }
}
