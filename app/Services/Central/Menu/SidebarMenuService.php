<?php

namespace App\Services\Central\Menu;

use App\Http\Controllers\Central\AnoLectivoController;
use App\Http\Controllers\Central\CursoController;
use App\Http\Controllers\Central\DisciplinaController;
use App\Http\Controllers\Central\TenantController;
use App\Http\Controllers\Central\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final class SidebarMenuService
{
    public function build(): array
    {
        $user = Auth::guard('web')->user();

        $gate = Gate::forUser($user);

        $groups = [

            new MenuGroup('Gestão de Clientes', [
                new MenuItem(
                    key: 'dashboard',
                    title: 'Dashboard',
                    href: route('central.dashboard'),
                    icon: 'LayoutGrid',
                    can: true,
                ),

                new MenuItem(
                    key: 'tenants',
                    title: 'Instituições',
                    href: action([TenantController::class, 'index']),
                    icon: 'Building2',
                    can: true,
                ),

                new MenuItem(
                    key: 'cursos',
                    title: 'Cursos',
                    href: action([CursoController::class, 'index']),
                    icon: 'BookOpen',
                    can: true,
                ),

                new MenuItem(
                    key: 'anos-lectivos',
                    title: 'Anos Lectivos',
                    href: action([AnoLectivoController::class, 'index']),
                    icon: 'CalendarClock',
                    can: true,
                ),

                 new MenuItem(
                    key: 'disciplinas',
                    title: 'Disciplinas',
                    href: action([DisciplinaController::class, 'index']),
                    icon: 'BookOpen',
                    can: true,
                ),

                new MenuItem(
                    key: 'calendario-anual',
                    title: 'Calendários',
                    href: route('central.dashboard.calendario-anual'),
                    icon: 'Calendar1',
                    can: true,
                ),
            ]),

            new MenuGroup('Gestão de Usuários', [
                new MenuItem(
                    key: 'users',
                    title: 'Usuários',
                    href: action([UserController::class, 'index']),
                    icon: 'Users',
                    can: true,
                ),
            ]),
        ];

        return array_values(array_filter(
            array_map(fn (MenuGroup $group) => $group->toArray(), $groups),
        ));
    }
}
