<?php

namespace App\Http\Controllers\Tenant\Colegios;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use App\Models\Central\Tutela;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Services\Tenant\TenantInstituicaoService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CursoTuteladoController extends Controller
{
    public function show(
        Request $request,
        string $colegio,
        string $cursoTutelado,
        TenantInstituicaoService $tenantInstituicaoService
    ) {
        // Instituto tutor
        $instituicao = Instituicao::findOrFail($request->query('instituicao'));

        $instituicoes = $tenantInstituicaoService->listarTodas()->keyBy('id');
        $colegioData = $instituicoes->get($colegio);

        abort_unless($colegioData, 404, 'Instituição tutelada não encontrada.');

        $tenantAtual = tenancy()->tenant;
        $tenantTutelado = Tenant::find($colegioData['tenant_id']);

        if (! $tenantTutelado) {
            abort(404, 'Tenant tutelado não encontrado.');
        }

        tenancy()->initialize($tenantTutelado);

        try {
            // Colégio tutelado
            $colegio = Instituicao::findOrFail($colegio);

            $cursoTutelado = CursoTutelado::whereKey($cursoTutelado)
                ->whereHas('instituicaoCurso', function ($query) use ($colegio) {
                    $query->where('instituicao_id', $colegio->id);
                })
                ->with([
                    'instituicaoCurso.curso:id,nome,descricao',
                    'instituicaoCurso.instituicao:id,nome',
                    'instituicaoTutora:id,nome',

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

            Tutela::query()
                ->where('instituicao_tutora_id', $instituicao->id)
                ->where('instituicao_tutelada_id', $colegio->id)
                ->where('curso_id', $cursoTutelado->instituicaoCurso->curso_id)
                ->where('ativo', true)
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
                        'id' => $cursoTutelado->instituicaoTutora->id,
                        'nome' => $cursoTutelado->instituicaoTutora->nome,
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
        } finally {
            if ($tenantAtual) {
                tenancy()->initialize($tenantAtual);
            } else {
                tenancy()->end();
            }
        }
    }
}
