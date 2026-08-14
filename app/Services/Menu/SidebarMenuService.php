<?php

namespace App\Services\Menu;

use App\Http\Controllers\AccessManagementController;
use App\Http\Controllers\AlunoController;
use App\Http\Controllers\AnoLectivoController;
use App\Http\Controllers\AvisoController;
use App\Http\Controllers\ClasseController;
use App\Http\Controllers\Colegios\ColegioController;
use App\Http\Controllers\CursosController;
use App\Http\Controllers\DocumentosController;
use App\Http\Controllers\GrelhaCurricularController;
use App\Http\Controllers\GrupoPapController;
use App\Http\Controllers\InscricaoController;
use App\Http\Controllers\InstituicaoController;
use App\Http\Controllers\NotaAlunoController;
use App\Http\Controllers\PautaController;
use App\Http\Controllers\ProfessorController;
use App\Http\Controllers\RegraAvaliacaoController;
use App\Http\Controllers\SolicitacaoEdicaoPautaController;
use App\Http\Controllers\TurmaController;
use App\Http\Controllers\TurnoController;
use App\Models\Aluno;
use App\Models\AnoLectivo;
use App\Models\Aviso;
use App\Models\Classe;
use App\Models\Curso;
use App\Models\GrupoPap;
use App\Models\Inscricao;
use App\Models\Instituicao;
use App\Models\Nota;
use App\Models\Professor;
use App\Models\RegraAvaliacao;
use App\Models\SolicitacaoEdicaoPauta;
use App\Models\Turma;
use App\Models\Turno;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final class SidebarMenuService
{
    public function build(): array
    {
        $groups = [

            new MenuGroup('Plataforma', [
                new MenuItem(
                    key: 'dashboard',
                    title: 'Dashboard',
                    href: route('dashboard'),
                    icon: 'LayoutGrid',
                    can: true,
                ),

                new MenuItem(
                    key: 'instituicoes',
                    title: 'Instituições',
                    href: action([InstituicaoController::class, 'index']),
                    icon: 'Building2',
                    can: fn() => Gate::allows('viewAny', Instituicao::class),
                ),

                new MenuItem(
                    key: 'minha-instituicao',
                    title: 'Minha Instituição',
                    href: (function () {
                        $id = Auth::user()?->instituicao_id;

                        return $id
                            ? action([InstituicaoController::class, 'show'], ['instituicao' => $id])
                            : '#';
                    })(),
                    icon: 'Building2',
                    can: function () {
                        $user = Auth::user();

                        if (!$user?->instituicao_id) {
                            return false;
                        }

                        $instituicao = Instituicao::find($user?->instituicao_id, ['id']);

                        return $instituicao && Gate::allows('view', $instituicao);
                    },
                ),

                new MenuItem(
                    key: 'cursos',
                    title: 'Cursos',
                    href: action([CursosController::class, 'index']),
                    icon: 'BookOpen',
                    can: Gate::allows('viewAny', Curso::class)
                ),

                new MenuItem(
                    key: 'classes',
                    title: 'Classes',
                    href: action([ClasseController::class, 'index']),
                    icon: 'GraduationCap',
                    can: Gate::allows('viewAny', Classe::class)
                ),

                new MenuItem(
                    key: 'turnos',
                    title: 'Turnos',
                    href: action([TurnoController::class, 'index']),
                    icon: 'Clock4',
                    can: Gate::allows('viewAny', Turno::class)
                ),

                new MenuItem(
                    key: 'turmas',
                    title: 'Turmas',
                    href: action([TurmaController::class, 'index']),
                    icon: 'Users',
                    can: fn() => Gate::allows('viewAny', Turma::class),
                ),

                new MenuItem(
                    key: 'pautas',
                    title: 'Pautas',
                    href: action([PautaController::class, 'indexCursos']),
                    icon: 'FileText',
                    can: fn() => Gate::allows('pauta.viewAny')
                ),

                new MenuItem(
                    key: 'grelha-curricular',
                    title: 'Grelha Curricular',
                    href: action([GrelhaCurricularController::class, 'index']),
                    icon: 'LayoutList',
                    can: fn() => Gate::allows('grelha-curricular.viewAny'),
                ),

                new MenuItem(
                    key: 'minhas-notas',
                    title: 'Minhas Notas',
                    href: action([NotaAlunoController::class, 'index']),
                    icon: 'FileTextIcon',
                    can: fn() => Gate::allows('viewAny', Nota::class)
                ),

                new MenuItem(
                    key: 'horarios',
                    title: 'Horários',
                    href: '/dashboard/horarios',
                    icon: 'CalendarClock',
                    can: fn() => Gate::allows('horarios.viewAny')
                ),

                new MenuItem(
                    key: 'grupos-pap',
                    title: 'Grupos PAP',
                    href: action([GrupoPapController::class, 'index']),
                    icon: 'Users',
                    can: fn() => Gate::allows('viewAny', GrupoPap::class),
                ),

                new MenuItem(
                    key: 'regras-avaliacao',
                    title: 'Regras de Avaliação',
                    href: action([RegraAvaliacaoController::class, 'index']),
                    icon: 'FileTextIcon',
                    can: fn() => Gate::allows('viewAny', RegraAvaliacao::class),
                ),

                new MenuItem(
                    key: 'anos-lectivos',
                    title: 'Anos Lectivos',
                    href: action([AnoLectivoController::class, 'index']),
                    icon: 'CalendarClock',
                    can: fn() => Gate::allows('viewAny', AnoLectivo::class)
                ),

                new MenuItem(
                    key: 'solicitacao-lancamento-notas',
                    title: 'Solicitações de Lançamentos',
                    href: action([SolicitacaoEdicaoPautaController::class, 'index']),
                    icon: 'FileTextIcon',
                    can: fn() => Gate::allows('viewAny', SolicitacaoEdicaoPauta::class)
                ),

                new MenuItem(
                    key: 'documentos-escolares',
                    title: 'Documentos Escolares',
                    href: action([DocumentosController::class, 'index']),
                    icon: 'FileTextIcon',
                    //can: fn() => Gate::allows(Documentos::class)
                ),
            ]),

            new MenuGroup('Matrículas', [
                new MenuItem(
                    key: 'inscricoes',
                    title: 'Matrículas',
                    href: action([InscricaoController::class, 'index']),
                    icon: 'ClipboardList',
                    can: fn() => Gate::allows('viewAny', Inscricao::class)
                ),
            ]),

            new MenuGroup('Usuários', [
                new MenuItem(
                    key: 'professores',
                    title: 'Professores',
                    href: action([ProfessorController::class, 'index']),
                    icon: 'Users',
                    can: fn() => Gate::allows('viewAny', Professor::class),
                ),

                new MenuItem(
                    key: 'alunos',
                    title: 'Alunos',
                    href: action([AlunoController::class, 'index']),
                    icon: 'GraduationCap',
                    can: fn() => Gate::allows('viewAny', Aluno::class),
                ),

                /* new MenuItem(
                     key: 'acessos',
                     title: 'Gerir Acessos',
                     href: action([AccessManagementController::class, 'index']),
                     icon: 'ShieldCheck',
                     can: fn () => Gate::allows('acessos.viewAny')
                 ),*/
            ]),

            new MenuGroup('Gestão de Colégios', [
                new MenuItem(
                    key: 'colegios',
                    title: 'Colégios Tutelados',
                    href: (function () {
                        $id = Auth::user()?->instituicao_id;

                        return $id
                            ? action([ColegioController::class, 'index'], ['instituicao' => $id])
                            : '#';
                    })(),
                    icon: 'Building2',
                    can: fn() => Auth::user()?->hasPermissionTo('colegios.viewAny')
                    && Auth::user()?->instituicao?->tipo === 'instituto',
                ),

                /*new MenuItem(
                    key: 'pautas-colegios',
                    title: 'Pautas',
                    href: '#',
                    icon: 'Sheet',
                    can: fn() => Auth::user()?->hasPermissionTo('colegios.viewAny')
                    && Auth::user()?->instituicao?->tipo === 'instituto',
                ),

                new MenuItem(
                    key: 'grupos-pap-colegios',
                    title: 'Grupos PAP',
                    href: '#',
                    icon: 'Users',
                    can: fn() => Auth::user()?->hasPermissionTo('colegios.viewAny')
                    && Auth::user()?->instituicao?->tipo === 'instituto'
                    && Auth::user()?->hasPermissionTo('grupopap.viewAny'),
                ),*/
            ]),

            new MenuGroup('Pagamentos', [
                new MenuItem(
                    key: 'itens-pagaveis',
                    title: 'Emolumentos Escolares',
                    href: route('itens-pagaveis.index'),
                    icon: 'ReceiptText',
                    can: fn() => Auth::user()?->hasPermissionTo('itemspagaveis.viewAny')
                    && Auth::user()?->instituicao?->tipo === 'colegio',
                ),

                new MenuItem(
                    key: 'pagamentos',
                    title: 'Pagamentos',
                    href: route('pagamentos.index'),
                    icon: 'CreditCard',
                    can: fn() => Auth::user()?->hasPermissionTo('pagamentos.viewAny')
                    && Auth::user()?->instituicao?->tipo === 'colegio',
                ),
            ]),

            new MenuGroup('Comunicação', [
                new MenuItem(
                    key: 'avisos',
                    title: 'Avisos',
                    href: action([AvisoController::class, 'index']),
                    icon: 'Bell',
                    can: fn() => Gate::allows('viewAny', Aviso::class),
                ),
            ]),
        ];

        return array_values(array_filter(
            array_map(fn(MenuGroup $group) => $group->toArray(), $groups),
        ));
    }
}
