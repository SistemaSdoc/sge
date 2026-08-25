<?php

namespace App\Listeners;

use App\Models\Tenant\User;
use Illuminate\Auth\Events\Login;

class UpdateLastLoginAt
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            User::withoutEvents(function () use ($event) {
                User::where('id', $event->user->getAuthIdentifier())
                    ->update(['last_login_at' => now()]);
            });
        }
    }
}
