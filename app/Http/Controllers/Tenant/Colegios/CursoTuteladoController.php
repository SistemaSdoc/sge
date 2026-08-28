<?php

namespace App\Http\Controllers\Tenant\Colegios;

use App\Http\Controllers\Controller;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CursoTuteladoController extends Controller
{
    public function show(Request $request, string $colegio, string $cursoTutelado)
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        $instituicaoTutor = Instituicao::findOrFail($user->instituicao_id);
        $tenantTutorId = (string) tenancy()->tenant->getTenantKey();
        $shared = CursoTuteladoShared::query()
            ->where('tenant_tutor_id', $tenantTutorId)
            ->where('tenant_tutelado_id', function ($query) use ($colegio): void {
                $query->select('id')
                    ->from('tenants')
                    ->where('instituicao_id', $colegio)
                    ->limit(1);
            })
            ->where('curso_tutelado_tutelado_id', $cursoTutelado)
            ->where('status', 'activo')
            ->firstOrFail();

        $tenantTutelado = Tenant::query()->findOrFail($shared->tenant_tutelado_id);

        return $tenantTutelado->run(function () use ($instituicaoTutor, $colegio, $cursoTutelado) {
            return $this->showFromTenant(
                $instituicaoTutor,
                Instituicao::findOrFail($colegio),
                $cursoTutelado,
            );
        });
    }

    private function showFromTenant(
        Instituicao $instituicao,
        Instituicao $colegio,
        string $cursoTuteladoId
    ) {
        // Buscar o curso tutelado garantindo que:
        // - pertence ao colégio
        // - é tutelado pelo instituto
        $cursoTutelado = CursoTutelado::whereKey($cursoTuteladoId)
            ->whereHas('instituicaoCurso', function ($query) use ($colegio) {
                $query->where('instituicao_id', $colegio->id);
            })
            ->with([
                'instituicaoCurso.curso:id,nome,descricao',
                'instituicaoCurso.instituicao:id,nome',
                'cursoClasses.classe:id,nome',

                'cursoClasses.turnos.turno:id,nome',

                'cursoClasses.turnos' => function ($query) {
                    $query->with([
                        'turmas.alunos:id',
                        'turmas.cursoClasseTurno.turno:id,nome',
                        'turmas.cursoClasseTurno.cursoClasse.classe:id,nome',
                        'classeTurnoDisciplinas.professores',
                        'classeTurnoDisciplinas',
                    ]);
                },

                'professores.user:id,nome',
            ])
            ->firstOrFail();

        return Inertia::render('tenant/colegio/cursos-tutelados/show', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
            ],

            'colegio' => [
                'id' => $colegio->id,
                'nome' => $colegio->nome,
            ],

            'cursoTutelado' => [
                'id' => $cursoTutelado->id,

                'curso' => [
                    'id' => $cursoTutelado->instituicaoCurso->curso->id,
                    'nome' => $cursoTutelado->instituicaoCurso->curso->nome,
                    'descricao' => $cursoTutelado->instituicaoCurso->curso->descricao,
                    'duracao_anos' => $cursoTutelado->instituicaoCurso->duracao_anos,
                ],

                'instituicao' => [
                    'id' => $cursoTutelado->instituicaoCurso->instituicao->id,
                    'nome' => $cursoTutelado->instituicaoCurso->instituicao->nome,
                ],

                'instituicao_tutora' => [
                    'id' => $instituicao->id,
                    'nome' => $instituicao->nome,
                ],

                'classes' => $cursoTutelado->cursoClasses->map(
                    fn ($cc) => [
                        'id' => $cc->id,
                        'nome' => $cc->classe->nome,

                        'turnos' => $cc->turnos->map(
                            fn ($cct) => $cct->turno->nome
                        ),
                    ]
                ),

                'professores' => $cursoTutelado->professores->map(
                    fn ($prof) => [
                        'id' => $prof->id,
                        'vinculo_id' => $prof->pivot->id,
                        'nome' => $prof->user?->nome,
                        'tipo' => $prof->pivot->tipo,
                    ]
                ),

                'contadores' => [
                    'turmas' => $cursoTutelado->cursoClasses
                        ->flatMap(fn ($cc) => $cc->turnos)
                        ->flatMap(fn ($cct) => $cct->turmas)
                        ->count(),

                    'alunos' => $cursoTutelado->cursoClasses
                        ->flatMap(fn ($cc) => $cc->turnos)
                        ->flatMap(fn ($cct) => $cct->turmas)
                        ->flatMap(fn ($turma) => $turma->alunos)
                        ->unique('id')
                        ->count(),
                ],
            ],

            'anosLectivos' => AnoLectivo::all(),
        ]);
    }
}
