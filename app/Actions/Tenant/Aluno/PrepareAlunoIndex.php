<?php

namespace App\Actions\Tenant\Aluno;

use App\Models\Central\AnoLectivo;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\User;
use App\Services\Tenant\AnoLectivo\AnoLectivoResolverService;
use App\Services\Tenant\VerificadorPropinaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Prepara os dados e permissões da lista de alunos. */
class PrepareAlunoIndex
{
    public function __construct(private readonly AnoLectivoResolverService $anoLectivoResolverService) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Request $request, User $user, VerificadorPropinaService $verificador): array
    {
        $anoLectivoId = filled($request->query('ano_lectivo_id'))
            ? $request->query('ano_lectivo_id')
            : $this->anoLectivoResolverService->obterAnoLectivoDefault();

        $cursosCoordenados = $user->professor
            ? DB::table('curso_tutelado_professor')
                ->where('professor_id', $user->professor->id)
                ->where('coordenador', true)
                ->pluck('curso_tutelado_id')
            : collect();

        $isCourseSecretary = $user->hasRole('Secretario do Curso');
        $ehCoordenador = $cursosCoordenados->isNotEmpty();
        $cursosComAcesso = $cursosCoordenados
            ->merge($isCourseSecretary ? $user->cursosSecretariados()->pluck('curso_tutelado.id') : collect())
            ->unique()
            ->values();

        $alunos = Aluno::query()
            ->select('alunos.*')
            ->leftJoin('inscricoes', 'inscricoes.id', '=', 'alunos.inscricao_id')
            ->leftJoin('candidatos', 'candidatos.id', '=', 'inscricoes.candidato_id')
            ->whereIn('alunos.situacao', ['activo', 'finalista', 'reprovado'])
            ->doAnoLectivo($anoLectivoId)
            ->search($request->string('search')->toString())
            ->whereHas('inscricao', fn ($query) => $query->where('status', '!=', 'cancelado'))
            ->with([
                'inscricao.candidato:id,nome,bi,email,telefone',
                'user:id,avatar',
                'inscricao.cursoClasseTurno.turno:id,nome',
                'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
                'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
                'turmas' => fn ($query) => $query->wherePivot('activo', true)
                    ->where('turmas.ano_lectivo_id', $anoLectivoId)
                    ->with([
                        'cursoClasseTurno.cursoClasse.classe:id,nome',
                        'anoLectivo:id,nome',
                    ]),
            ])
            ->when(
                $user->hasAnyRole(['Director', 'Subdirector', 'Secretaria']),
                fn ($query) => $query->whereHas(
                    'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                    fn ($query) => $query->where('instituicao_id', $user->instituicao_id)
                )
            )
            ->when(
                $isCourseSecretary || $ehCoordenador,
                fn ($query) => $query->whereHas(
                    'inscricao.cursoClasseTurno.cursoClasse',
                    fn ($query) => $query->whereIn('curso_tutelado_id', $cursosComAcesso)
                )
            )
            ->when(
                ! $ehCoordenador && ! $isCourseSecretary && $user->hasRole('Professor'),
                fn ($query) => $query->whereHas(
                    'turmas',
                    fn ($query) => $query->whereIn('turmas.id', $user->professor->turmas()->pluck('turmas.id'))
                )
            )
            ->orderBy('candidatos.nome')
            ->orderBy('alunos.id')
            ->paginate(10)
            ->withQueryString();

        $alunos->getCollection()->transform(function ($aluno) use ($user) {
            $aluno->can = [
                'view' => $user->can('view', $aluno),
                'update' => $user->can('update', $aluno),
            ];

            return $aluno;
        });

        return [
            'alunos' => $alunos->through(function ($aluno) use ($verificador) {
                $turmaNoAno = $aluno->turmas->first();
                $status = $turmaNoAno
                    ? ($verificador->estaEmDia($aluno) ? 'pagou' : 'atrasado')
                    : 'sem_turma';

                Log::debug('[AlunoController] status calculado', [
                    'aluno_id' => $aluno->id,
                    'nome' => $aluno->inscricao?->candidato?->nome,
                    'propina_status' => $status,
                ]);

                return [
                    'id' => $aluno->id,
                    'matricula' => $aluno->matricula,
                    'numero_processo' => $aluno->numero_processo,
                    'nome' => $aluno->inscricao?->candidato?->nome,
                    'bi' => $aluno->inscricao?->candidato?->bi,
                    'email' => $aluno->inscricao?->candidato?->email,
                    'telefone' => $aluno->inscricao?->candidato?->telefone,
                    'avatar' => $aluno->user?->avatar_url,
                    'curso' => $aluno->inscricao?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
                    'instituicao' => $aluno->inscricao?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->instituicao?->nome,
                    'turno' => $aluno->inscricao?->cursoClasseTurno?->turno?->nome,
                    'turma' => $turmaNoAno?->nome ?? 'Sem turma',
                    'classe' => $turmaNoAno?->cursoClasseTurno?->cursoClasse?->classe?->nome,
                    'ano_lectivo' => $turmaNoAno?->anoLectivo?->nome,
                    'propina_status' => $status,
                    'can' => $aluno->can,
                ];
            }),
            'anoLectivoId' => $anoLectivoId,
            'filters' => $request->only('search'),
            'anosLectivos' => AnoLectivo::query()
                ->select('id', 'nome')
                ->orderByDesc('data_inicio')
                ->get(),
            'can' => [
                'create' => $user->can('create', Aluno::class),
            ],
        ];
    }
}
