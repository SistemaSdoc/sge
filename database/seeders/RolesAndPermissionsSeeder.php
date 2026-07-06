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
            'ver professores', 'criar professores', 'editar professores', 'eliminar professores',
            // Grupo PAP
            'ver grupopap', 'criar grupopap', 'editar grupopap', 'eliminar grupopap',
            'definir data defesa grupopap',
            // Relatórios
            'ver relatorios',
            // Financeiro
            'ver pagamentos', 'gerir pagamentos',
            // Sistema
            'gerir utilizadores', 'gerir permissoes',
            'ver avisos', 'criar avisos', 'editar avisos', 'eliminar avisos',
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
            'ver relatorios',
            'ver pagamentos', 'gerir pagamentos',
            'gerir utilizadores', 'gerir permissoes',
            'ver avisos', 'criar avisos', 'editar avisos', 'eliminar avisos',
              'ver professores', 'criar professores', 'editar professores', 'eliminar professores',
              'ver grupopap', 'criar grupopap', 'editar grupopap', 'eliminar grupopap',
                'definir data defesa grupopap'
        ]);

        // Subdirector — pedagógico, sem gerir utilizadores/sistema
        $subdirector = Role::create(['name' => 'Subdirector']);
        $subdirector->givePermissionTo([
            'ver alunos', 'editar alunos',
            'ver turmas', 'gerir turmas',
            'ver pautas',
            'ver relatorios',
            'ver avisos', 'criar avisos', 'editar avisos',
            'ver professores', 'criar professores', 'editar professores',
            'ver grupopap', 'criar grupopap', 'editar grupopap', 'eliminar grupopap',
            'definir data defesa grupopap',
        ]);

        // Secretaria — administrativo
        $secretaria = Role::create(['name' => 'Secretaria']);
        $secretaria->givePermissionTo([
            'ver alunos', 'criar alunos', 'editar alunos',
            'ver turmas',
            'ver pautas',
            'ver pagamentos', 'gerir pagamentos',
            'ver avisos', 'criar avisos', 'editar avisos',
            'ver professores', 'criar professores', 'editar professores',
            'ver grupopap', 'criar grupopap', 'editar grupopap', 'eliminar grupopap'
        ]);

        // Professor — as suas turmas
        $professor = Role::create(['name' => 'Professor']);
        $professor->givePermissionTo([
            'ver alunos',
            'ver turmas',
            'lançar notas', 'editar notas', 'ver pautas',
            'ver avisos', 'criar avisos', 'editar avisos',
            'ver grupopap', 'criar grupopap', 'editar grupopap'
        ]);

        // Aluno e Candidato — sem permissions de backoffice
        Role::create(['name' => 'Candidato']);
        
       $aluno= Role::create(['name' => 'Aluno']);
        $aluno->givePermissionTo([
           'ver avisos',
           'ver grupopap'
        ]);

        // Limpar cache depois
        app()[PermissionRegistrar::class]
            ->forgetCachedPermissions();
    }
}
