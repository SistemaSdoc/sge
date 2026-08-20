<?php

namespace App\Http\Controllers\Central;

use Illuminate\Routing\Controller;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Renderiza o dashboard central (para gestão dos tenants).
     */
    public function index()
    {
        return Inertia::render('central/dashboard', [
            'data' => 'bem vindo user!!! este é o dashboard central',
        ]);
    }
}
