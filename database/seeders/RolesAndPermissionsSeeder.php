<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpar cache
        app()[PermissionRegistrar::class]
            ->forgetCachedPermissions();

        // Criar permissions
        $permissions = [
            // Alunos
            'ver alunos', 'criar alunos', 'editar alunos', 'eliminar alunos',
            // Turmas
            'ver turmas', 'gerir turmas',
            // Notas e Pautas
            'lançar notas', 'editar notas', 'ver pautas',
            // Professores
            'ver professores', 'gerir professores',
            // Relatórios
            'ver relatorios',
            // Financeiro
            'ver pagamentos', 'gerir pagamentos',
            // Sistema
            'gerir utilizadores', 'gerir permissoes',
        ];

        foreach ($permissions as $perm) {
            Permission::create(['name' => $perm]);
        }

        // SuperAdmin — gerido via Gate::before, sem permissions directas
        Role::create(['name' => 'SuperAdmin']);

        // Director — gestão total da sua instituição
        $director = Role::create(['name' => 'Director']);
        $director->givePermissionTo([
            'ver alunos', 'criar alunos', 'editar alunos', 'eliminar alunos',
            'ver turmas', 'gerir turmas',
            'ver pautas',
            'ver professores', 'gerir professores',
            'ver relatorios',
            'ver pagamentos', 'gerir pagamentos',
            'gerir utilizadores', 'gerir permissoes',
        ]);

        // Subdirector — pedagógico, sem gerir utilizadores/sistema
        $subdirector = Role::create(['name' => 'Subdirector']);
        $subdirector->givePermissionTo([
            'ver alunos', 'editar alunos',
            'ver turmas', 'gerir turmas',
            'ver pautas',
            'ver professores',
            'ver relatorios',
        ]);

        // Secretaria — administrativo
        $secretaria = Role::create(['name' => 'Secretaria']);
        $secretaria->givePermissionTo([
            'ver alunos', 'criar alunos', 'editar alunos',
            'ver turmas',
            'ver pautas',
            'ver professores',
            'ver pagamentos', 'gerir pagamentos',
        ]);

        // Professor — as suas turmas
        $professor = Role::create(['name' => 'Professor']);
        $professor->givePermissionTo([
            'ver alunos',
            'ver turmas',
            'lançar notas', 'editar notas', 'ver pautas',
        ]);

        // Aluno e Candidato — sem permissions de backoffice
        Role::create(['name' => 'Aluno']);
        Role::create(['name' => 'Candidato']);

        // Limpar cache depois
        app()[PermissionRegistrar::class]
            ->forgetCachedPermissions();
    }
}
