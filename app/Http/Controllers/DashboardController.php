<?php

namespace App\Http\Controllers;

use App\Models\Professor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Renderiza o dashboard do usuário autenticado.
     *
     * Este método decide a experiência correta do dashboard no servidor
     * com base na função do usuário, evitando a troca de funções apenas no frontend.
     */
    public function index(): Response|RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Exemplo.
        if ($user->hasRole('Director')) {
            return Inertia::render('dashboards/director', [
                'data' => 'Director Logado',
                'dashboardHeading' => 'Painel de Diretor — Experimento',
                'dashboardMessage' => 'Este é um teste de renderização de página específica para o Director.',
            ]);
        }

        if ($user->hasRole('Professor')) {
            return Inertia::render('dashboards/professor', [
                'data' => 'Professor Logado',
            ]);
        }

        // TODO: add other roles as needed.
        // if ($user->hasRole('Professor')) {
        //     return Inertia::render('dashboard/professor', [
        //         'dashboardType' => 'professor',
        //         'dashboardHeading' => 'Painel de Professor',
        //     ]);
        // }
        // if ($user->hasRole('Secretaria')) {
        //     return Inertia::render('dashboard/secretaria', [
        //         'dashboardType' => 'secretaria',
        //         'dashboardHeading' => 'Painel de Secretaria',
        //     ]);
        // }
        // if ($user->hasRole('Master')) {
        //     return Inertia::render('dashboard/master', [
        //         'dashboardType' => 'master',
        //     ]);
        // }

        // Fallback for any other staff role that belongs here.
        return Inertia::render('dashboard', [
            'dashboardType' => 'staff',
            'dashboardHeading' => 'Painel de Staff',
            'dashboardMessage' => 'Você está no dashboard da equipa, mas o role ainda não tem um dashboard personalizado.',
        ]);
    }
}