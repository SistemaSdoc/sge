<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Alunos
            [
                'nome' => 'Listar Alunos',
                'slug' => 'alunos.index',
                'descricao' => 'Visualizar lista de alunos',
            ],
            [
                'nome' => 'Ver Aluno',
                'slug' => 'alunos.show',
                'descricao' => 'Visualizar detalhes de um aluno',
            ],
            [
                'nome' => 'Criar Aluno',
                'slug' => 'alunos.create',
                'descricao' => 'Criar novo aluno',
            ],
            [
                'nome' => 'Editar Aluno',
                'slug' => 'alunos.edit',
                'descricao' => 'Editar dados de um aluno',
            ],
            [
                'nome' => 'Eliminar Aluno',
                'slug' => 'alunos.delete',
                'descricao' => 'Eliminar um aluno',
            ],

            // Turmas
            [
                'nome' => 'Listar Turmas',
                'slug' => 'turmas.index',
                'descricao' => 'Visualizar lista de turmas',
            ],
            [
                'nome' => 'Ver Turma',
                'slug' => 'turmas.show',
                'descricao' => 'Visualizar detalhes de uma turma',
            ],
            [
                'nome' => 'Criar Turma',
                'slug' => 'turmas.create',
                'descricao' => 'Criar nova turma',
            ],
            [
                'nome' => 'Editar Turma',
                'slug' => 'turmas.edit',
                'descricao' => 'Editar dados de uma turma',
            ],
            [
                'nome' => 'Eliminar Turma',
                'slug' => 'turmas.delete',
                'descricao' => 'Eliminar uma turma',
            ],

            // Cursos
            [
                'nome' => 'Listar Cursos',
                'slug' => 'cursos.index',
                'descricao' => 'Visualizar lista de cursos',
            ],
            [
                'nome' => 'Ver Curso',
                'slug' => 'cursos.show',
                'descricao' => 'Visualizar detalhes de um curso',
            ],
            [
                'nome' => 'Criar Curso',
                'slug' => 'cursos.create',
                'descricao' => 'Criar novo curso',
            ],
            [
                'nome' => 'Editar Curso',
                'slug' => 'cursos.edit',
                'descricao' => 'Editar dados de um curso',
            ],
            [
                'nome' => 'Eliminar Curso',
                'slug' => 'cursos.delete',
                'descricao' => 'Eliminar um curso',
            ],

            // Professores
            [
                'nome' => 'Listar Professores',
                'slug' => 'professores.index',
                'descricao' => 'Visualizar lista de professores',
            ],
            [
                'nome' => 'Ver Professor',
                'slug' => 'professores.show',
                'descricao' => 'Visualizar detalhes de um professor',
            ],
            [
                'nome' => 'Criar Professor',
                'slug' => 'professores.create',
                'descricao' => 'Criar novo professor',
            ],
            [
                'nome' => 'Editar Professor',
                'slug' => 'professores.edit',
                'descricao' => 'Editar dados de um professor',
            ],
            [
                'nome' => 'Eliminar Professor',
                'slug' => 'professores.delete',
                'descricao' => 'Eliminar um professor',
            ],

            // Disciplinas
            [
                'nome' => 'Listar Disciplinas',
                'slug' => 'disciplinas.index',
                'descricao' => 'Visualizar lista de disciplinas',
            ],
            [
                'nome' => 'Ver Disciplina',
                'slug' => 'disciplinas.show',
                'descricao' => 'Visualizar detalhes de uma disciplina',
            ],
            [
                'nome' => 'Criar Disciplina',
                'slug' => 'disciplinas.create',
                'descricao' => 'Criar nova disciplina',
            ],
            [
                'nome' => 'Editar Disciplina',
                'slug' => 'disciplinas.edit',
                'descricao' => 'Editar dados de uma disciplina',
            ],
            [
                'nome' => 'Eliminar Disciplina',
                'slug' => 'disciplinas.delete',
                'descricao' => 'Eliminar uma disciplina',
            ],

            // Instituições
            [
                'nome' => 'Listar Instituições',
                'slug' => 'instituicoes.index',
                'descricao' => 'Visualizar lista de instituições',
            ],
            [
                'nome' => 'Ver Instituição',
                'slug' => 'instituicoes.show',
                'descricao' => 'Visualizar detalhes de uma instituição',
            ],
            [
                'nome' => 'Criar Instituição',
                'slug' => 'instituicoes.create',
                'descricao' => 'Criar nova instituição',
            ],
            [
                'nome' => 'Editar Instituição',
                'slug' => 'instituicoes.edit',
                'descricao' => 'Editar dados de uma instituição',
            ],
            [
                'nome' => 'Eliminar Instituição',
                'slug' => 'instituicoes.delete',
                'descricao' => 'Eliminar uma instituição',
            ],

            // Classes
            [
                'nome' => 'Listar Classes',
                'slug' => 'classes.index',
                'descricao' => 'Visualizar lista de classes',
            ],
            [
                'nome' => 'Ver Classe',
                'slug' => 'classes.show',
                'descricao' => 'Visualizar detalhes de uma classe',
            ],
            [
                'nome' => 'Criar Classe',
                'slug' => 'classes.create',
                'descricao' => 'Criar nova classe',
            ],
            [
                'nome' => 'Eliminar Classe',
                'slug' => 'classes.delete',
                'descricao' => 'Eliminar uma classe',
            ],

            // Classes
            [
                'nome' => 'Listar avisos',
                'slug' => 'avisos.index',
                'descricao' => 'Visualizar lista de avisos',
            ],
            [
                'nome' => 'Ver Aviso',
                'slug' => 'avisos.show',
                'descricao' => 'Visualizar detalhes de um aviso',
            ],
            [
                'nome' => 'Criar Aviso',
                'slug' => 'avisos.create',
                'descricao' => 'Criar novo aviso',
            ],
            [
                'nome' => 'Eliminar Aviso',
                'slug' => 'avisos.delete',
                'descricao' => 'Eliminar um aviso',
            ],

            // Pautas
            [
                'nome' => 'Ver pauta',
                'slug' => 'pauta.show',
                'descricao' => 'Visualizar pauta',
            ],

            // PAP (Projeto de Área de Projeção)
            [
                'nome' => 'Listar PAP',
                'slug' => 'pap.index',
                'descricao' => 'Visualizar lista de projetos PAP',
            ],
            [
                'nome' => 'Ver PAP',
                'slug' => 'pap.show',
                'descricao' => 'Visualizar detalhes de um projeto PAP',
            ],
            [
                'nome' => 'Criar PAP',
                'slug' => 'pap.create',
                'descricao' => 'Criar novo projeto PAP',
            ],
            [
                'nome' => 'Editar PAP',
                'slug' => 'pap.edit',
                'descricao' => 'Editar dados de um projeto PAP',
            ],
            [
                'nome' => 'Eliminar PAP',
                'slug' => 'pap.delete',
                'descricao' => 'Eliminar um projeto PAP',
            ],

            // Inscrições
            [
                'nome' => 'Listar Inscrições',
                'slug' => 'inscricoes.index',
                'descricao' => 'Visualizar lista de inscrições',
            ],
            [
                'nome' => 'Ver Inscrição',
                'slug' => 'inscricoes.show',
                'descricao' => 'Visualizar detalhes de uma inscrição',
            ],
            [
                'nome' => 'Criar Inscrição',
                'slug' => 'inscricoes.create',
                'descricao' => 'Criar nova inscrição',
            ],
            [
                'nome' => 'Editar Inscrição',
                'slug' => 'inscricoes.edit',
                'descricao' => 'Editar dados de uma inscrição',
            ],
            [
                'nome' => 'Eliminar Inscrição',
                'slug' => 'inscricoes.delete',
                'descricao' => 'Eliminar uma inscrição',
            ],

            // Mini Pauta
            [
                'nome' => 'Ver Mini Pauta',
                'slug' => 'mini-pauta.show',
                'descricao' => 'Visualizar detalhes de uma mini pauta',
            ],
            [
                'nome' => 'Editar Mini Pauta',
                'slug' => 'mini-pauta.edit',
                'descricao' => 'Editar dados de uma mini pauta',
            ],

            // Pauta
            [
                'nome' => 'Ver lista de pautas',
                'slug' => 'pauta.index',
                'descricao' => 'Visualizar lista de pautas',
            ],
            [
                'nome' => 'Ver Pauta',
                'slug' => 'pauta.show',
                'descricao' => 'Visualizar detalhes de uma pauta',
            ],
            [
                'nome' => 'Editar Pauta',
                'slug' => 'pauta.edit',
                'descricao' => 'Editar dados de uma pauta',
            ],

            // Ver colegios com cursos tutelados pela instituição
            [
                'nome' => 'Ver Colégios',
                'slug' => 'colegios.index',
                'descricao' => 'Visualizar colégios com cursos tutelados pela instituição',
            ],

            // Sistema
            [
                'nome' => 'Gerenciar Permissões',
                'slug' => 'permissions.manage',
                'descricao' => 'Gerenciar permissões do sistema',
            ],
            [
                'nome' => 'Gerenciar Roles',
                'slug' => 'roles.manage',
                'descricao' => 'Gerenciar roles do sistema',
            ],
            [
                'nome' => 'Ver Logs',
                'slug' => 'logs.view',
                'descricao' => 'Visualizar logs do sistema',
            ],

            [
                'nome' => 'Acessar Notas do aluno',
                'slug' => 'dashboard.aluno.notas',
                'descricao' => 'Acessar notas do aluno no dashboard',
            ],

            [
                'nome' => 'Acessar Notas do aluno',
                'slug' => 'dashboard.aluno.notas',
                'descricao' => 'Acessar notas do aluno no dashboard',
            ],

            [
                'nome' => 'Acessar Grelha Curricular do aluno',
                'slug' => 'dashboard.aluno.grelha',
                'descricao' => 'Acessar grelha curricular do aluno no dashboard',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}
