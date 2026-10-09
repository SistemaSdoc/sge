<?php

use Illuminate\Support\Facades\Broadcast;

it('registers the tenant user broadcast channel used by notifications', function () {
    $channels = Broadcast::getChannels();

    expect($channels)
        ->toHaveKey('App.Models.Tenant.User.{id}')
        ->not->toHaveKey('App.Models.User.{id}');
});
