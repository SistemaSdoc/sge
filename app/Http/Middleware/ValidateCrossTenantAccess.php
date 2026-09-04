<?php

namespace App\Http\Middleware;

use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\User;
use App\Services\Tenant\CrossTenantAccessService;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateCrossTenantAccess
{
    public function __construct(private readonly CrossTenantAccessService $service) {}

    public function handle(Request $request, Closure $next, ...$params): Response
    {
        $colegioTenantId = $request->route('colegio_tenant_id')
            ?? $request->route('colegio')
            ?? $request->input('colegio_tenant_id');
        $cursoTuteladoSharedId = $request->route('curso_shared')
            ?? $request->input('curso_tutelado_shared_id');
        $tutor = $request->user('tenant');

        if (! $tutor instanceof User || ! filled($colegioTenantId) || ! filled($cursoTuteladoSharedId)) {
            if ($tutor instanceof User && $request->route('colegio') && $request->route('cursoTutelado')) {
                $colegioTenantId = Tenant::query()
                    ->where('instituicao_id', $request->route('colegio'))
                    ->value('id');
                $cursoTuteladoSharedId = CursoTuteladoShared::query()
                    ->where('tenant_tutor_id', tenancy()->tenant?->getTenantKey())
                    ->where('tenant_tutelado_id', $colegioTenantId)
                    ->where('curso_tutelado_tutelado_id', $request->route('cursoTutelado'))
                    ->where('status', 'activo')
                    ->value('id');
            }
        }

        if (! $tutor instanceof User || ! filled($colegioTenantId) || ! filled($cursoTuteladoSharedId)) {
            throw new AuthorizationException('Dados de acesso cross-tenant incompletos.');
        }

        $tenantColega = $this->service->validarAcessoDoTutorAoColega(
            $tutor,
            (string) $colegioTenantId,
            (string) $cursoTuteladoSharedId,
        );

        $request->attributes->set('cross_tenant_tutor', $tutor);
        $request->attributes->set(
            'cross_tenant_can_create_banca',
            $tutor->can('bancajuripap.create'),
        );
        $request->attributes->set(
            'cross_tenant_can_delete_banca',
            $tutor->can('bancajuripap.delete'),
        );
        $request->attributes->set(
            'cross_tenant_can_update_banca',
            $tutor->can('bancajuripap.update'),
        );
        $request->attributes->set(
            'cross_tenant_can_update_nota',
            $tutor->can('elementogrupopap.atualizarNota'),
        );

        return $tenantColega->run(fn (): Response => $next($request));
    }
}
