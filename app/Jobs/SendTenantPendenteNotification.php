<?php

namespace App\Jobs;

use App\Models\Central\PendingTenantData;
use App\Models\Central\Tenant;
use App\Notifications\TenantPendenteNotification;
use Illuminate\Support\Facades\Notification;

class SendTenantPendenteNotification
{
    public Tenant $tenant;

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public function handle(): void
    {
        $pending = PendingTenantData::where('tenant_id', $this->tenant->id)->first();

        if (!$pending)
            return;

        Notification::route('mail', [
            $pending->user_email => $pending->user_nome,
        ])->notify(new TenantPendenteNotification(
                    nomeInstituicao: $pending->nome,
                    nomeUser: $pending->user_nome,
                    subdomain: $this->tenant->id,
                ));
    }
}