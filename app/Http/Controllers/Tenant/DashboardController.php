<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\User;
use App\Services\Tenant\Dashboards\DashboardAlunoService;
use App\Services\Tenant\Dashboards\DashboardDirectorService;
use App\Services\Tenant\Dashboards\DashboardProfessorService;
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
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $anoLectivoId = AnoLectivo::activo()?->id;

        if ($user->hasAnyRole(['SuperAdmin', 'Master', 'Director', 'Subdirector', 'Secretaria'])) {
            return $this->renderDirectorDashboard($user->instituicaoFiltro());
        }

        if ($user->hasRole('Professor')) {
            $professor = $user?->professor;

            return Inertia::render('tenant/dashboards/aluno/index', [
                'proximasAulas' => $this->dashboardProfessorService->obterProximasAulas($professor, 2, 6),
                'avisos' => $this->dashboardProfessorService->obterAvisos($professor, 6),
                'anoLectivoId' => $anoLectivoId,          // ← NOVO
                'anosLectivos' => AnoLectivo::all(),      // ← NOVO
            ]);
        }

        if ($user->hasRole('Aluno')) {
            $aluno = $user?->aluno;

            return Inertia::render('tenant/dashboards/aluno/index', [
                'proximasAulas' => $this->dashboardAlunoService->obterProximasAulas($aluno, 2, 6),
                'avisos' => $this->dashboardAlunoService->obterAvisos($aluno, 6),
                'anoLectivoId' => $anoLectivoId,          // ← NOVO
                'anosLectivos' => AnoLectivo::all(),      // ← NOVO

            ]);
        }

        return $this->renderDirectorDashboard($user->instituicaoFiltro());
    }

    /**
     * Renderiza a dashboard do diretor com os dados já consolidados.
     */
    private function renderDirectorDashboard(?string $instituicaoId): Response
    {
        return Inertia::render('tenant/dashboards/director/index', [
            'metricas' => $this->dashboardDirectorService->obterMetricas($instituicaoId),
            'accoes' => $this->dashboardDirectorService->obterAccoesPendentes($instituicaoId),
            'eventos' => $this->dashboardDirectorService->obterAvisos($instituicaoId),
        ]);
    }
}
