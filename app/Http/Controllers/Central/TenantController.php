<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Tenant\StoreTenantRequest;
use App\Http\Requests\Central\Tenant\UpdateTenantRequest;
use App\Http\Resources\Central\Tenant\TenantIndexResource;
use App\Models\Central\Tenant;
use App\Services\Central\TenantMetricsService;
use App\Services\Central\TenantService;
use Inertia\Inertia;

class TenantController extends Controller
{
    public function __construct(
        private TenantService $tenantService
    ) {}

    /**
     * Lista todos os tenants paginados com instituições.
     */
    public function index()
    {
        $tenants = Tenant::with('domains')->paginate(10);

        $tenantsPagineted = TenantIndexResource::collection(
            $this->tenantService->getTenantsWithInstituicoes($tenants)
        );

        return Inertia::render('central/tenants/index', [
            'tenants' => $tenantsPagineted,
        ]);
    }

    /**
     * Exibe formulário de criação de tenant.
     */
    public function create()
    {
        return Inertia::render('central/tenants/create');
    }

    /**
     * Cria um novo tenant com dados da instituição.
     */
    public function store(StoreTenantRequest $request)
    {
        $validated = $request->validated();

        $tenant = $this->tenantService->createTenant($validated);
        $this->tenantService->savePendingTenantData($tenant, $validated);

        return redirect()->route('central.dashboard.tenants.index');
    }

    /**
     * Exibe detalhes de um tenant específico.
     */
    public function show(Tenant $tenant)
    {
        $tenant->load('domains');
        $instituicao = $this->tenantService->getInstituicao($tenant);
        $adminUser = $this->tenantService->getTenantAdminUser($tenant);

        // Executar dentro do contexto do tenant
        $metrics = $tenant->run(function () {
            $metricsService = new TenantMetricsService;

            return $metricsService->getMetrics();
        });

        return Inertia::render('central/tenants/show', [
            'tenant' => [
                'id' => $tenant->id,
                'status' => $tenant->status,
                'domain' => $tenant->domains?->first()?->domain,
                'instituicao' => [
                    'nome' => $instituicao?->nome,
                    'sigla' => $instituicao?->sigla,
                    'tipo' => $instituicao?->tipo,
                    'user' => [
                        'nome' => $adminUser?->nome,
                        'email' => $adminUser?->email,
                    ],
                ],
            ],
            'metrics' => $metrics,
        ]);
    }

    /**
     * Exibe formulário de edição de tenant.
     */
    public function edit(Tenant $tenant)
    {
        $tenant->load('domains');
        $instituicao = $this->tenantService->getInstituicao($tenant);
        $adminUser = $this->tenantService->getTenantAdminUser($tenant);

        return Inertia::render('central/tenants/edit', [
            'tenant' => [
                'id' => $tenant->id,
                'domain' => $tenant->domains?->first()?->domain,
                'nome' => $instituicao?->nome,
                'sigla' => $instituicao?->sigla,
                'tipo' => $instituicao?->tipo,
                'user_nome' => $adminUser?->nome,
                'user_email' => $adminUser?->email,
            ],
        ]);
    }

    /**
     * Actualiza tenant e instituição.
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant)
    {
        $validated = $request->validated();

        $this->tenantService->updateInstituicao($tenant, $validated);
        $this->tenantService->updateTenant($tenant, $validated);

        return redirect()->route('central.dashboard.tenants.show', $tenant)
            ->with('success', 'Tenant actualizado!');
    }

    /**
     * Elimina um tenant.
     */
    public function destroy(Tenant $tenant)
    {
        $this->tenantService->deleteTenant($tenant);

        return redirect()->route('central.dashboard.tenants.index')
            ->with('success', 'Tenant eliminado!');
    }

    /**
     * Altera o status de um tenant e inicia o progresso.
     */
    public function toggleStatus(Tenant $tenant)
    {
        $validated = request()->validate([
            'status' => ['required', 'string', 'in:active,trial,suspended,archived'],
        ]);

        try {
            $this->tenantService->transitionStatus($tenant, $validated['status']);

            return back()->with('success', 'Tenant a ser ativado...');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao alterar status: '.$e->getMessage());
        }
    }

    /**
     * Exibe página com a lista de todas as tabelas da base de dados do tenant
     *
     * Ordenadas por tamanho.
     */
    public function showTablesSize(Tenant $tenant)
    {
        $tenant->load('domains');
        $instituicao = $this->tenantService->getInstituicao($tenant);
        $adminUser = $this->tenantService->getTenantAdminUser($tenant);

        $metrics = $tenant->run(function () {
            $metricsService = new TenantMetricsService;

            return $metricsService->getAllTablesBySize();
        });

        return Inertia::render('central/tenants/database/details/table-size-details', [
            'tenant' => [
                'id' => $tenant->id,
                'status' => $tenant->status,
                'domain' => $tenant->domains?->first()?->domain,
                'instituicao' => [
                    'nome' => $instituicao?->nome,
                    'sigla' => $instituicao?->sigla,
                    'tipo' => $instituicao?->tipo,
                    'user' => [
                        'nome' => $adminUser?->nome,
                        'email' => $adminUser?->email,
                    ],
                ],
            ],
            'metrics' => $metrics,
        ]);
    }

    /**
     * Exibe página com a lista de todas as tabelas da base de dados do tenant
     *
     * Ordenadas por número de registos.
     */
    public function showTablesRecords(Tenant $tenant)
    {
        $tenant->load('domains');
        $instituicao = $this->tenantService->getInstituicao($tenant);
        $adminUser = $this->tenantService->getTenantAdminUser($tenant);

        $metrics = $tenant->run(function () {
            $metricsService = new TenantMetricsService;

            return $metricsService->getAllTablesByRecords();
        });

        return Inertia::render('central/tenants/database/details/table-records-details', [
            'tenant' => [
                'id' => $tenant->id,
                'status' => $tenant->status,
                'domain' => $tenant->domains?->first()?->domain,
                'instituicao' => [
                    'nome' => $instituicao?->nome,
                    'sigla' => $instituicao?->sigla,
                    'tipo' => $instituicao?->tipo,
                    'user' => [
                        'nome' => $adminUser?->nome,
                        'email' => $adminUser?->email,
                    ],
                ],
            ],
            'metrics' => $metrics,
        ]);
    }
}
