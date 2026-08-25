<?php

namespace App\Models\Central;

use App\Enums\TenantStatus;
use App\Models\Tenant\Instituicao;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $casts = [
        'status' => TenantStatus::class,
        'provisioning_started_at' => 'datetime',
        'provisioning_finished_at' => 'datetime',
    ];

    public static function getCustomColumns(): array
    {
        return [
            ...parent::getCustomColumns(),
            'instituicao_id',
            'admin_user_id',
            'status',
            'provisioning_target_status',
            'provisioning_attempts',
            'provisioning_error',
            'provisioning_started_at',
            'provisioning_finished_at',
        ];
    }

    public function instituicao(): HasOne
    {
        return $this->hasOne(Instituicao::class, 'tenant_id', 'id');
    }

    public function pendingData(): HasOne
    {
        return $this->hasOne(PendingTenantData::class, 'tenant_id', 'id');
    }

    public function isActive(): bool
    {
        return $this->status === TenantStatus::ACTIVE;
    }

    public function canAccess(): bool
    {
        return $this->status->canAccess();
    }
}
