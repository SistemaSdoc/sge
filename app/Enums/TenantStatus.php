<?php

namespace App\Enums;

enum TenantStatus: string
{
    case ACTIVE = 'active';
    case TRIAL = 'trial';
    case PENDING = 'pending';
    case PROVISIONING = 'provisioning';
    case FAILED = 'failed';
    case SUSPENDED = 'suspended';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Activo',
            self::TRIAL => 'Período de Teste',
            self::PENDING => 'Pendente de Verificação',
            self::PROVISIONING => 'A configurar',
            self::FAILED => 'Falha na configuração',
            self::SUSPENDED => 'Suspenso',
            self::ARCHIVED => 'Arquivado',
        };
    }

    public function canAccess(): bool
    {
        return in_array($this, [self::ACTIVE, self::TRIAL]);
    }

    public function isRestricted(): bool
    {
        return $this === self::TRIAL;
    }

    public function isProvisioning(): bool
    {
        return $this === self::PROVISIONING;
    }
}
