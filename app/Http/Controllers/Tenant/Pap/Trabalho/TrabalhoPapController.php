<?php

namespace App\Http\Controllers\Tenant\Pap\Trabalho;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\Pap\Consultas\PrepararDetalheGrupoPapService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrabalhoPapController extends Controller
{
    public function __construct(
        private readonly PrepararDetalheGrupoPapService $prepararDetalheGrupoPapService,
    ) {}

    /**
     * Apresenta o estado, as versões e o histórico de feedback do trabalho.
     */
    public function show(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ): InertiaResponse {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        Gate::forUser($user)->authorize('view', $grupoPap);

        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho) {
            return inertia('cursos-tutelados/classes/turnos/turmas/pap/trabalho/indisponivel', [
                'grupoPap' => $grupoPap->only('id', 'nome_grupo', 'status_aprovacao'),
            ]);
        }

        $instituicaoTutora = $cursoTutelado->instituicaoTutora;

        $nomeCurso = $cursoTutelado->instituicaoCurso?->curso?->nome;

        $trabalhoDetalhes = $this->prepararDetalheGrupoPapService->detalhesTrabalho(
            $grupoPap,
            $instituicaoTutora,
            $nomeCurso,
            $instituicaoTutora?->sigla
                ?? $this->prepararDetalheGrupoPapService->siglaTutoraExterna($cursoTutelado),
        );

        return inertia('cursos-tutelados/classes/turnos/turmas/pap/trabalho/show', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'grupoPap' => $grupoPap->only('id', 'nome_grupo'),
            'trabalho' => $trabalhoDetalhes,
            'can' => [
                'submeter' => $user->can('submeterTrabalho', $grupoPap),
                'aprovarComoTutor' => $user->can('aprovarTrabalhoComoTutor', $grupoPap),
                'solicitarCorrecaoComoTutor' => $user->can('solicitarCorrecaoTrabalhoComoTutor', $grupoPap),
                'aprovarComoCoordenacao' => $user->can('aprovarTrabalhoComoCoordenacao', $grupoPap),
                'solicitarCorrecaoComoCoordenacao' => $user->can('solicitarCorrecaoTrabalhoComoCoordenacao', $grupoPap),
                'downloadVersao' => $user->can('downloadVersaoTrabalho', $grupoPap),
            ],
        ]);
    }

    /**
     * Apresenta uma versão do trabalho no navegador.
     */
    public function visualizar(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
        int $numeroVersao,
    ): Response {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('downloadVersaoTrabalho', $grupoPap);

        $trabalho = $grupoPap->trabalhoPap;

        $versao = $trabalho?->versoes()->where('numero_versao', $numeroVersao)->firstOrFail();

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
    }

    /**
     * Descarrega uma versão do trabalho PAP.
     */
    public function download(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
        int $numeroVersao,
    ): StreamedResponse {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('downloadVersaoTrabalho', $grupoPap);

        $trabalho = $grupoPap->trabalhoPap;

        $versao = $trabalho?->versoes()->where('numero_versao', $numeroVersao)->firstOrFail();

        /** @var FilesystemAdapter $privateStorage */
        $privateStorage = Storage::disk('private');

        if (! $privateStorage->exists($versao->caminho_ficheiro)) {
            abort(404, 'Ficheiro não encontrado.');
        }

        return $privateStorage->download($versao->caminho_ficheiro, $versao->nome_original);
    }

    /**
     * Descarrega o ficheiro de correcção associado ao feedback.
     */
    public function downloadCorrecao(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
        string $feedbackId,
    ): StreamedResponse {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('downloadVersaoTrabalho', $grupoPap);

        $trabalho = $grupoPap->trabalhoPap;

        $feedback = $trabalho?->feedbacks()->findOrFail($feedbackId);

        /** @var FilesystemAdapter $privateStorage */
        $privateStorage = Storage::disk('private');

        if (
            ! $feedback->caminho_ficheiro_correcao
            || ! $privateStorage->exists($feedback->caminho_ficheiro_correcao)
        ) {
            abort(404, 'Ficheiro de correção não encontrado.');
        }

        return $privateStorage->download(
            $feedback->caminho_ficheiro_correcao,
            $feedback->nome_original_correcao ?? 'correcao.pdf',
        );
    }
}
