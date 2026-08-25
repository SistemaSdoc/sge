<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Tenants
            'tenants.viewAny',
            'tenants.view',
            'tenants.create',
            'tenants.update',
            'tenants.delete',

            // Users
            'users.viewAny',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            
            'confirmacao-matricula.viewAny',
            'confirmacao-matricula.view',
            'confirmacao-matricula.create',

            'regra-avaliacao.viewAny',
            'regra-avaliacao.view',
            'regra-avaliacao.create',
            'regra-avaliacao.update',
            'regra-avaliacao.delete',

            // Solicitacao Edicao Pauta
            'solicitacao-edicao-pauta.viewAny',
            'solicitacao-edicao-pauta.view',

            // colegios
            'colegios.viewAny',

            // Alunos
            'alunos.viewAny',
            'alunos.view',
            'alunos.create',
            'alunos.update',

            // Curso Classe
            'cursoclasse.viewAny',
            'cursoclasse.view',
            'cursoclasse.create',
            'cursoclasse.update',
            'cursoclasse.delete',

            // Curso Classe Turno
            'cursoclasseturno.viewAny',
            'cursoclasseturno.view',
            'cursoclasseturno.create',
            'cursoclasseturno.update',
            'cursoclasseturno.delete',

            // Turnos
            'turnos.viewAny',
            'turnos.view',
            'turnos.create',
            'turnos.update',
            'turnos.delete',

            // Turmas
            'turmas.viewAny',
            'turmas.view',
            'turmas.create',
            'turmas.update',
            'turmas.delete',

            // classeturnodisciplina
            'classeturnodisciplina.viewAny',
            'classeturnodisciplina.view',
            'classeturnodisciplina.create',
            'classeturnodisciplina.update',
            'classeturnodisciplina.delete',

            // Pautas
            'pautas.viewAny',
            'pautas.view',
            'pautas.finalizar',
            'pautas.decidirSolicitacaoEdicao',
            'pautas.gerirPrazos',
            'pautas.solicitarEdicao',

            // Notas
            'notas.create',
            'notas.update',
            'notas.viewAny',

            // Grelhar Curricular
            'grelha.viewAny',

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

            // Inscrições
            'inscricoes.viewAny',
            'inscricoes.view',
            'inscricoes.create',
            'inscricoes.update',
            'inscricoes.delete',
            'inscricoes.cancelar',
            'inscricoes.reativar',

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
            'grupopap.corrigirTema',
            'grupopap.aprovar',
            'grupopap.reprovar',
            'grupopap.solicitarMelhoria',

            // Elemento Grupo PAP
            'elementogrupopap.viewAny',
            'elementogrupopap.view',
            'elementogrupopap.create',
            'elementogrupopap.update',
            'elementogrupopap.delete',
            'elementogrupopap.atualizarNota',

            // Cursos
            'cursos.viewAny',
            'cursos.view',
            'cursos.create',
            'cursos.update',
            'cursos.delete',

            // Classes
            'classes.viewAny',
            'classes.view',
            'classes.create',
            'classes.update',
            'classes.delete',

            // Cursos Tutelados
            'curso-tutelado.viewAny',
            'curso-tutelado.view',
            'curso-tutelado.create',
            'curso-tutelado.update',
            'curso-tutelado.delete',

            // Mini-pauta
            'notas.export',

            // Sistema
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

            'coordenador.view-curso',
            'coordenador.update-curso',
            'coordenador.manage-professores',
            'coordenador.manage-turmas',
            'coordenador.view-pautas',
            'coordenador.update-pautas',
            'coordenador.create-notas',
            'coordenador.update-notas',
            'coordenador.view-relatorios',

            'confirmacoes.viewAny',
            'confirmacoes.confirmar',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
