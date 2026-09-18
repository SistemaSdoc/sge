<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Instituições
            'instituicoes.view' => 'Ver dados da instituição',
            'instituicoes.update' => 'Editar dados da instituição',

            // Confirmação de matrícula
            'confirmacoes.matricula.viewAny' => 'Ver lista de confirmações de matrícula',
            'confirmacoes.matricula.confirmar' => 'Confirmar matrícula de um aluno',

            // Regras de avaliação
            'regra-avaliacao.viewAny' => 'Ver regras de avaliação',
            'regra-avaliacao.view' => 'Ver detalhes de uma regra de avaliação',
            'regra-avaliacao.create' => 'Criar nova regra de avaliação',
            'regra-avaliacao.update' => 'Editar regra de avaliação',
            'regra-avaliacao.delete' => 'Eliminar regra de avaliação',

            // Solicitação de edição de pauta
            'solicitacao-edicao-pauta.viewAny' => 'Ver pedidos de edição de pauta',
            'solicitacao-edicao-pauta.view' => 'Ver detalhes de um pedido de edição de pauta',

            // Colégios
            'colegios.viewAny' => 'Ver lista de colégios',

            // Alunos
            'alunos.viewAny' => 'Ver lista de alunos',
            'alunos.view' => 'Ver perfil de um aluno',
            'alunos.create' => 'Cadastrar novo aluno',
            'alunos.update' => 'Editar dados de um aluno',

            // Classes de curso
            'cursoclasse.viewAny' => 'Ver classes de um curso',
            'cursoclasse.view' => 'Ver detalhes de uma classe de curso',
            'cursoclasse.create' => 'Adicionar classe a um curso',
            'cursoclasse.update' => 'Editar classe de um curso',
            'cursoclasse.delete' => 'Remover classe de um curso',

            // Turnos de classe
            'cursoclasseturno.viewAny' => 'Ver turnos disponíveis por classe',
            'cursoclasseturno.view' => 'Ver detalhes do turno de uma classe',
            'cursoclasseturno.create' => 'Adicionar turno a uma classe',
            'cursoclasseturno.update' => 'Editar turno de uma classe',
            'cursoclasseturno.delete' => 'Remover turno de uma classe',

            // Turnos
            'turnos.viewAny' => 'Ver lista de turnos (manhã, tarde, noite)',
            'turnos.view' => 'Ver detalhes de um turno',
            'turnos.create' => 'Criar novo turno',
            'turnos.update' => 'Editar turno',
            'turnos.delete' => 'Eliminar turno',

            // Turmas
            'turmas.viewAny' => 'Ver lista de turmas',
            'turmas.view' => 'Ver detalhes de uma turma',
            'turmas.create' => 'Criar nova turma',
            'turmas.update' => 'Editar turma',
            'turmas.delete' => 'Eliminar turma',

            // Disciplinas da turma
            'classeturnodisciplina.viewAny' => 'Ver disciplinas atribuídas à turma',
            'classeturnodisciplina.view' => 'Ver detalhes de uma disciplina da turma',
            'classeturnodisciplina.create' => 'Atribuir disciplina a uma turma',
            'classeturnodisciplina.update' => 'Editar disciplina atribuída à turma',
            'classeturnodisciplina.delete' => 'Remover disciplina da turma',
            'classeturnodisciplina.definirProfessor' => 'Definir professor da disciplina na turma',
            'classeturnodisciplina.gerirHorarios' => 'Definir horários da disciplina na turma',

            // Pautas
            'pautas.viewAny' => 'Ver lista de pautas',
            'pautas.view' => 'Ver detalhes de uma pauta',
            'pautas.finalizar' => 'Finalizar (fechar) pauta',
            'pautas.decidirSolicitacaoEdicao' => 'Aprovar ou recusar pedido de edição de pauta',
            'pautas.gerirPrazos' => 'Definir prazos de lançamento de pauta',
            'pautas.solicitarEdicao' => 'Solicitar reabertura de uma pauta',

            // Notas
            'notas.create' => 'Lançar notas dos alunos',
            'notas.update' => 'Editar notas já lançadas',
            'notas.viewAny' => 'Ver notas dos alunos',
            'notas.export' => 'Exportar notas (Excel/PDF)',

            // Grelha curricular
            'grelha.viewAny' => 'Ver grelha curricular do curso',

            // Professores
            'professores.viewAny' => 'Ver lista de professores',
            'professores.view' => 'Ver perfil de um professor',
            'professores.create' => 'Cadastrar novo professor',
            'professores.update' => 'Editar dados de um professor',
            'professores.delete' => 'Eliminar professor',

            // Avisos
            'avisos.viewAny' => 'Ver lista de avisos',
            'avisos.view' => 'Ver detalhes de um aviso',
            'avisos.create' => 'Publicar novo aviso',
            'avisos.update' => 'Editar aviso publicado',
            'avisos.delete' => 'Eliminar aviso',

            // Inscrições
            'inscricoes.viewAny' => 'Ver lista de matrículas',
            'inscricoes.view' => 'Ver detalhes de uma matrícula',
            'inscricoes.create' => 'Matricular novo aluno',
            'inscricoes.update' => 'Editar dados da matrícula',
            'inscricoes.delete' => 'Eliminar matrícula',
            'inscricoes.cancelar' => 'Cancelar matrícula de um aluno',
            'inscricoes.reativar' => 'Reativar matrícula cancelada',

            // Grupos PAP
            'grupopap.viewAny' => 'Ver lista de grupos PAP',
            'grupopap.view' => 'Ver detalhes de um grupo PAP',
            'grupopap.create' => 'Criar novo grupo PAP',
            'grupopap.update' => 'Editar grupo PAP',
            'grupopap.delete' => 'Eliminar grupo PAP',
            'grupopap.definirData' => 'Definir data de defesa do grupo PAP',
            'grupopap.definirTema' => 'Definir tema do grupo PAP',
            'grupopap.selecionarInstituicao' => 'Selecionar instituição do grupo PAP',
            'grupopap.selecionarAnoLectivo' => 'Selecionar ano letivo do grupo PAP',
            'grupopap.corrigirTema' => 'Solicitar correção do tema do PAP',
            'grupopap.aprovar' => 'Aprovar grupo PAP',
            'grupopap.reprovar' => 'Reprovar grupo PAP',
            'grupopap.solicitarMelhoria' => 'Solicitar melhorias no PAP',

            // Elementos do grupo PAP
            'elementogrupopap.viewAny' => 'Ver membros do grupo PAP',
            'elementogrupopap.view' => 'Ver detalhes de um membro do grupo PAP',
            'elementogrupopap.create' => 'Adicionar membro ao grupo PAP',
            'elementogrupopap.update' => 'Editar dados de um membro do PAP',
            'elementogrupopap.delete' => 'Remover membro do grupo PAP',
            'elementogrupopap.atualizarNota' => 'Lançar/editar nota do membro do PAP',

            // Bancas de júri PAP
            'bancajuripap.viewAny' => 'Ver bancas de júri do PAP',
            'bancajuripap.view' => 'Ver detalhes de uma banca de júri',
            'bancajuripap.create' => 'Criar banca de júri para o PAP',
            'bancajuripap.update' => 'Editar banca de júri',
            'bancajuripap.delete' => 'Eliminar banca de júri',

            // Classes
            'classes.viewAny' => 'Ver lista de classes',
            'classes.view' => 'Ver detalhes de uma classe',
            'classes.create' => 'Criar nova classe',
            'classes.update' => 'Editar classe',
            'classes.delete' => 'Eliminar classe',

            // Cursos tutelados
            'curso-tutelado.viewAny' => 'Ver cursos tutelados',
            'curso-tutelado.view' => 'Ver detalhes de um curso tutelado',
            'curso-tutelado.create' => 'Criar curso tutelado',
            'curso-tutelado.update' => 'Editar curso tutelado',
            'curso-tutelado.delete' => 'Eliminar curso tutelado',

            // Utilizadores
            'usuarios.viewAny' => 'Ver lista de utilizadores',
            'usuarios.view' => 'Ver perfil de um utilizador',
            'usuarios.create' => 'Criar novo utilizador',
            'usuarios.update' => 'Editar dados de um utilizador',
            'usuarios.delete' => 'Eliminar utilizador',

            // Acessos
            'acessos.viewAny' => 'Ver permissões de acesso',
            'acessos.create' => 'Atribuir permissões de acesso',

            // Relatórios
            'relatorios.view' => 'Ver relatórios do sistema',

            // Pagamentos
            'pagamentos.viewAny' => 'Ver lista de pagamentos',
            'pagamentos.view' => 'Ver detalhes de um pagamento',
            'pagamentos.create' => 'Registar novo pagamento',
            'pagamentos.update' => 'Editar pagamento',
            'pagamentos.delete' => 'Eliminar pagamento',

            // Itens pagáveis
            'itemspagaveis.viewAny' => 'Ver itens pagáveis (propinas, taxas, etc.)',
            'itemspagaveis.view' => 'Ver detalhes de um item pagável',
            'itemspagaveis.create' => 'Criar novo item pagável',
            'itemspagaveis.update' => 'Editar item pagável',
            'itemspagaveis.delete' => 'Eliminar item pagável',

            // Documentos
            'documentos.viewAny' => 'Ver lista de documentos',
            'documentos.view' => 'Ver detalhes de um documento',
            'documentos.emitir' => 'Emitir documentos (certificados, declarações, etc.)',
            'documentos.exportar' => 'Exportar documentos',

            // Coordenador
            'coordenador.view-curso' => 'Ver curso sob coordenação',
            'coordenador.update-curso' => 'Editar curso sob coordenação',
            'coordenador.manage-professores' => 'Gerir professores do curso',
            'coordenador.manage-turmas' => 'Gerir turmas do curso',
            'coordenador.view-pautas' => 'Ver pautas do curso',
            'coordenador.update-pautas' => 'Editar pautas do curso',
            'coordenador.create-notas' => 'Lançar notas do curso',
            'coordenador.update-notas' => 'Editar notas do curso',
            'coordenador.view-relatorios' => 'Ver relatórios do curso',

        ];

        foreach ($permissions as $permission => $label) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'tenant'],
                ['label' => $label]
            );

            Permission::where('name', $permission)
                ->where('guard_name', 'tenant')
                ->update(['label' => $label]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
