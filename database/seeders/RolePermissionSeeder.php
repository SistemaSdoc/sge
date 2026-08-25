<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $mapa = [
            'Director' => [
                // Instituições
                'instituicoes.view',
                'instituicoes.update',
                'colegios.viewAny',

                // solicitacao-edicao-pauta
                'solicitacao-edicao-pauta.viewAny',
                'solicitacao-edicao-pauta.view',

                // Regra de Avaliação
                'regra-avaliacao.viewAny',
                'regra-avaliacao.view',
                'regra-avaliacao.create',
                'regra-avaliacao.update',
                'regra-avaliacao.delete',

                // Alunos
                'alunos.viewAny',
                'alunos.view',
                'alunos.create',
                'alunos.update',

                // Turmas
                'turmas.viewAny',
                'turmas.view',
                'turmas.create',
                'turmas.update',
                'turmas.delete',

                // Turnos
                // 'turnos.viewAny',
                // 'turnos.view',
                // 'turnos.create',
                // 'turnos.update',
                // 'turnos.delete',

                // Inscrições
                'inscricoes.viewAny',
                'inscricoes.view',
                'inscricoes.create',
                'inscricoes.update',
                'inscricoes.cancelar',
                'inscricoes.reativar',

                // CursoClasse
                'cursoclasse.viewAny',
                'cursoclasse.view',
                'cursoclasse.create',
                'cursoclasse.update',
                'cursoclasse.delete',

                // classeturnodisciplina
                'classeturnodisciplina.viewAny',
                'classeturnodisciplina.view',
                'classeturnodisciplina.create',
                'classeturnodisciplina.update',
                'classeturnodisciplina.delete',

                // Curso Classe Turno
                'cursoclasseturno.viewAny',
                'cursoclasseturno.view',
                'cursoclasseturno.create',
                'cursoclasseturno.update',
                'cursoclasseturno.delete',

                // Pautas
                'pautas.viewAny',
                'pautas.view',

                // Notas
                'notas.create',
                'notas.update',

                // Notas
                'pautas.finalizar',
                'pautas.decidirSolicitacaoEdicao',
                'pautas.gerirPrazos',

                // Professores
                'professores.viewAny',
                'professores.view',
                'professores.create',
                'professores.update',
                'professores.delete',

                // Avisos
                'avisos.viewAny',
                'avisos.view',
                'avisos.create',
                'avisos.update',
                'avisos.delete',

                // Grupo PAP
                'grupopap.viewAny',
                'grupopap.view',
                'grupopap.create',
                'grupopap.update',
                'grupopap.delete',
                'grupopap.definirData',
                'grupopap.aprovar',
                'grupopap.reprovar',
                'grupopap.solicitarMelhoria',

                // Banca de Júri PAP
                'bancajuripap.viewAny',
                'bancajuripap.view',
                'bancajuripap.create',
                'bancajuripap.update',
                'bancajuripap.delete',

                // Elemento Grupo PAP
                'elementogrupopap.viewAny',
                'elementogrupopap.view',
                'elementogrupopap.create',
                'elementogrupopap.update',
                'elementogrupopap.delete',
                'elementogrupopap.atualizarNota',

                // Classes
                // 'classes.viewAny',
                // 'classes.view',
                // 'classes.create',
                // 'classes.update',
                // 'classes.delete',

                // Cursos
                // 'cursos.viewAny',
                // 'cursos.view',
                // 'cursos.create',
                // 'cursos.update',
                // 'cursos.delete',

                // Cursos Tutelados
                'curso-tutelado.viewAny',
                'curso-tutelado.view',
                'curso-tutelado.create',
                'curso-tutelado.update',
                'curso-tutelado.delete',

                // Mini-pauta
                'notas.export',

                // Outros
                'utilizadores.gerir',
                'acessos.viewAny',
                'acessos.create',
                'relatorios.view',

                // Pagamentos
                'pagamentos.viewAny',
                'pagamentos.view',
                'pagamentos.create',
                'pagamentos.update',
                'pagamentos.delete',

                // Itens Pagáveis
                'itemspagaveis.viewAny',
                'itemspagaveis.view',
                'itemspagaveis.create',
                'itemspagaveis.update',
                'itemspagaveis.delete',

                // Documentos
                'documentos.viewAny',
                'documentos.view',
                'documentos.emitir',
                'documentos.exportar',

                // Confirmação de Matrícula
                'confirmacoes.viewAny',
                'confirmacoes.confirmar',
            ],

            'Subdirector' => [
                // Instituições
                'instituicoes.view',
                'instituicoes.update',

                // Regra de Avaliação
                'regra-avaliacao.viewAny',
                'regra-avaliacao.view',
                'regra-avaliacao.create',
                'regra-avaliacao.update',
                'regra-avaliacao.delete',

                // Alunos
                'alunos.viewAny',
                'alunos.view',
                'alunos.update',

                // Turmas
                'turmas.viewAny',
                'turmas.view',
                'turmas.create',
                'turmas.update',
                'turmas.delete',

                // Inscrições
                'inscricoes.viewAny',
                'inscricoes.view',
                'inscricoes.create',
                'inscricoes.update',
                'inscricoes.cancelar',
                'inscricoes.reativar',

                // CursoClasse
                'cursoclasse.viewAny',
                'cursoclasse.view',
                'cursoclasse.create',
                'cursoclasse.update',
                'cursoclasse.delete',

                // classeturnodisciplina
                'classeturnodisciplina.viewAny',
                'classeturnodisciplina.view',
                'classeturnodisciplina.create',
                'classeturnodisciplina.update',
                'classeturnodisciplina.delete',

                // Curso Classe Turno
                'cursoclasseturno.viewAny',
                'cursoclasseturno.view',
                'cursoclasseturno.create',
                'cursoclasseturno.update',
                'cursoclasseturno.delete',

                // Pautas
                'pautas.viewAny',
                'pautas.view',

                // Notas
                'notas.create',
                'notas.update',

                // Professores
                'professores.viewAny',
                'professores.view',
                'professores.create',
                'professores.update',

                // Avisos
                'avisos.viewAny',
                'avisos.view',
                'avisos.create',
                'avisos.update',

                // Grupo PAP
                'grupopap.viewAny',
                'grupopap.view',
                'grupopap.create',
                'grupopap.update',
                'grupopap.delete',
                'grupopap.definirData',

                // Banca de Júri PAP
                'bancajuripap.viewAny',
                'bancajuripap.view',
                'bancajuripap.create',
                'bancajuripap.update',
                'bancajuripap.delete',

                // Elemento Grupo PAP
                'elementogrupopap.viewAny',
                'elementogrupopap.view',
                'elementogrupopap.create',
                'elementogrupopap.update',
                'elementogrupopap.delete',
                'elementogrupopap.atualizarNota',

                // Classes
                // 'classes.viewAny',
                // 'classes.view',
                // 'classes.create',
                // 'classes.update',
                // 'classes.delete',

                // Cursos
                // 'cursos.viewAny',
                // 'cursos.view',
                // 'cursos.create',
                // 'cursos.update',
                // 'cursos.delete',

                // Cursos Tutelados
                'curso-tutelado.viewAny',
                'curso-tutelado.view',
                'curso-tutelado.create',
                'curso-tutelado.update',

                // Turnos
                // 'turnos.viewAny',
                // 'turnos.view',
                // 'turnos.create',
                // 'turnos.update',
                // 'turnos.delete',

                // Pagamentos
                'pagamentos.viewAny',
                'pagamentos.view',
                'pagamentos.create',
                'pagamentos.update',
                'pagamentos.delete',

                // Itens Pagáveis
                'itemspagaveis.viewAny',
                'itemspagaveis.view',
                'itemspagaveis.create',
                'itemspagaveis.update',
                'itemspagaveis.delete',

                // Documentos
                'documentos.viewAny',
                'documentos.view',
                'documentos.emitir',
                'documentos.exportar',

                // Mini-pauta
                'notas.export',

                // Outros
                'relatorios.view',

                // Confirmação de Matrícula
                'confirmacoes.viewAny',
                'confirmacoes.confirmar',
            ],

            'Secretaria' => [
                // Instituições
                'instituicoes.view',

                // Alunos
                'alunos.viewAny',
                'alunos.view',
                'alunos.create',
                'alunos.update',

                // Turmas
                'turmas.viewAny',
                'turmas.view',
                'turmas.create',
                'turmas.update',

                // Pautas
                'pautas.viewAny',
                'pautas.view',

                // Professores
                'professores.viewAny',
                'professores.view',
                'professores.create',
                'professores.update',

                // classeturnodisciplina
                'classeturnodisciplina.viewAny',
                'classeturnodisciplina.view',

                // Curso Classe Turno
                'cursoclasseturno.viewAny',
                'cursoclasseturno.view',

                // Avisos
                'avisos.viewAny',
                'avisos.view',
                'avisos.create',
                'avisos.update',

                // Turnos
                // 'turnos.viewAny',
                // 'turnos.view',
                // 'turnos.create',
                // 'turnos.update',

                // Inscrições
                'inscricoes.viewAny',
                'inscricoes.view',
                'inscricoes.create',
                'inscricoes.update',
                'inscricoes.cancelar',
                'inscricoes.reativar',
                // CursoClasse
                'cursoclasse.viewAny',
                'cursoclasse.view',

                // Grupo PAP
                'grupopap.viewAny',
                'grupopap.view',
                'grupopap.create',
                'grupopap.update',
                'grupopap.delete',
                'grupopap.definirData',

                // Banca de Júri PAP
                'bancajuripap.viewAny',
                'bancajuripap.view',
                'bancajuripap.create',

                // Elemento Grupo PAP
                'elementogrupopap.viewAny',
                'elementogrupopap.view',
                'elementogrupopap.create',
                'elementogrupopap.update',

                // Cursos
                // 'cursos.viewAny',
                // 'cursos.view',
                // 'cursos.create',
                // 'cursos.update',

                // Classes
                // 'classes.viewAny',
                // 'classes.view',
                // 'classes.create',
                // 'classes.update',

                // Cursos Tutelados
                'curso-tutelado.viewAny',
                'curso-tutelado.view',

                // Mini-pauta
                'notas.export',

                // Pagamentos
                'pagamentos.viewAny',
                'pagamentos.view',
                'pagamentos.create',
                'pagamentos.update',

                // Itens Pagáveis
                'itemspagaveis.viewAny',
                'itemspagaveis.view',
                'itemspagaveis.create',
                'itemspagaveis.update',

                // Confirmação de Matrícula
                'confirmacoes.viewAny',
                'confirmacoes.confirmar',
            ],

            'Coordenador' => [

                'instituicoes.view',
                'instituicoes.update',

                'alunos.viewAny',
                'alunos.view',

                'colegios.viewAny',

                'professores.viewAny',
                'professores.view',
                'professores.create',
                'professores.update',

                'turmas.viewAny',
                'turmas.view',
                'turmas.create',
                'turmas.update',

                'pautas.viewAny',
                'pautas.view',

                'pautas.solicitarEdicao',
                'pautas.finalizar',

                'notas.create',
                'notas.update',
                'notas.viewAny',
                'notas.export',

                'avisos.viewAny',
                'avisos.view',
                'avisos.create',
                'avisos.update',

                'grupopap.viewAny',
                'grupopap.view',
                'grupopap.create',
                'grupopap.update',
                'grupopap.aprovar',
                'grupopap.reprovar',
                'grupopap.definirData',
                'grupopap.solicitarMelhoria',

                'elementogrupopap.viewAny',
                'elementogrupopap.view',
                'elementogrupopap.create',
                'elementogrupopap.update',
                'elementogrupopap.atualizarNota',

                'bancajuripap.viewAny',
                'bancajuripap.view',
                'bancajuripap.create',
                'bancajuripap.update',

                // Curso Tutelado
                'curso-tutelado.view',
                'curso-tutelado.update',

                // Confirmação de Matrícula
                'confirmacoes.viewAny',
                'confirmacoes.confirmar',
            ],

            'Professor' => [
                // Alunos
                // 'alunos.viewAny',
                // 'alunos.view',

                // Professores
                'professores.view',

                // Turmas
                'turmas.viewAny',
                'turmas.view',

                // Pautas
                'pautas.viewAny',
                'pautas.view',

                // Notas
                'notas.create',
                'notas.update',

                // Professor e Coordenador (já têm notas.create/update)
                'pautas.solicitarEdicao',
                'pautas.finalizar',

                // Avisos
                // 'avisos.viewAny',
                'avisos.view',

                // Grupo PAP
                'grupopap.viewAny',
                'grupopap.view',
                'grupopap.create',
                'grupopap.update',
                'grupopap.corrigirTema',

                // Elemento Grupo PAP
                'elementogrupopap.viewAny',
                'elementogrupopap.view',
                'elementogrupopap.create',
                'elementogrupopap.update',

                // Banca de Júri PAP
                'bancajuripap.viewAny',
                'bancajuripap.view',

                // Mini-pauta
                'notas.export',
            ],

            'Aluno' => [
                'notas.viewAny',
                'grelha.viewAny',

                'grupopap.viewAny',
                'grupopap.view',
                'grupopap.corrigirTema',
            ],

            'Candidato' => [],
        ];

        foreach ($mapa as $roleName => $permissions) {
            Role::findByName($roleName)->syncPermissions($permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
