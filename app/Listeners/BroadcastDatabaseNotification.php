<?php

namespace App\Listeners;

use Illuminate\Notifications\Events\NotificationSent;

class BroadcastDatabaseNotification
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
    public function handle(NotificationSent $event): void
    {
        //
    }
}
