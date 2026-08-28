<?php

namespace App\Helpers;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

class CentralDatabase
{
    public static function connection(): Connection
    {
        return DB::connection(config('tenancy.database.central_connection', config('database.default')));
    }
}
