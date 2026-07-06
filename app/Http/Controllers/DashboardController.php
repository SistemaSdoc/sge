<?php

namespace App\Http\Controllers;

use App\Services\Dashboards\DashboardAlunoService;
use App\Services\Dashboards\DashboardDirectorService;
use App\Services\Dashboards\DashboardProfessorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardDirectorService $dashboardDirectorService,
        private DashboardProfessorService $dashboardProfessorService,
        private DashboardAlunoService $dashboardAlunoService,
    ) {}

    /**
     * Renderiza o dashboard do usuário autenticado.
     *
     * Este método decide a experiência correta do dashboard no servidor
     * com base na função do usuário, evitando a troca de funções apenas no frontend.
     */
    public function index(): Response|RedirectResponse
    {
        $user = Auth::user();

        if ($user->hasRole('Director')) {
            return Inertia::render('dashboards/director/index', [
                'metricas' => $this->dashboardDirectorService->obterMetricas($user->instituicao_id),
                'accoes' => $this->dashboardDirectorService->obterAccoesPendentes($user->instituicao_id),
                'eventos' => $this->dashboardDirectorService->obterAvisos($user->instituicao_id),
            ]);
        }

        if ($user->hasRole('Professor')) {
           $professor = $user?->professor;

            return Inertia::render('dashboards/professor/index', [
                'proximasAulas' => $this->dashboardProfessorService->obterProximasAulas($professor),
                'avisos' => $this->dashboardProfessorService->obterAvisos($professor),
            ]);
        }

        if ($user->hasRole('Aluno')) {
            $aluno = $user?->aluno;

            return Inertia::render('dashboards/aluno/index', [
                'proximasAulas' => $this->dashboardAlunoService->obterProximasAulas($aluno, 2, 6),
                'avisos' => $this->dashboardAlunoService->obterAvisos($aluno, 6),
            ]);
        }

        // Fallback for any other staff role that belongs here.
        return Inertia::render('dashboard');
    }
}
