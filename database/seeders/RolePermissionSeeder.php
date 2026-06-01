<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Master — todas as permissões
        $master = Role::firstOrCreate(['nome' => 'Master']);
        $this->syncPermissions($master, Permission::all()->pluck('id'));

        // Director — tudo excepto gerir roles/permissões e logs
        $director = Role::firstOrCreate(['nome' => 'Director']);
        $this->syncPermissions($director,
            Permission::whereNotIn('slug', [
                'permissions.manage',
                'dashboard.aluno.notas',
                'dashboard.aluno.grelha',
                'instituicoes.index', 'instituicoes.create', 'instituicoes.delete',
                'roles.manage',
                'logs.view',
            ])->pluck('id')
        );

        // Secretaria — operações do dia-a-dia, sem apagar nem configurações da instituição
        $secretaria = Role::firstOrCreate(['nome' => 'Secretaria']);
        $this->syncPermissions($secretaria,
            Permission::whereIn('slug', [
                'alunos.index', 'alunos.show', 'alunos.create', 'alunos.edit',
                'turmas.index', 'turmas.show', 'turmas.create', 'turmas.edit',
                'cursos.index', 'cursos.show', 'cursos.create', 'cursos.edit',
                'professores.index', 'professores.show', 'professores.create', 'professores.edit',
                'disciplinas.index', 'disciplinas.show', 'disciplinas.create', 'disciplinas.edit',
                'instituicoes.show',
                'pap.index', 'pap.show', 'pap.create', 'pap.edit',
            ])->pluck('id')
        );

        // Professor — (mais tarde)
        $professor = Role::firstOrCreate(['nome' => 'Professor']);
        $this->syncPermissions($professor,
            Permission::whereIn('slug', [
                'turmas.index', 'turmas.show',
                'disciplinas.index', 'disciplinas.show',
            ])->pluck('id')
        );

        // Aluno — (mais tarde)
        $aluno = Role::firstOrCreate(['nome' => 'Aluno']);
        $this->syncPermissions($aluno,
            Permission::whereIn('slug', [
                'alunos.show',
                'dashboard.aluno.notas',
                'dashboard.aluno.grelha',
                'cursos.index', 'cursos.show',
                'disciplinas.index', 'disciplinas.show',
                'pap.show',
            ])->pluck('id')
        );

        $candidato = Role::firstOrCreate(['nome' => 'Candidato']);
        $this->syncPermissions($candidato, collect([])); // sem permissões por agora
    }

    private function syncPermissions(Role $role, $permissionIds): void
    {
        $role->permissions()->sync(
            $permissionIds->mapWithKeys(fn ($id) => [
                $id => ['id' => (string) Str::uuid7()],
            ])->all()
        );
    }
}
