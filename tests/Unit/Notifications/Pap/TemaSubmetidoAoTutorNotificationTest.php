<?php

use App\Models\Tenant\GrupoPap;
use App\Notifications\Pap\TemaSubmetidoAoTutorNotification;

test('resubmitting a corrected theme notifies the tutor by database and email', function (): void {
    $notification = new TemaSubmetidoAoTutorNotification(new GrupoPap);

    expect($notification->via(new stdClass))->toBe(['database', 'mail']);
});
