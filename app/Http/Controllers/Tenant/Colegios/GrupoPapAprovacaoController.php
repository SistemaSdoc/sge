<?php

namespace App\Http\Controllers\Tenant\Colegios;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\HistoricoAprovacaoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Services\AprovacaoTemaService;
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
    public function pendentes(Instituicao $instituicao)
    {
        $user = Auth::user();

        // O utilizador precisa estar associado a um professor
        if (! $user->professor) {
            return inertia('pap/PendentesAprovacao', [
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

        return inertia('pap/PendentesAprovacao', [
            'temasPendentes' => $temasPendentes,

            'rotaAprovar' => route('colegio.grupo-pap-aprovacao.aprovar', [
                'instituicao' => $instituicao->id,
                'grupoPap' => ':id',
            ]),
            'rotaReprovar' => route('colegio.grupo-pap-aprovacao.reprovar', [
                'instituicao' => $instituicao->id,
                'grupoPap' => ':id',
            ]),
            'rotaMelhoria' => route('colegio.grupo-pap-aprovacao.solicitar-melhoria', [
                'instituicao' => $instituicao->id,
                'grupoPap' => ':id',
            ]),
        ]);

    }

    /**
     * Aprovar tema PAP
     */
    public function aprovar(
        Request $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
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
            Auth::user(),
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


    public function aprovarTutor(
        Request $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
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
                'utilizador_id' => Auth::id(),
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

    public function solicitarMelhoriaComoTutor(
        Request $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('solicitarMelhoriaComoTutor', $grupoPap);

        $validated = $request->validate([
            'recomendacao' => 'required|string|min:10|max:2000',
        ]);

        DB::transaction(function () use ($grupoPap, $validated) {
            $grupoPap->update([
                'status_aprovacao' => GrupoPap::APROVACAO_MELHORIA,
                'comentario_aprovacao' => $validated['recomendacao'],
            ]);

            HistoricoAprovacaoPap::create([
                'grupo_pap_id' => $grupoPap->id,
                'utilizador_id' => Auth::id(),
                'estado_anterior' => GrupoPap::APROVACAO_SUBMETIDO,
                'estado_novo' => GrupoPap::APROVACAO_MELHORIA,
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
     * Reprovar tema PAP
     */
    public function reprovar(
        Request $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
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
            Auth::user(),
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
    public function atualizar(
        Request $request,
        GrupoPap $grupoPap
    ) {
        // Verificar autorização se necessário
        // $this->authorize('editarTema', $grupoPap);

        // Validar dados (ajusta conforme necessário)
        $validated = $request->validate([
            'tema' => 'required|string|max:500',
            'descricao' => 'nullable|string|max:2000',
            // ... outros campos ...
        ]);

        // Atualizar o grupo PAP
        $grupoPap->update($validated);

        return back()->with(
            'success',
            'Tema atualizado com sucesso.'
        );
    }

    /**
     * Solicitar melhoria no tema PAP
     */
    public function solicitarMelhoria(
        Request $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
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
            Auth::user(),
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
    public function reenviar(
        Request $request,
        GrupoPap $grupoPap
    ) {
        // $this->authorize('reenviarTema', $grupoPap);

        $dados = $request->validate([
            'nome_grupo' => ['required', 'string', 'max:255'],
            'tema_grupo' => ['required', 'string', 'max:255'],
        ]);

        $resultado = $this->service->reenviar(
            $grupoPap,
            Auth::user(),
            $dados
        );

        if (! $resultado) {
            return back()->withErrors([
                'grupo' => 'Este tema não pode ser reenviado neste momento.',
            ]);
        }

        return back()->with('success', 'Tema corrigido e reenviado para nova análise com sucesso.');
    }

    /**
     * Temas que precisam de melhoria
     *
     * Página do colégio
     */
    public function melhorias(Instituicao $instituicao)
    {
        $user = Auth::user();

        $temas = GrupoPap::query()
            ->where('status_aprovacao', 'melhoria-solicitada')
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

        return inertia('pap/TemasMelhoria', [
            'temas' => $temas,

            'rotaEditar' => route('colegio.grupo-pap-aprovacao.editar', [
                'instituicao' => $instituicao->id,
                'grupoPap' => ':id',
            ]),
        ]);
    }

    /**
     * Formulário para corrigir tema
     */
    public function editar(GrupoPap $grupoPap, Instituicao $instituicao)
    {
        /* $this->authorize(
             'editarTema',
             $grupoPap
         );*/

        $grupoPap->load([
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
            'professor.user',
        ]);

        return inertia('pap/EditarTemaMelhoria', [
            'grupoPap' => $grupoPap,

            'rotaAtualizar' => route('colegio.grupo-pap-aprovacao.atualizar', [
                'instituicao' => $instituicao->id,
                'grupoPap' => $grupoPap->id,
            ]),
            'rotaReenviar' => route('colegio.grupo-pap-aprovacao.reenviar', [
                'instituicao' => $instituicao->id,
                'grupoPap' => $grupoPap->id,
            ]),
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

        return inertia('pap/HistoricoAprovacao', [
            'grupoPap' => $grupoPap,
            'historico' => $grupoPap->historicoAprovacao,
        ]);
    }
}
