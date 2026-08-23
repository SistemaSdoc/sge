<?php

namespace App\Http\Resources\Central\Tenant;

use App\Models\Tenant\Instituicao;
use App\Services\Central\TenantService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantEditResource extends JsonResource
{
    /**
     * Transforma o tenant para array de edição.
     *
     * @return array<string, mixed>
     */
    public function __construct($resource, private ?Instituicao $instituicao = null)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $tenantService = app(TenantService::class);

        return [
            'id' => $this->id,
            'domain' => $this->domains?->first()?->domain,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'nome' => $this->instituicao?->nome,
            'sigla' => $this->instituicao?->sigla,
            'tipo' => $this->instituicao?->tipo,
            'email' => $this->instituicao?->email,
            'instituicao_id' => $this->instituicao_id,
            'availableTransitions' => $tenantService->getAvailableStatusTransitions($this->resource),
        ];
    }
}
