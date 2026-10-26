<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.Tenant.User.{id}', function ($user, $id) {
    return $user->id === $id;
});
