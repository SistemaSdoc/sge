<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CursoTuteladoSecretarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $manageSecretaries = Permission::findOrCreate('curso.secretarios.manage', 'tenant');
        $manageHistory = Permission::findOrCreate('historico.manage', 'tenant');
        $secretaryPermissions = [
            $manageHistory,
            Permission::findOrCreate('curso-tutelado.viewAny', 'tenant'),
            Permission::findOrCreate('curso-tutelado.view', 'tenant'),
            Permission::findOrCreate('cursoclasse.viewAny', 'tenant'),
            Permission::findOrCreate('cursoclasse.view', 'tenant'),
            Permission::findOrCreate('cursoclasseturno.viewAny', 'tenant'),
            Permission::findOrCreate('cursoclasseturno.view', 'tenant'),
            Permission::findOrCreate('turmas.viewAny', 'tenant'),
            Permission::findOrCreate('turmas.view', 'tenant'),
            Permission::findOrCreate('classeturnodisciplina.viewAny', 'tenant'),
            Permission::findOrCreate('classeturnodisciplina.view', 'tenant'),
            Permission::findOrCreate('inscricoes.create', 'tenant'),
            Permission::findOrCreate('inscricoes.viewAny', 'tenant'),
            Permission::findOrCreate('inscricoes.view', 'tenant'),
            Permission::findOrCreate('alunos.viewAny', 'tenant'),
            Permission::findOrCreate('alunos.view', 'tenant'),
            Permission::findOrCreate('pautas.viewAny', 'tenant'),
            Permission::findOrCreate('pautas.view', 'tenant'),
        ];

        Role::findOrCreate('Secretario do Curso', 'tenant')->syncPermissions($secretaryPermissions);

        Role::findOrCreate('Coordenador', 'tenant')->givePermissionTo($manageSecretaries);
        Role::findOrCreate('Coordenador', 'tenant')->givePermissionTo($manageHistory);

        foreach (['Director', 'Subdirector', 'Secretaria'] as $roleName) {
            Role::findOrCreate($roleName, 'tenant')->revokePermissionTo($manageSecretaries);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
