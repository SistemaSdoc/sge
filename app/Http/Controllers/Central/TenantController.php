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
     * Display a listing of the resource.
     */
    public function index()
    {
        $tenants = Tenant::with('domains')->paginate(10);

        $tenantsPagineted = TenantIndexResource::collection(
            $this->tenantService->getTenantsWithInstituicoes($tenants)
        );

        return Inertia::render('central/tenants/index', [
            'tenants' => $tenantsPagineted,
            'statuses' => $this->tenantService->getAvailableStatuses(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('central/tenants/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTenantRequest $request)
    {
        $validated = $request->validated();

        $tenant = $this->tenantService->createTenant($validated);

        $this->tenantService->createInstituicao($tenant, $validated);

        return redirect()->route('central.dashboard.tenants.index')
            ->with('success', 'Tenant criado com sucesso!');
    }

    /**
     * Display the specified resource.
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
     * Show the form for editing the specified resource.
     */
    public function edit(Tenant $tenant)
    {
        $instituicao = $this->tenantService->getInstituicao($tenant);

        return Inertia::render('central/tenants/edit', [
            'tenant' => $tenant->load('domains'),
            'instituicao' => $instituicao,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant)
    {
        $validated = $request->validated();

        $this->tenantService->updateInstituicao($tenant, $validated);

        $this->tenantService->updateTenant($tenant, $validated);

        return redirect()->route('central.dashboard.tenants.show', $tenant)->with('success', 'Tenant actualizado!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenant $tenant)
    {
        $this->tenantService->deleteTenant($tenant);

        return redirect()->route('central.dashboard.tenants.index')->with('success', 'Tenant eliminado!');
    }

    public function toggleStatus(Tenant $tenant)
    {
        $validated = request()->validate([
            'status' => ['required', 'string', 'in:active,trial,pending,suspended,inactive,archived'],
        ]);

        $tenant->update(['status' => $validated['status']]);

        return back()->with('success', 'Status alterado com sucesso!');
    }
}
