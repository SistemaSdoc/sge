<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Tenant\StoreTenantRequest;
use App\Http\Requests\Central\Tenant\UpdateTenantRequest;
use App\Http\Resources\Central\Tenant\TenantIndexResource;
use App\Models\Central\Tenant;
use App\Services\Central\TenantService;
use Inertia\Inertia;

class TenantController extends Controller
{
    public function __construct(private TenantService $tenantService) {}

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
        $instituicao = $this->tenantService->getInstituicao($tenant);

        return Inertia::render('central/tenants/show', [
            'tenant' => $tenant->load('domains'),
            'instituicao' => $instituicao,
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

        return redirect()->route('central.dashboard.tenants.show', $tenant)->with('success', 'Tenant actualizado!');
    }

    /**
     * Elimina um tenant.
     */
    public function destroy(Tenant $tenant)
    {
        $this->tenantService->deleteTenant($tenant);

        return redirect()->route('central.dashboard.tenants.index')->with('success', 'Tenant eliminado!');
    }

    /**
     * Altera o status de um tenant e inicia o progresso
     */
    public function toggleStatus(Tenant $tenant)
    {
        $validated = request()->validate([
            'status' => ['required', 'string', 'in:active,trial,suspended,archived'],
        ]);

        try {
            // Se está a ativar (mudar para active ou trial), inicializa o progresso
            if ($validated['status'] === 'active' || $validated['status'] === 'trial') {
                cache()->put(
                    "progresso_tenant_{$tenant->id}",
                    [
                        'etapa' => 'iniciando',
                        'mensagem' => 'A iniciar criação do tenant...',
                        'percentagem' => 0,
                        'status' => 'em_progresso',
                    ],
                    now()->addHour()
                );
            }

            // Dispara a transição de status
            $this->tenantService->transitionStatus($tenant, $validated['status']);

            return back()->with('success', 'Tenant a ser ativado...');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao alterar status: ' . $e->getMessage());
        }
    }

    /**
     * Stream de status do progresso do tenant (SSE)
     */
    public function statusStream(Tenant $tenant)
    {
        return response()->stream(
            function () use ($tenant) {
                $tempoMaximo = 300; // 5 minutos
                $tempoInicio = time();

                // Loop que fica aberto enquanto o tenant está a ser criado
                while (time() - $tempoInicio < $tempoMaximo) {
                    // Lê o progresso do cache
                    $progresso = cache()->get("progresso_tenant_{$tenant->id}");

                    if ($progresso) {
                        // Envia para o frontend em formato SSE
                        echo "data: " . json_encode($progresso) . "\n\n";

                        // Se terminou (com sucesso ou erro), fecha a ligação
                        if ($progresso['status'] === 'concluido' || $progresso['status'] === 'erro') {
                            break;
                        }
                    }

                    ob_flush();
                    flush();
                    sleep(0.5); // Aguarda 500ms antes de verificar de novo
                }
            },
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }
}
