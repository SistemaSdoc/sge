<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\TrabalhoPapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TrabalhoPapController extends Controller
{
    public function __construct(
        private readonly TrabalhoPapService $service
    ) {}

    /**
     * Página principal do trabalho PAP.
     * Mostra o estado atual, versões e histórico de feedbacks.
     */
    public function show(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('view', $grupoPap);

        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $trabalho = $grupoPap->trabalhoPap()->with([
            'versoes.submetidoPor:id,nome',
            'versoes.feedbacks.utilizador:id,nome',
            'feedbacks.utilizador:id,nome',
            'feedbacks.versao:id,numero_versao',
            'aprovadoPor:id,nome',
        ])->first();

        // Se o tema ainda não foi aprovado, não existe trabalho
        if (! $trabalho) {
            return inertia('cursos-tutelados/classes/turnos/turmas/pap/trabalho/indisponivel', [
                'grupoPap' => $grupoPap->only('id', 'nome_grupo', 'status_aprovacao'),
            ]);
        }

        return inertia('cursos-tutelados/classes/turnos/turmas/pap/trabalho/show', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'grupoPap' => $grupoPap->only('id', 'nome_grupo'),
            'trabalho' => [
                'id' => $trabalho->id,
                'status' => $trabalho->status,
                'data_aprovacao' => $trabalho->data_aprovacao?->toIso8601String(),
                'aprovado_por' => $trabalho->aprovadoPor?->nome,
                'versoes' => $trabalho->versoes->map(fn ($v) => [
                    'id' => $v->id,
                    'numero_versao' => $v->numero_versao,
                    'nome_original' => $v->nome_original,
                    'status_quando_submetido' => $v->status_quando_submetido,
                    'submetido_por' => $v->submetidoPor?->nome,
                    'created_at' => $v->created_at?->toIso8601String(),
                    'feedbacks' => $v->feedbacks->map(fn ($f) => [
                        'id' => $f->id,
                        'tipo' => $f->tipo,
                        'comentario' => $f->comentario,
                        'utilizador' => $f->utilizador?->nome,
                        'created_at' => $f->created_at?->toIso8601String(),
                        'tem_ficheiro_correcao' => ! is_null($f->caminho_ficheiro_correcao),  // ← faltava
                        'nome_original_correcao' => $f->nome_original_correcao,               // ← falta
                    ]),
                ]),
            ],
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

    // TrabalhoPapController
    public function visualizar(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
        int $numeroVersao
    ) {
        $this->authorize('downloadVersaoTrabalho', $grupoPap);

        $trabalho = $grupoPap->trabalhoPap;

        $versao = $trabalho?->versoes()
            ->where('numero_versao', $numeroVersao)
            ->firstOrFail();

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
     * Aluno submete um novo PDF.
     */
    public function submeter(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('submeterTrabalho', $grupoPap);

        $request->validate([
            'ficheiro' => ['required', 'file', 'mimes:pdf', 'max:20480'], // 20MB
        ], [
            'ficheiro.uploaded' => 'O servidor não conseguiu receber o trabalho. Tente novamente.',
        ]);

        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerSubmetido()) {
            return back()->withErrors([
                'ficheiro' => 'O trabalho não pode ser submetido neste momento.',
            ]);
        }

        $this->service->submeter($trabalho, Auth::user(), $request->file('ficheiro'));

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Trabalho submetido com sucesso. Aguarda revisão do professor tutor.',
        ]);
    }

    /**
     * Tutor aprova o trabalho e envia para a coordenação.
     */
    public function aprovarComoTutor(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('aprovarTrabalhoComoTutor', $grupoPap);

        $validated = $request->validate([
            'comentario' => ['nullable', 'string', 'max:2000'],
        ]);

        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerAnalisadoPeloTutor()) {
            return back()->withErrors([
                'trabalho' => 'O trabalho não está disponível para análise do tutor.',
            ]);
        }

        $this->service->aprovarComoTutor($trabalho, Auth::user(), $validated['comentario'] ?? null);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Trabalho enviado para a coordenação.',
        ]);
    }

    /**
     * Tutor solicita correção ao aluno.
     */
    public function solicitarCorrecaoComoTutor(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('solicitarCorrecaoTrabalhoComoTutor', $grupoPap);

        $validated = $request->validate([
            'comentario' => ['required', 'string', 'min:10', 'max:2000'],
            'ficheiro' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerAnalisadoPeloTutor()) {
            return back()->withErrors([
                'trabalho' => 'O trabalho não está disponível para análise do tutor.',
            ]);
        }

        $this->service->solicitarCorrecaoComoTutor(
            $trabalho,
            Auth::user(),
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
     * Coordenação aprova o trabalho definitivamente.
     */
    public function aprovarComoCoordenacao(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('aprovarTrabalhoComoCoordenacao', $grupoPap);

        $validated = $request->validate([
            'comentario' => ['nullable', 'string', 'max:2000'],
        ]);

        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerAnalisadoPelaCoordenacao()) {
            return back()->withErrors([
                'trabalho' => 'O trabalho não está disponível para análise da coordenação.',
            ]);
        }

        $this->service->aprovarComoCoordenacao($trabalho, Auth::user(), $validated['comentario'] ?? null);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Trabalho aprovado com sucesso.',
        ]);
    }

    /**
     * Coordenação solicita correção ao aluno.
     */
    public function solicitarCorrecaoComoCoordenacao(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('solicitarCorrecaoTrabalhoComoCoordenacao', $grupoPap);

        $validated = $request->validate([
            'comentario' => ['required', 'string', 'min:10', 'max:2000'],
            'ficheiro' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerAnalisadoPelaCoordenacao()) {
            return back()->withErrors([
                'trabalho' => 'O trabalho não está disponível para análise da coordenação.',
            ]);
        }

        $this->service->solicitarCorrecaoComoCoordenacao(
            $trabalho,
            Auth::user(),
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
     * Download de uma versão do trabalho.
     */
    public function download(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
        int $numeroVersao
    ) {
        $this->authorize('downloadVersaoTrabalho', $grupoPap);

        $trabalho = $grupoPap->trabalhoPap;

        $versao = $trabalho?->versoes()
            ->where('numero_versao', $numeroVersao)
            ->firstOrFail();

        if (! Storage::disk('private')->exists($versao->caminho_ficheiro)) {
            abort(404, 'Ficheiro não encontrado.');
        }

        return Storage::disk('private')->download(
            $versao->caminho_ficheiro,
            $versao->nome_original
        );
    }

    public function downloadCorrecao(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
        string $feedbackId
    ) {
        $this->authorize('downloadVersaoTrabalho', $grupoPap);

        $trabalho = $grupoPap->trabalhoPap;
        $feedback = $trabalho?->feedbacks()->findOrFail($feedbackId);

        if (
            ! $feedback->caminho_ficheiro_correcao ||
            ! Storage::disk('private')->exists($feedback->caminho_ficheiro_correcao)
        ) {
            abort(404, 'Ficheiro de correção não encontrado.');
        }

        return Storage::disk('private')->download(
            $feedback->caminho_ficheiro_correcao,
            $feedback->nome_original_correcao ?? 'correcao.pdf'
        );
    }
}
