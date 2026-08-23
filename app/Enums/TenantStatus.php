<?php

namespace App\Enums;

enum TenantStatus: string
{
    case ACTIVE = 'active';
    case TRIAL = 'trial';
    case PENDING = 'pending';
    case SUSPENDED = 'suspended';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Activo',
            self::TRIAL => 'Período de Teste',
            self::PENDING => 'Pendente de Verificação',
            self::SUSPENDED => 'Suspenso',
            self::ARCHIVED => 'Arquivado',
        };
    }

    public function canAccess(): bool
    {
        return in_array($this, [self::ACTIVE, self::TRIAL, self::PENDING]);
    }

    public function isRestricted(): bool
    {
        return $this === self::TRIAL;
    }
}
