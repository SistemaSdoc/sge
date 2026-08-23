<?php

namespace App\Services\Central\Menu;

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
            array_map(fn(MenuGroup $group) => $group->toArray(), $groups),
        ));
    }
}
