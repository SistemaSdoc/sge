<?php

namespace App\Services\Tenant\Menu;

use App\Http\Controllers\Tenant\AlunoController;
use App\Http\Controllers\Tenant\AnoLectivoController;
use App\Http\Controllers\Tenant\AvisoController;
use App\Http\Controllers\Tenant\ClasseController;
use App\Http\Controllers\Tenant\Colegios\ColegioController;
use App\Http\Controllers\Tenant\CursosController;
use App\Http\Controllers\Tenant\CursoTuteladoController;
use App\Http\Controllers\Tenant\DocumentosController;
use App\Http\Controllers\Tenant\GrelhaCurricularController;
use App\Http\Controllers\Tenant\GrupoPapController;
use App\Http\Controllers\Tenant\InscricaoController;
use App\Http\Controllers\Tenant\InstituicaoController;
use App\Http\Controllers\Tenant\NotaAlunoController;
use App\Http\Controllers\Tenant\PautaController;
use App\Http\Controllers\Tenant\ProfessorController;
use App\Http\Controllers\Tenant\RegraAvaliacaoController;
use App\Http\Controllers\Tenant\SolicitacaoEdicaoPautaController;
use App\Http\Controllers\Tenant\TurmaController;
use App\Http\Controllers\Tenant\TurnoController;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Aviso;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Inscricao;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Nota;
use App\Models\Tenant\Professor;
use App\Models\Tenant\RegraAvaliacao;
use App\Models\Tenant\SolicitacaoEdicaoPauta;
use App\Models\Tenant\Turma;
use App\Models\Tenant\Turno;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final class SidebarMenuService
{
    public function build(): array
    {
        $user = Auth::guard('tenant')->user();
        $gate = Gate::forUser($user);

        $groups = [

            new MenuGroup('Plataforma', [
                new MenuItem(
                    key: 'dashboard',
                    title: 'Dashboard',
                    href: route('tenant.dashboard'),
                    icon: 'LayoutGrid',
                    can: true,
                ),

                new MenuItem(
                    key: 'instituicoes',
                    title: 'Instituições',
                    href: action([InstituicaoController::class, 'index']),
                    icon: 'Building2',
                    can: fn() => $gate->allows('viewAny', Instituicao::class),
                ),

                new MenuItem(
                    key: 'minha-instituicao',
                    title: 'Minha Instituição',
                    href: (function () use ($user) {
                        $id = $user?->instituicao_id;

                        return $id
                            ? action([InstituicaoController::class, 'show'], ['instituicao' => $id])
                            : '#';
                    })(),
                    icon: 'Building2',
                    can: function () use ($user, $gate) {
                        if (!$user?->instituicao_id) {
                            return false;
                        }

                        $instituicao = Instituicao::find($user?->instituicao_id, ['id']);

                        return $instituicao && $gate->allows('view', $instituicao);
                    },
                ),

                new MenuItem(
                    key: 'meus-cursos',
                    title: 'Meus Cursos',
                    href: (function () use ($user) {
                        $id = $user?->instituicao_id;

                        return $id
                            ? action([CursoTuteladoController::class, 'index'], ['instituicao' => $id])
                            : '#';
                    })(),
                    icon: 'Building2',
                    can: function () use ($user, $gate) {
                        if (!$user?->instituicao_id) {
                            return false;
                        }

                        $instituicao = Instituicao::find($user?->instituicao_id, ['id']);

                        return $instituicao && $gate->allows('view', $instituicao);
                    },
                ),

                new MenuItem(
                    key: 'cursos',
                    title: 'Cursos',
                    href: action([CursosController::class, 'index']),
                    icon: 'BookOpen',
                    can: $gate->allows('viewAny', Curso::class)
                ),

                new MenuItem(
                    key: 'classes',
                    title: 'Classes',
                    href: action([ClasseController::class, 'index']),
                    icon: 'GraduationCap',
                    can: $gate->allows('viewAny', Classe::class)
                ),

                new MenuItem(
                    key: 'turnos',
                    title: 'Turnos',
                    href: action([TurnoController::class, 'index']),
                    icon: 'Clock4',
                    can: $gate->allows('viewAny', Turno::class)
                ),

                new MenuItem(
                    key: 'turmas',
                    title: 'Turmas',
                    href: action([TurmaController::class, 'index']),
                    icon: 'Users',
                    can: fn() => $gate->allows('viewAny', Turma::class),
                ),

                new MenuItem(
                    key: 'pautas',
                    title: 'Pautas',
                    href: action([PautaController::class, 'indexCursos']),
                    icon: 'FileText',
                    can: fn() => $gate->allows('pauta.viewAny')
                ),

                new MenuItem(
                    key: 'grelha-curricular',
                    title: 'Grelha Curricular',
                    href: action([GrelhaCurricularController::class, 'index']),
                    icon: 'LayoutList',
                    can: fn() => $gate->allows('grelha-curricular.viewAny'),
                ),

                new MenuItem(
                    key: 'minhas-notas',
                    title: 'Minhas Notas',
                    href: action([NotaAlunoController::class, 'index']),
                    icon: 'FileTextIcon',
                    can: fn() => $gate->allows('viewAny', Nota::class)
                ),

                new MenuItem(
                    key: 'horarios',
                    title: 'Horários',
                    href: route('tenant.dashboard.horarios'),
                    icon: 'CalendarClock',
                    can: fn() => $gate->allows('horarios.viewAny')
                ),

                new MenuItem(
                    key: 'grupos-pap',
                    title: 'Grupos PAP',
                    href: action([GrupoPapController::class, 'index']),
                    icon: 'Users',
                    can: fn() => $gate->allows('viewAny', GrupoPap::class),
                ),

                new MenuItem(
                    key: 'regras-avaliacao',
                    title: 'Regras de Avaliação',
                    href: action([RegraAvaliacaoController::class, 'index']),
                    icon: 'FileTextIcon',
                    can: fn() => $gate->allows('viewAny', RegraAvaliacao::class),
                ),

                new MenuItem(
                    key: 'anos-lectivos',
                    title: 'Anos Lectivos',
                    href: action([AnoLectivoController::class, 'index']),
                    icon: 'CalendarClock',
                    can: fn() => $gate->allows('viewAny', AnoLectivo::class)
                ),

                new MenuItem(
                    key: 'solicitacao-lancamento-notas',
                    title: 'Solicitações de Lançamentos',
                    href: action([SolicitacaoEdicaoPautaController::class, 'index']),
                    icon: 'FileTextIcon',
                    can: fn() => $gate->allows('viewAny', SolicitacaoEdicaoPauta::class)
                ),

                new MenuItem(
                    key: 'documentos-escolares',
                    title: 'Documentos Escolares',
                    href: action([DocumentosController::class, 'index']),
                    icon: 'FileTextIcon',
                    can: fn() => Auth::user()?->hasPermissionTo('documentos.viewAny'),
                ),
            ]),

            new MenuGroup('Matrículas', [
                new MenuItem(
                    key: 'inscricoes',
                    title: 'Matrículas',
                    href: action([InscricaoController::class, 'index']),
                    icon: 'ClipboardList',
                    can: fn() => $gate->allows('viewAny', Inscricao::class)
                ),
            ]),

            new MenuGroup('Usuários', [
                new MenuItem(
                    key: 'professores',
                    title: 'Professores',
                    href: action([ProfessorController::class, 'index']),
                    icon: 'Users',
                    can: fn() => $gate->allows('viewAny', Professor::class),
                ),

                new MenuItem(
                    key: 'alunos',
                    title: 'Alunos',
                    href: action([AlunoController::class, 'index']),
                    icon: 'GraduationCap',
                    can: fn() => $gate->allows('viewAny', Aluno::class),
                ),
            ]),

            new MenuGroup('Gestão de Colégios', [
                new MenuItem(
                    key: 'colegios',
                    title: 'Colégios Tutelados',
                    href: (function () use ($user) {
                        $id = $user?->instituicao_id;

                        return $id
                            ? action([ColegioController::class, 'index'], ['instituicao' => $id])
                            : '#';
                    })(),
                    icon: 'Building2',
                    can: fn() => $gate->allows('colegios.viewAny')
                    && $user?->instituicao?->tipo === 'instituto',
                ),
            ]),

            new MenuGroup('Pagamentos', [
                new MenuItem(
                    key: 'itens-pagaveis',
                    title: 'Emolumentos Escolares',
                    href: route('tenant.dashboard.itens-pagaveis.index'),
                    icon: 'ReceiptText',
                    can: fn() => $gate->allows('itemspagaveis.viewAny')
                ),

                new MenuItem(
                    key: 'pagamentos',
                    title: 'Pagamentos',
                    href: route('tenant.dashboard.pagamentos.index'),
                    icon: 'CreditCard',
                    can: fn() => $gate->allows('pagamentos.viewAny')
                    && $user?->instituicao?->tipo === 'colegio',
                ),
            ]),

            new MenuGroup('Comunicação', [
                new MenuItem(
                    key: 'avisos',
                    title: 'Avisos',
                    href: action([AvisoController::class, 'index']),
                    icon: 'Bell',
                    can: fn() => $gate->allows('viewAny', Aviso::class),
                ),
            ]),
        ];

        return array_values(array_filter(
            array_map(fn(MenuGroup $group) => $group->toArray(), $groups),
        ));
    }
}
