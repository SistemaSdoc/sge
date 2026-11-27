<?php

namespace App\Notifications\Concerns;

trait ReliableNotification
{
    public int $tries = 3;

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }
}
