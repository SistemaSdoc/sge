<?php

namespace App\Http\Controllers\Central;

use App\Services\Central\DashboardService;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(DashboardService $dashboard)
    {
        return Inertia::render('central/dashboard/index', [
            'dashboard' => $dashboard->getData(),
        ]);
    }
}