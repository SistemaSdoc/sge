<?php

namespace App\Services;

use App\Models\User;

class MenuService
{
    public function build(User $user): array
    {
        return $this->items();
    }

    private function items(): array
    {
        return [
            [
                'title' => 'Dashboard',
                'url' => '/dashboard',
                'icon' => 'LayoutGrid',
            ],
            [
                'title' => 'Instituição',
                'url' => '/dashboard/instituicoes/{instituicao_id}',
                'icon' => 'Building2',
            ],
            [
                'title' => 'Professores',
                'url' => '/dashboard/professores',
                'icon' => 'UserCheck',
            ],
            [
                'title' => 'Alunos',
                'url' => '/dashboard/alunos',
                'icon' => 'UserRound',
            ],
            [
                'title' => 'Turmas',
                'url' => '/dashboard/turmas',
                'icon' => 'UsersIcon',
            ],
            [
                'title' => 'Inscrições',
                'url' => '/dashboard/pap/inscricoes',
                'icon' => 'ClipboardList',
            ],
            [
                'title' => 'Grupos PAP',
                'url' => '/dashboard/pap/grupos',
                'icon' => 'BookOpen',
            ],
            [
                'title' => 'Pautas',
                'url' => '/dashboard/pautas',
                'icon' => 'Table',
            ],
            [
                'title' => 'Colégios',
                'url' => '/dashboard/colegios',
                'icon' => 'Building2',
            ],
            [
                'title' => 'Grelha Curricular',
                'url' => '/dashboard/grelha-curricular',
                'icon' => 'TableOfContents',
            ],
            [
                'title' => 'Minhas Notas',
                'url' => '/dashboard/minhas-notas',
                'icon' => 'BookUser',
            ],
            [
                'title' => 'Avisos',
                'url' => '/dashboard/avisos',
                'icon' => 'Bell',
            ],
        ];
    }
}
