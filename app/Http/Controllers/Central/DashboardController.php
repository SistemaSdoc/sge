<?php

namespace App\Http\Controllers\Central;

use App\Enums\TenantStatus;
use App\Models\Central\Tenant;
use App\Models\Central\User;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the dashboard view.
     */
    public function index()
    {
        $tenants = Tenant::with('domains')
            ->latest()
            ->limit(5)
            ->get();

        return Inertia::render('central/dashboard/index', [
            'metricas' => [
                'totalInstituicoes' => Tenant::count(),
                'instituicoesActivas' => Tenant::where('status', TenantStatus::ACTIVE)->count(),
                'pendentes' => Tenant::where('status', TenantStatus::PENDING)->count(),
                'totalUtilizadores' => User::count(),
            ],
            'tenants' => $tenants->map(fn ($t) => [
                'id' => $t->id,
                'nome' => $t->id,
                'tipo' => '—',
                'estado' => $t->status->value,
                'estadoLabel' => $t->status->label(),
                'criadoEm' => $t->created_at?->format('d/m/Y'),
            ]),
        ]);
    }
}
