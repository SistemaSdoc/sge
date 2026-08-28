<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\HistoricoAprovacaoPap;
use App\Services\Tenant\AprovacaoTemaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GrupoPapAprovacaoController extends Controller
{
    public function __construct(
        private AprovacaoTemaService $service
    ) {}

    /**
     * Listar temas PAP pendentes de aprovação
     * para o coordenador do curso.
     */
    public function pendentes()
    {
        $user = Auth::guard('tenant')->user();

        // O utilizador precisa estar associado a um professor
        if (! $user->professor) {
            return inertia('tenant/pap/PendentesAprovacao', [
                'temasPendentes' => [],
                'rotaAprovar' => null,
                'rotaReprovar' => null,
                'rotaMelhoria' => null,
            ]);
        }

        // Buscar apenas os temas dos cursos
        // onde o professor é coordenador.
        $temasPendentes = $this->service->temasPendentesParaCoordenador(
            $user->professor->id
        );

        return inertia('tenant/pap/PendentesAprovacao', [
            'temasPendentes' => $temasPendentes,

            // Rotas para o frontend
            'rotaAprovar' => route(
                'grupo-pap-aprovacao.aprovar',
                ':id'
            ),

            'rotaReprovar' => route(
                'grupo-pap-aprovacao.reprovar',
                ':id'
            ),

            'rotaMelhoria' => route(
                'grupo-pap-aprovacao.solicitar-melhoria',
                ':id'
            ),
        ]);
    }

    public function aprovarTutor(Request $request, GrupoPap $grupoPap)
    {

        $this->authorize('aprovarComoTutor', $grupoPap);

        $validated = $request->validate([
            'comentario' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($grupoPap, $validated) {
            $grupoPap->update([
                'status_aprovacao' => GrupoPap::APROVACAO_PENDENTE,
            ]);

            HistoricoAprovacaoPap::create([
                'grupo_pap_id' => $grupoPap->id,
                'utilizador_id' => Auth::guard('tenant')->id(),
                'estado_anterior' => GrupoPap::APROVACAO_SUBMETIDO,
                'estado_novo' => GrupoPap::APROVACAO_PENDENTE,
                'tema' => $grupoPap->tema_grupo,
                'problema' => $grupoPap->problema,
                'objectivos' => $grupoPap->objectivos,
                'comentario' => $validated['comentario'] ?? 'Aprovado pelo professor tutor.',
            ]);
        });

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Tema enviado para a coordenação.',
        ]);
    }

    /**
     * Solicitar melhoria no tema PAP como tutor.
     */
    public function solicitarMelhoriaComoTutor(Request $request, GrupoPap $grupoPap)
    {
        $this->authorize('solicitarMelhoriaComoTutor', $grupoPap);

        $validated = $request->validate([
            'recomendacao' => 'required|string|min:10|max:2000',
        ]);

        DB::transaction(function () use ($grupoPap, $validated) {
            $grupoPap->update([
                'status_aprovacao' => GrupoPap::APROVACAO_MELHORIA_TUTOR,
                'comentario_aprovacao' => $validated['recomendacao'],
            ]);

            HistoricoAprovacaoPap::create([
                'grupo_pap_id' => $grupoPap->id,
                'utilizador_id' => Auth::guard('tenant')->id(),
                'estado_anterior' => GrupoPap::APROVACAO_SUBMETIDO,
                'estado_novo' => GrupoPap::APROVACAO_MELHORIA_TUTOR,
                'tema' => $grupoPap->tema_grupo,
                'problema' => $grupoPap->problema,
                'objectivos' => $grupoPap->objectivos,
                'comentario' => $validated['recomendacao'],
            ]);
        });

        return back()->with('toast', [
            'type' => 'warning',
            'message' => 'Melhoria solicitada aos alunos.',
        ]);
    }

    /**
     * Aprovar tema PAP
     */
    public function aprovar(Request $request, GrupoPap $grupoPap)
    {
        // Verificar se pode ser aprovado
        if (! $grupoPap->podeSerAprovado()) {
            return back()->withErrors([
                'grupo' => 'Este tema já foi finalizado e não pode ser alterado.',
            ])->with('status', 'erro');
        }

        $validated = $request->validate([
            'comentario' => ['nullable', 'string', 'max:2000'],
        ]);

        $resultado = $this->service->aprovar(
            $grupoPap,
            Auth::guard('tenant')->user(),
            $validated['comentario'] ?? null
        );

        if (! $resultado) {
            return back()->withErrors([
                'grupo' => 'Erro ao aprovar o tema.',
            ]);
        }

        // ✅ FORÇA RELOAD COMPLETO
        return redirect()->back()->with(
            'success',
            'Tema aprovado com sucesso.'
        );
    }

    /**
     * Reprovar tema PAP
     */
    public function reprovar(
        Request $request,
        GrupoPap $grupoPap
    ) {
        // Verificar autorização
        /* $this->authorize(
             'reprovarTema',
             $grupoPap
         );*/

        // O motivo da reprovação é obrigatório
        $validated = $request->validate([
            'motivo' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ]);

        // Executar reprovação
        $resultado = $this->service->reprovar(
            $grupoPap,
            Auth::guard('tenant')->user(),
            $validated['motivo']
        );

        // Verificar se o tema pode ser reprovado
        if (! $resultado) {
            return back()->withErrors([
                'grupo' => 'Este tema não pode ser reprovado neste momento.',
            ]);
        }

        return back()->with(
            'success',
            'Tema reprovado com sucesso.'
        );
    }

    /**
     * Atualizar tema PAP após correção
     */
    public function atualizar(Request $request, GrupoPap $grupoPap)
    {
        $validated = $request->validate([
            'nome_grupo' => 'required|string|max:500',
            'tema_grupo' => 'required|string|max:500',
            'problema' => 'nullable|string|max:2000',
            'objectivos' => 'nullable|string|max:2000',
            'estudo_caso' => 'nullable|string|max:2000',
        ]);

        $grupoPap->update($validated);

        return back()->with('success', 'Tema atualizado com sucesso.');
    }

    /**
     * Solicitar melhoria no tema PAP
     */
    public function solicitarMelhoria(
        Request $request,
        GrupoPap $grupoPap
    ) {
        // Verificar autorização
        /* $this->authorize(
             'solicitarMelhoriaTema',
             $grupoPap
         );*/

        // A recomendação é obrigatória
        $validated = $request->validate([
            'recomendacao' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ]);

        // Executar solicitação de melhoria
        $resultado = $this->service->solicitarMelhoria(
            $grupoPap,
            Auth::guard('tenant')->user(),
            $validated['recomendacao']
        );

        // Verificar se a operação foi realizada
        if (! $resultado) {
            return back()->withErrors([
                'grupo' => 'Não é possível solicitar melhoria neste momento.',
            ]);
        }

        return back()->with(
            'success',
            'Recomendação de melhoria enviada com sucesso.'
        );
    }

    /**
     * Reenviar tema PAP após correção solicitada.
     *
     * Esta ação é realizada pelo colégio.
     */
    public function reenviar(Request $request, GrupoPap $grupoPap)
    {
        if (! $grupoPap->podeSerReenviado()) {
            return back()->withErrors(['grupo' => 'Este tema não pode ser reenviado neste momento.']);
        }

        $validated = $request->validate([
            'nome_grupo' => 'required|string|max:500',
            'tema_grupo' => 'required|string|max:500',
            'problema' => 'nullable|string|max:2000',
            'objectivos' => 'nullable|string|max:2000',
        ]);

        $resultado = $this->service->reenviar($grupoPap, Auth::guard('tenant')->user(), $validated);

        if (! $resultado) {
            return back()->withErrors(['grupo' => 'Erro ao reenviar o tema.']);
        }

        $grupoPap->load('turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso');

        $turma = $grupoPap->turma;
        $cursoClasseTurno = $turma->cursoClasseTurno;
        $cursoClasse = $cursoClasseTurno->cursoClasse;
        $cursoTutelado = $cursoClasse->cursoTutelado;

        return redirect()
            ->route('tenant.dashboard.pap.show', [
                'instituicao' => $cursoTutelado->instituicaoCurso->instituicao_id,
                'cursoTutelado' => $cursoTutelado->id,
                'cursoClasse' => $cursoClasse->id,
                'cursoClasseTurno' => $cursoClasseTurno->id,
                'turma' => $turma->id,
                'grupoPap' => $grupoPap->id,
            ])
            ->with('success', 'Tema reenviado com sucesso.');
    }

    /**
     * Temas que precisam de melhoria
     *
     * Página do colégio
     */
    public function melhorias()
    {
        $user = Auth::guard('tenant')->user();

        $temas = GrupoPap::query()
            ->whereIn('status_aprovacao', [
                GrupoPap::APROVACAO_MELHORIA_TUTOR,
                GrupoPap::APROVACAO_MELHORIA_COORDENACAO,
            ])
            ->whereHas(
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
                function ($query) {
                    // Aqui deve ser aplicada a regra
                    // para garantir que o grupo pertence
                    // ao colégio do utilizador.
                }
            )
            ->with([
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
                'professor.user',
            ])
            ->latest()
            ->get();

        return inertia('tenant/pap/TemasMelhoria', [
            'temas' => $temas,

            'rotaEditar' => route(
                'grupo-pap-aprovacao.editar',
                ':id'
            ),
        ]);
    }

    /**
     * Formulário para corrigir tema
     */
    public function editar(GrupoPap $grupoPap)
    {
        /* $this->authorize(
             'editarTema',
             $grupoPap
         );*/

        $grupoPap->load([
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
            'professor.user',
        ]);

        return inertia('tenant/pap/EditarTemaMelhoria', [
            'grupoPap' => $grupoPap,

            'rotaAtualizar' => route(
                'grupo-pap-aprovacao.atualizar',
                ':id'
            ),

            'rotaReenviar' => route(
                'grupo-pap-aprovacao.reenviar',
                ':id'
            ),
        ]);
    }

    /**
     * Histórico de aprovação do grupo PAP
     */
    public function historico(GrupoPap $grupoPap)
    {
        /* $this->authorize(
             'verHistorico',
             $grupoPap
         );*/

        $grupoPap->load([
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
            'professor.user',
            'historicoAprovacao.utilizador',
        ]);

        return inertia('tenant/pap/HistoricoAprovacao', [
            'grupoPap' => $grupoPap,
            'historico' => $grupoPap->historicoAprovacao,
        ]);
    }
}
