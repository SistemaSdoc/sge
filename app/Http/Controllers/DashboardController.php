<?php

namespace App\Http\Controllers;

use App\Models\Aluno;
use App\Models\Aviso;
use App\Models\GrupoPap;
use App\Models\Inscricao;
use App\Models\Professor;
use App\Models\Turma;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function metrics()
    {
        $instituicaoId = $this->instituicaoId();

        $alunosAtivos = Aluno::where('situacao', 'activo')
            ->whereHas('inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso', function ($query) use ($instituicaoId) {
                if ($instituicaoId) {
                    $query->where('instituicao_id', $instituicaoId);
                }
            })
            ->count();

        $turmasAbertas = Turma::whereHas('cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso', function ($query) use ($instituicaoId) {
            if ($instituicaoId) {
                $query->where('instituicao_id', $instituicaoId);
            }
        })
            ->count();

        $inscricoesPendentes = Inscricao::where('status', 'pendente')
            ->whereHas('cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso', function ($query) use ($instituicaoId) {
                if ($instituicaoId) {
                    $query->where('instituicao_id', $instituicaoId);
                }
            })
            ->count();

        $professoresAtivos = Professor::whereHas('user', function ($query) use ($instituicaoId) {
            if ($instituicaoId) {
                $query->where('instituicao_id', $instituicaoId);
            }
        })
            ->count();

        return response()->json([
            'alunosAtivos' => $alunosAtivos,
            'turmasAbertas' => $turmasAbertas,
            'inscricoesPendentes' => $inscricoesPendentes,
            'professoresAtivos' => $professoresAtivos,
        ]);
    }

    public function actions()
    {
        $instituicaoId = $this->instituicaoId();

        $pendentes = Inscricao::where('status', 'pendente')
            ->whereHas('cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso', function ($query) use ($instituicaoId) {
                if ($instituicaoId) {
                    $query->where('instituicao_id', $instituicaoId);
                }
            })
            ->count();

        $turmasSemProfessor = Turma::whereHas('cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso', function ($query) use ($instituicaoId) {
            if ($instituicaoId) {
                $query->where('instituicao_id', $instituicaoId);
            }
        })
            ->whereDoesntHave('turmaDisciplinaProfessor')
            ->count();

        $gruposSemBanca = GrupoPap::whereHas('turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso', function ($query) use ($instituicaoId) {
            if ($instituicaoId) {
                $query->where('instituicao_id', $instituicaoId);
            }
        })
            ->whereDoesntHave('jurados')
            ->count();

        return response()->json([
            [
                'id' => 'pending-inscricoes',
                'type' => 'inscricoes',
                'title' => 'Inscrições Pendentes',
                'description' => 'Inscrições de novos alunos aguardando aprovação',
                'count' => $pendentes,
                'severity' => 'critical',
                'icon' => 'user-check',
                'href' => '/dashboard/inscricoes?status=pendente',
            ],
            [
                'id' => 'turmas-sem-professor',
                'type' => 'turmas',
                'title' => 'Turmas sem Professor',
                'description' => 'Turmas que ainda não têm docente atribuído',
                'count' => $turmasSemProfessor,
                'severity' => 'warning',
                'icon' => 'users',
                'href' => '/dashboard/turmas?sem_professor=true',
            ],
            [
                'id' => 'pap-sem-banca',
                'type' => 'pap',
                'title' => 'Grupos PAP sem Banca',
                'description' => 'Grupos de Projeto de Aptidão Profissional sem banca marcada',
                'count' => $gruposSemBanca,
                'severity' => 'attention',
                'icon' => 'briefcase',
                'href' => '/dashboard/pap?sem_banca=true',
            ],
        ]);
    }

    public function events()
    {
        $instituicaoId = $this->instituicaoId();
        $today = Carbon::today();

        // Avisos ativos
        $avisos = Aviso::where('ativo', true)
            ->when(
                $instituicaoId,
                fn ($q) => $q->where('instituicao_id', $instituicaoId)
            )
            ->orderByRaw("FIELD(tipo, 'urgente', 'evento', 'aviso')")
            ->orderBy('data', 'asc')
            ->take(5)
            ->get()
            ->map(fn (Aviso $aviso) => [
                'id' => "aviso-{$aviso->id}",
                'date' => $aviso->data?->valueOf() ?? now()->valueOf(),
                'title' => $aviso->titulo,
                'type' => $aviso->tipo,
            ]);

        // Eventos de defesa de PAP
        $eventos = GrupoPap::whereHas('turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso', function ($query) use ($instituicaoId) {
            if ($instituicaoId) {
                $query->where('instituicao_id', $instituicaoId);
            }
        })
            ->whereNotNull('data_defesa')
            ->whereDate('data_defesa', '>=', $today)
            ->orderBy('data_defesa')
            ->take(5)
            ->get()
            ->map(fn (GrupoPap $grupo) => [
                'id' => "pap-{$grupo->id}",
                'date' => $grupo->data_defesa?->valueOf(),
                'title' => "Banca de Defesa - {$grupo->nome_grupo}",
                'type' => 'banca',
            ]);

        // Combinar e ordenar por data
        $combined = $avisos->concat($eventos)->sortBy('date')->values();

        return response()->json($combined);
    }

    protected function instituicaoId(): ?string
    {
        return auth()->user()?->instituicaoFiltro();
    }
}