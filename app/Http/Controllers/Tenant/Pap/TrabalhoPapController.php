<?php

namespace App\Http\Controllers\Tenant\Pap;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Pap\Trabalho\AprovarTrabalhoPapRequest;
use App\Http\Requests\Tenant\Pap\Trabalho\SolicitarCorrecaoTrabalhoPapRequest;
use App\Http\Requests\Tenant\Pap\Trabalho\SubmeterTrabalhoPapRequest;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\Pap\Consultas\PrepararDetalheGrupoPapService;
use App\Services\Tenant\Pap\Trabalho\RevisaoTrabalhoPapCoordenacaoService;
use App\Services\Tenant\Pap\Trabalho\RevisaoTrabalhoPapTutorService;
use App\Services\Tenant\Pap\Trabalho\SubmissaoTrabalhoPapService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class TrabalhoPapController extends Controller
{
    /**
     * Recebe o serviço responsável pelo ciclo de vida do trabalho PAP.
     */
    public function __construct(
        private readonly SubmissaoTrabalhoPapService $submissaoTrabalhoPapService,
        private readonly RevisaoTrabalhoPapTutorService $revisaoTrabalhoPapTutorService,
        private readonly RevisaoTrabalhoPapCoordenacaoService $revisaoTrabalhoPapCoordenacaoService,
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
    ) {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('view', $grupoPap);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        $trabalho = $grupoPap->trabalhoPap;
        $instituicaoTutora = $cursoTutelado->instituicaoTutora;
        $nomeCurso = $cursoTutelado->instituicaoCurso?->curso?->nome;

        if (! $trabalho) {
            return inertia('cursos-tutelados/classes/turnos/turmas/pap/trabalho/indisponivel', [
                'grupoPap' => $grupoPap->only('id', 'nome_grupo', 'status_aprovacao'),
            ]);
        }

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
    ) {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('downloadVersaoTrabalho', $grupoPap);
        $trabalho = $grupoPap->trabalhoPap;
        $versao = $trabalho?->versoes()->where('numero_versao', $numeroVersao)->firstOrFail();

        if (! Storage::disk('private')->exists($versao->caminho_ficheiro)) {
            abort(404, 'Ficheiro não encontrado.');
        }

        return response(
            Storage::disk('private')->get($versao->caminho_ficheiro),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$versao->nome_original.'"',
            ]
        );
    }

    /**
     * Recebe uma nova versão do trabalho submetida pelo aluno.
     */
    public function submeter(
        SubmeterTrabalhoPapRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('submeterTrabalho', $grupoPap);
        $validated = $request->validated();
        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerSubmetido()) {
            return back()->withErrors([
                'ficheiro' => 'O trabalho não pode ser submetido neste momento.',
            ]);
        }

        $this->submissaoTrabalhoPapService->submeter(
            $trabalho,
            Auth::guard('tenant')->user(),
            $validated['ficheiro'],
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Trabalho submetido com sucesso. Aguarda revisão do professor tutor.',
        ]);
    }

    /**
     * Regista a aprovação do trabalho pelo tutor.
     */
    public function aprovarComoTutor(
        AprovarTrabalhoPapRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        abort_unless(! $user->hasAnyRole(['Director', 'Subdirector']), 403);
        Gate::forUser($user)->authorize('aprovarTrabalhoComoTutor', $grupoPap);
        $validated = $request->validated();
        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerAnalisadoPeloTutor()) {
            return back()->withErrors([
                'trabalho' => 'O trabalho não está disponível para análise do tutor.',
            ]);
        }

        $this->revisaoTrabalhoPapTutorService->aprovar($trabalho, $user, $validated['comentario'] ?? null);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Trabalho enviado para a coordenação.',
        ]);
    }

    /**
     * Regista um pedido de correcção do trabalho feito pelo tutor.
     */
    public function solicitarCorrecaoComoTutor(
        SolicitarCorrecaoTrabalhoPapRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('solicitarCorrecaoTrabalhoComoTutor', $grupoPap);
        $validated = $request->validated();
        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerAnalisadoPeloTutor()) {
            return back()->withErrors([
                'trabalho' => 'O trabalho não está disponível para análise do tutor.',
            ]);
        }

        $this->revisaoTrabalhoPapTutorService->solicitarCorrecao(
            $trabalho,
            Auth::guard('tenant')->user(),
            $validated['comentario'],
            $request->file('ficheiro'),
        );

        return redirect()->route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', [
            'instituicao' => $instituicao,
            'cursoTutelado' => $cursoTutelado,
            'cursoClasse' => $cursoClasse,
            'cursoClasseTurno' => $cursoClasseTurno,
            'turma' => $turma,
            'grupoPap' => $grupoPap,
        ])->with('toast', ['type' => 'warning', 'message' => 'Correção solicitada ao aluno.']);
    }

    /**
     * Regista a aprovação definitiva do trabalho pela coordenação.
     */
    public function aprovarComoCoordenacao(
        AprovarTrabalhoPapRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        abort_unless(! $user->hasAnyRole(['Director', 'Subdirector']), 403);
        Gate::forUser($user)->authorize('aprovarTrabalhoComoCoordenacao', $grupoPap);
        $validated = $request->validated();
        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerAnalisadoPelaCoordenacao()) {
            return back()->withErrors([
                'trabalho' => 'O trabalho não está disponível para análise da coordenação.',
            ]);
        }

        $this->revisaoTrabalhoPapCoordenacaoService->aprovar(
            $trabalho,
            $user,
            $validated['comentario'] ?? null,
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Trabalho aprovado com sucesso.',
        ]);
    }

    /**
     * Regista um pedido de correcção do trabalho feito pela coordenação.
     */
    public function solicitarCorrecaoComoCoordenacao(
        SolicitarCorrecaoTrabalhoPapRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        abort_unless(! $user->hasAnyRole(['Director', 'Subdirector']), 403);
        Gate::forUser($user)->authorize('solicitarCorrecaoTrabalhoComoCoordenacao', $grupoPap);
        $validated = $request->validated();
        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerAnalisadoPelaCoordenacao()) {
            return back()->withErrors([
                'trabalho' => 'O trabalho não está disponível para análise da coordenação.',
            ]);
        }

        $this->revisaoTrabalhoPapCoordenacaoService->solicitarCorrecao(
            $trabalho,
            $user,
            $validated['comentario'],
            $request->file('ficheiro'),
        );

        return redirect()->route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ])->with('toast', [
            'type' => 'warning',
            'message' => 'Correção solicitada ao aluno.',
        ]);
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
    ) {
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
    ) {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('downloadVersaoTrabalho', $grupoPap);
        $trabalho = $grupoPap->trabalhoPap;
        $feedback = $trabalho?->feedbacks()->findOrFail($feedbackId);
        /** @var FilesystemAdapter $privateStorage */
        $privateStorage = Storage::disk('private');

        if (
            ! $feedback->caminho_ficheiro_correcao ||
            ! $privateStorage->exists($feedback->caminho_ficheiro_correcao)
        ) {
            abort(404, 'Ficheiro de correção não encontrado.');
        }

        return $privateStorage->download(
            $feedback->caminho_ficheiro_correcao,
            $feedback->nome_original_correcao ?? 'correcao.pdf'
        );
    }
}
