<?php

namespace App\Services\Dashboards;

use App\Models\Aluno;
use App\Models\Aviso;
use App\Models\GrupoPap;
use App\Models\Inscricao;
use App\Models\Professor;
use App\Models\Turma;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class DashboardDirectorService
{
    /**
     * Obter métricas gerais do dashboard do diretor/admin
     */
    public function obterMetricas(?string $instituicaoId = null): array
    {
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

        return [
            'totalAlunos' => $alunosAtivos,
            'totalTurmas' => $turmasAbertas,
            'inscricoesPendentes' => $inscricoesPendentes,
            'totalProfessores' => $professoresAtivos,
            'alunosAtivos' => $alunosAtivos,
            'turmasAbertas' => $turmasAbertas,
            'professoresAtivos' => $professoresAtivos,
        ];
    }

    /**
     * Obter ações pendentes (inscrições, turmas sem professor, PAP sem banca)
     */
    public function obterAccoesPendentes(?string $instituicaoId = null): array
    {
        $pendentes = Inscricao::where('status', 'pendente')
            ->whereHas('cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso', function ($query) use ($instituicaoId) {
                if ($instituicaoId) {
                    $query->where('instituicao_id', $instituicaoId);
                }
            })->count();

        $turmasSemProfessor = Turma::whereHas('cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso', function ($query) use ($instituicaoId) {
            if ($instituicaoId) {
                $query->where('instituicao_id', $instituicaoId);
            }
        })->whereDoesntHave('turmaDisciplinaProfessor')->count();

        $gruposSemBanca = GrupoPap::whereHas('turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso', function ($query) use ($instituicaoId) {
            if ($instituicaoId) {
                $query->where('instituicao_id', $instituicaoId);
            }
        })->whereDoesntHave('jurados')->count();

        return [
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
        ];
    }

    /**
     * Obter próximos eventos (bancas de defesa)
     */
    public function obterEventos(?string $instituicaoId = null): array
    {
        $today = Carbon::today();

        return GrupoPap::whereHas('turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso', function ($query) use ($instituicaoId) {
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
            ])
            ->toArray();
    }

    /**
     * Obter avisos/notificações (será implementado quando tabela existir)
     */
    public function obterAvisos(?string $instituicaoId = null, ?int $limite = 10): Collection
    {
        return Aviso::where('ativo', true)
            ->where(function ($query) use ($instituicaoId) {
                if ($instituicaoId) {
                    $query->where('instituicao_id', $instituicaoId);
                }
            })
            ->where(function ($query) {
                $query->whereNull('data')
                    ->orWhereDate('data', '>=', now());
            })
            ->orderBy('data')
            ->take($limite)
            ->get();
    }
}
