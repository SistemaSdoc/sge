<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCursoTuteladoRequest;
use App\Http\Resources\CursoTutelado\CursoTuteladoResourceEdit;
use App\Http\Resources\CursoTutelado\CursoTuteladoResourceShow;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\NivelEnsino;
use App\Services\Tenant\AnoLectivo\AnoLectivoResolverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CursoTuteladoController extends Controller
{
    public function __construct(private readonly AnoLectivoResolverService $anoLectivoResolverService) {}

    public function index(Instituicao $instituicao)
    {
        $cursos = $instituicao->instituicaoCursos()
            ->with(['curso:id,nome', 'cursoTutelado.instituicaoTutora:id,nome'])
            ->paginate(10)
            ->through(fn ($instituicaoCurso) => [
                'id' => $instituicaoCurso->cursoTutelado->id,
                'nome' => $instituicaoCurso->curso->nome,
                'instituicao_tutora' => $instituicaoCurso->cursoTutelado?->instituicaoTutora?->nome,
                'can' => [
                    'view' => Auth::user()->can('view', $instituicaoCurso->cursoTutelado),
                    'update' => Auth::user()->can('update', $instituicaoCurso->cursoTutelado),
                    'delete' => Auth::user()->can('delete', $instituicaoCurso->cursoTutelado),
                ],
            ]);

        return Inertia::render('tenant/cursos-tutelados/index', [
            'cursos' => $cursos,
            'instituicao' => $instituicao->only('id'),
            'can' => [
                'create_curso' => Auth::user()->can('create', CursoTutelado::class),
            ],
        ]);
    }

    public function create(Instituicao $instituicao)
    {
          Gate::authorize('create', CursoTutelado::class);

    $classes = Classe::select('id', 'nome')
        ->orderBy('nome')
        ->get();

    $niveisEnsino = NivelEnsino::select('id', 'nome')
        ->orderBy('nome')
        ->get();

    $cursosJaAssociadosQuery = InstituicaoCurso::query();

    if ($instituicao->tipo === 'instituto') {
        // Instituto: esconde cursos já associados a qualquer colégio
        $cursosJaAssociadosQuery->whereHas('instituicao', fn ($q) => $q->where('tipo', 'colegio'));
    } else {
        // Colégio: esconde só os cursos já associados a este próprio colégio
        // (cursos de institutos continuam a aparecer)
        $cursosJaAssociadosQuery->where('instituicao_id', $instituicao->id);
    }

    $cursosJaAssociados = $cursosJaAssociadosQuery->pluck('curso_id');

    $cursos = Curso::select('id', 'nome')
        ->whereNotIn('id', $cursosJaAssociados)
        ->orderBy('nome')
        ->get();

    return Inertia::render('tenant/cursos-tutelados/create', [
        'instituicao' => $instituicao->only('id'),
        'classes' => $classes,
        'cursos' => $cursos,
        'niveisEnsino' => $niveisEnsino,
    ]);
    }

    public function store(StoreCursoTuteladoRequest $request, Instituicao $instituicao)
    {
        Gate::authorize('create', CursoTutelado::class);

        $validated = $request->validated();

        // Obter ou criar curso
        $curso = isset($validated['curso_id'])
            ? Curso::findOrFail($validated['curso_id'])
            : Curso::firstOrCreate(
                ['nome' => $validated['nome']],
                ['duracao_anos' => $validated['duracao_anos']]
            );

        // Verificar duplicado antes de entrar na transação
        if (
            InstituicaoCurso::where('instituicao_id', $instituicao->id)
                ->where('curso_id', $curso->id)
                ->exists()
        ) {
            return back()->withErrors([
                'curso_id' => 'Esta instituição já tem este curso associado.',
            ]);
        }

        DB::transaction(function () use ($instituicao, $curso, $validated) {
            $instituicaoCurso = InstituicaoCurso::create([
                'curso_id' => $curso->id,
                'instituicao_id' => $instituicao->id,
                'duracao_anos' => $validated['duracao_anos'] ?? $curso->duracao_anos,
            ]);

            $cursoTutelado = $instituicaoCurso->cursoTutelado()->create([
                'instituicao_tutora_id' => $instituicao->id,
            ]);

            // Insert bulk — uma única query independentemente do nº de classes
            $now = now();
            CursoClasse::insert(
                collect($validated['classe_ids'])->map(fn ($classeId) => [
                    'id' => (string) Str::uuid7(),
                    'curso_tutelado_id' => $cursoTutelado->id,
                    'classe_id' => $classeId,
                    'nivel_ensino_id' => $validated['nivel_ensino_id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        });

        return to_route('cursos-tutelados.index', $instituicao)->with('toast', [
            'type' => 'success',
            'message' => 'Curso criado com sucesso!',
        ]);
    }

    public function show(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        Gate::authorize('view', $cursoTutelado);

        $anoLectivoId = filled(request('ano_lectivo_id'))
            ? request('ano_lectivo_id')
            : $this->anoLectivoResolverService->obterAnoLectivoDefault();

        $cursoTutelado->load([
            'instituicaoCurso.curso:id,nome,descricao',
            'instituicaoCurso.instituicao:id,nome',
            'instituicaoTutora:id,nome',
            'cursoClasses.classe:id,nome',
            'cursoClasses.turnos.turno:id,nome',
            'cursoClasses.turnos' => function ($query) use ($anoLectivoId) {
                $query->with([
                    'turmas' => fn ($q) => $q->where('ano_lectivo_id', $anoLectivoId),
                    'turmas.cursoClasseTurno.turno:id,nome',
                    'turmas.cursoClasseTurno.cursoClasse.classe:id,nome',
                    'classeTurnoDisciplinas.professores',
                    'classeTurnoDisciplinas',
                ]);
            },
            'professores.user:id,nome',
        ]);

        return Inertia::render('tenant/cursos-tutelados/show', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
            ],
            'cursoTutelado' => (new CursoTuteladoResourceShow($cursoTutelado))->resolve(),
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => AnoLectivo::query()
                ->select('id', 'nome')
                ->orderByDesc('data_inicio')
                ->get(),
            'can' => [
                'instituicao' => [
                    'view' => Auth::user()->can('view', $instituicao),
                ],
            ],
        ]);
    }

    public function edit(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        Gate::authorize('update', $cursoTutelado);

        $cursoTutelado->load([
            'instituicaoCurso.curso:id,nome',
            'instituicaoCurso',
            'instituicaoTutora:id,nome',
            'classes:id',
        ]);

        $classes = Classe::select('id', 'nome')
            ->orderBy('nome')
            ->get();

        // Só faz sentido para colégios — institutos não passam tutela
        $instituicoes = collect();

        if ($instituicao->tipo === 'colegio') {
            $cursoId = $cursoTutelado->instituicaoCurso->curso_id;

            $instituicoes = Instituicao::select('id', 'nome')
                ->where(function ($q) use ($cursoId) {
                    // Institutos que têm o curso
                    $q->where('tipo', 'instituto')
                        ->whereHas('instituicaoCursos', fn ($q) => $q->where('curso_id', $cursoId));
                })
                ->orWhere('id', $cursoTutelado->instituicao_tutora_id) // Garante que a tutora actual aparece sempre
                ->orderBy('nome')
                ->get();
        } else {
            $instituicoes = Instituicao::select('id', 'nome')
                ->where('id', $cursoTutelado->instituicao_tutora_id)
                ->get();
        }

        return Inertia::render('tenant/cursos-tutelados/edit', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
                'tipo' => $instituicao->tipo,
            ],
            'cursoTutelado' => (new CursoTuteladoResourceEdit($cursoTutelado))->resolve(),
            'classes' => $classes,
            'instituicoes' => $instituicoes,
        ]);
    }

    public function update(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        Gate::authorize('update', $cursoTutelado);

        $validated = request()->validate([
            'instituicao_tutora_id' => ['required', 'string', 'exists:instituicoes,id'],
            'duracao_anos' => ['required', 'integer', 'min:1', 'max:10'],
            'classes' => ['required', 'array', 'min:1'],
            'classes.*' => ['string', 'exists:classes,id'],
        ]);

        DB::transaction(function () use ($validated, $cursoTutelado) {
            $cursoTutelado->update([
                'instituicao_tutora_id' => $validated['instituicao_tutora_id'],
            ]);

            $cursoTutelado->instituicaoCurso->update([
                'duracao_anos' => $validated['duracao_anos'],
            ]);

            $cursoTutelado->classes()->sync($validated['classes']);
        });

        return to_route('cursos-tutelados.index', $instituicao)->with('toast', [
            'type' => 'success',
            'message' => 'Curso tutelado atualizado com sucesso!',
        ]);
    }

    public function destroy(Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        Gate::authorize('update', $cursoTutelado);

        $temTurmas = $cursoTutelado->cursoClasses
            ->flatMap(fn ($cc) => $cc->turnos)
            ->isNotEmpty();

        if ($temTurmas) {
            abort(422, 'Não é possível remover um curso que tem turmas associadas.');
        }

        $cursoTutelado->delete();

        return response()->noContent();
    }

    public function uploadCriteriosPap(Request $request, Instituicao $instituicao, CursoTutelado $cursoTutelado)
    {
        Gate::authorize('update', $cursoTutelado);

        $request->validate([
            'criterios_pap' => [($cursoTutelado->criterios_pap_path ? 'nullable' : 'required'), 'file', 'mimes:pdf', 'max:10240'],
            'manual_pt' => [($cursoTutelado->manual_pt_path ? 'nullable' : 'required'), 'file', 'mimes:pdf', 'max:10240'],
        ]);

        if ($request->hasFile('criterios_pap')) {
            if ($cursoTutelado->criterios_pap_path) {
                Storage::disk('public')->delete($cursoTutelado->criterios_pap_path);
            }
            $cursoTutelado->criterios_pap_path = $request->file('criterios_pap')
                ->store("cursos-tutelados/{$cursoTutelado->id}/criterios-pap", 'public');
        }

        if ($request->hasFile('manual_pt')) {
            if ($cursoTutelado->manual_pt_path) {
                Storage::disk('public')->delete($cursoTutelado->manual_pt_path);
            }
            $cursoTutelado->manual_pt_path = $request->file('manual_pt')
                ->store("cursos-tutelados/{$cursoTutelado->id}/manual-pt", 'public');
        }

        $cursoTutelado->save();

        return redirect()->route('cursos-tutelados.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
        ])->with('toast', [
            'type' => 'success',
            'message' => 'Documentos actualizados com sucesso.',
        ]);
    }
}
