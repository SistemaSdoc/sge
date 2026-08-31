<?php

namespace App\Services\Tenant\Tutela\Data;

use App\Models\Central\Tenant;
use App\Models\Tenant\Instituicao;

/**
 * Dados validados da instituição tutora e do seu tenant.
 */
final readonly class InstituicaoTutoraData
{
    /**
     * Guarda a instituição e o tenant tutor depois da validação.
     */
    public function __construct(
        public Tenant $tenant,
        public Instituicao $instituicao,
    ) {}
}
