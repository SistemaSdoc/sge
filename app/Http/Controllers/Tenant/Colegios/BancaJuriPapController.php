<?php

namespace App\Http\Controllers\Tenant\Colegios;

use App\Actions\Tenant\BancaJuriPap\PrepareBancaJuriPapForm;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\BancaJuriPap\StoreRequest;
use App\Http\Requests\Tenant\BancaJuriPap\UpdateRequest;
use App\Models\Central\AnoLectivo;
use App\Models\Central\Tenant;
use App\Models\Tenant\BancaJuriPap;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Notifications\Pap\JuradoAdicionadoBancaNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class BancaJuriPapController extends Controller
{
    public function create(
        Request $request,
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap,
    ) {
        /** @var User $user */
        $user = $request->attributes->get('cross_tenant_tutor') ?? Auth::guard('tenant')->user();
        $contexto = $this->contexto($colegio, $cursoTutelado, $cursoClasse, $cursoClasseTurno, $turma, $grupoPap);

        abort_unless(
            $request->attributes->get('cross_tenant_can_create_banca') === true
            && $contexto['grupo']->data_defesa !== null,
            403,
        );

        $professores = app(PrepareBancaJuriPapForm::class)
            ->handle($contexto['turma'], $contexto['curso'], $contexto['grupo'])['professores'];

        return Inertia::render('tenant/colegio/cursos-tutelados/classes/turnos/turmas/pap/banca/create', [
            'instituicao' => ['id' => $user->instituicao_id],
            'colegio' => $contexto['colegio']->only('id', 'nome'),
            'cursoTutelado' => $contexto['curso']->only('id'),
            'cursoClasse' => $contexto['classe']->only('id'),
            'cursoClasseTurno' => $contexto['turno']->only('id'),
            'turma' => $contexto['turma']->only('id', 'nome'),
            'anoLectivoId' => $contexto['turma']->ano_lectivo_id,
            'anosLectivos' => AnoLectivo::all(),
            'grupoPap' => $contexto['grupo']->only('id', 'nome_grupo'),
            'professores' => $professores,
            'funcoes' => ['Presidente', 'Vogal 1', 'Vogal 2'],
        ]);
    }

    public function store(
        StoreRequest $request,
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap,
    ) {
        $contexto = $this->contexto($colegio, $cursoTutelado, $cursoClasse, $cursoClasseTurno, $turma, $grupoPap);

        abort_unless(
            $request->attributes->get('cross_tenant_can_create_banca') === true
            && $contexto['grupo']->data_defesa !== null,
            403,
        );

        $professor = $this->professorSeleccionado($request, $contexto);
        $externo = $professor['tenant_id'] !== null;

        $banca = BancaJuriPap::create([
            'grupo_pap_id' => $contexto['grupo']->id,
            'professor_id' => $externo ? null : $professor['id'],
            'professor_externo_id' => $externo ? $professor['id'] : null,
            'professor_externo_tenant_id' => $externo ? $professor['tenant_id'] : null,
            'funcao' => $request->funcao,
        ]);

        $jurado = $this->utilizadorDoProfessor($professor);

        if ($jurado) {
            $jurado->notify(new JuradoAdicionadoBancaNotification($contexto['grupo'], $banca));
        }

        return to_route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', compact(
            'colegio',
            'cursoTutelado',
            'cursoClasse',
            'cursoClasseTurno',
            'turma',
            'grupoPap',
        ));
    }

    public function destroy(
        Request $request,
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap,
        string $bancaJuriPap,
    ) {
        abort_unless($request->attributes->get('cross_tenant_can_delete_banca') === true, 403);

        BancaJuriPap::query()
            ->whereKey($bancaJuriPap)
            ->where('grupo_pap_id', $grupoPap)
            ->firstOrFail()
            ->delete();

        return to_route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', compact(
            'colegio',
            'cursoTutelado',
            'cursoClasse',
            'cursoClasseTurno',
            'turma',
            'grupoPap',
        ));
    }

    public function edit(
        Request $request,
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap,
        string $bancaJuriPap,
    ) {
        $contexto = $this->contexto($colegio, $cursoTutelado, $cursoClasse, $cursoClasseTurno, $turma, $grupoPap);
        abort_unless($request->attributes->get('cross_tenant_can_update_banca') === true, 403);
        $banca = BancaJuriPap::query()->whereKey($bancaJuriPap)->where('grupo_pap_id', $grupoPap)->firstOrFail();
        $professores = app(PrepareBancaJuriPapForm::class)
            ->handle($contexto['turma'], $contexto['curso'], $contexto['grupo'], $banca)['professores'];

        return Inertia::render('tenant/colegio/cursos-tutelados/classes/turnos/turmas/pap/banca/edit', [
            'instituicao' => ['id' => $request->attributes->get('cross_tenant_tutor')?->instituicao_id],
            'colegio' => $contexto['colegio']->only('id', 'nome'),
            'cursoTutelado' => $contexto['curso']->only('id'),
            'cursoClasse' => $contexto['classe']->only('id'),
            'cursoClasseTurno' => $contexto['turno']->only('id'),
            'turma' => $contexto['turma']->only('id', 'nome'),
            'grupoPap' => $contexto['grupo']->only('id', 'nome_grupo'),
            'bancaJuriPap' => [
                'id' => $banca->id,
                'professor_id' => $banca->professor_externo_id ?? $banca->professor_id,
                'professor_externo_tenant_id' => $banca->professor_externo_tenant_id,
                'funcao' => $banca->funcao,
            ],
            'professores' => $professores,
            'funcoes' => ['Presidente', 'Vogal 1', 'Vogal 2'],
        ]);
    }

    public function update(
        UpdateRequest $request,
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap,
        string $bancaJuriPap,
    ) {
        $contexto = $this->contexto($colegio, $cursoTutelado, $cursoClasse, $cursoClasseTurno, $turma, $grupoPap);

        abort_unless($request->attributes->get('cross_tenant_can_update_banca') === true, 403);

        $banca = BancaJuriPap::query()->whereKey($bancaJuriPap)->where('grupo_pap_id', $grupoPap)->firstOrFail();
        $professor = $this->professorSeleccionado($request, $contexto, $banca);
        $externo = $professor['tenant_id'] !== null;

        $banca->update([
            'professor_id' => $externo ? null : $professor['id'],
            'professor_externo_id' => $externo ? $professor['id'] : null,
            'professor_externo_tenant_id' => $externo ? $professor['tenant_id'] : null,
            'funcao' => $request->funcao,
        ]);

        $jurado = $this->utilizadorDoProfessor($professor);

        if ($jurado) {
            $jurado->notify(new JuradoAdicionadoBancaNotification($contexto['grupo'], $banca));
        }

        return to_route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', compact(
            'colegio',
            'cursoTutelado',
            'cursoClasse',
            'cursoClasseTurno',
            'turma',
            'grupoPap',
        ));
    }

    /**
     * @return array{colegio: Instituicao, curso: CursoTutelado, classe: CursoClasse, turno: CursoClasseTurno, turma: Turma, grupo: GrupoPap}
     */
    private function contexto(
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap,
    ): array {
        $colegioModel = Instituicao::findOrFail($colegio);
        $curso = CursoTutelado::query()->whereKey($cursoTutelado)
            ->whereHas('instituicaoCurso', fn ($query) => $query->where('instituicao_id', $colegioModel->id))
            ->firstOrFail();
        $classe = CursoClasse::query()->whereKey($cursoClasse)->where('curso_tutelado_id', $curso->id)->firstOrFail();
        $turno = CursoClasseTurno::query()->whereKey($cursoClasseTurno)->where('curso_classe_id', $classe->id)->firstOrFail();
        $turmaModel = Turma::query()->whereKey($turma)->where('curso_classe_turno_id', $turno->id)->firstOrFail();
        $grupo = GrupoPap::query()->whereKey($grupoPap)->where('turma_id', $turmaModel->id)->firstOrFail();

        return [
            'colegio' => $colegioModel,
            'curso' => $curso,
            'classe' => $classe,
            'turno' => $turno,
            'turma' => $turmaModel,
            'grupo' => $grupo,
        ];
    }

    /**
     * @param  array{colegio: Instituicao, curso: CursoTutelado, classe: CursoClasse, turno: CursoClasseTurno, turma: Turma, grupo: GrupoPap}  $contexto
     * @return array{id: string, nome: string, externo: bool, tenant_id: ?string}
     */
    private function professorSeleccionado(Request $request, array $contexto, ?BancaJuriPap $banca = null): array
    {
        $professores = app(PrepareBancaJuriPapForm::class)
            ->handle($contexto['turma'], $contexto['curso'], $contexto['grupo'], $banca)['professores'];

        $professor = $professores->first(fn (array $item): bool => (string) $item['id'] === (string) $request->input('professor_id')
            && (string) ($item['tenant_id'] ?? '') === (string) $request->input('professor_externo_tenant_id', '')
        );

        if ($professor === null) {
            throw ValidationException::withMessages([
                'professor_id' => 'O professor seleccionado não pertence ao curso tutelado ou já está na banca.',
            ]);
        }

        return $professor;
    }

    /**
     * @param  array{id: string, nome: string, externo: bool, tenant_id: ?string}  $professor
     */
    private function utilizadorDoProfessor(array $professor): ?User
    {
        if ($professor['tenant_id'] === null) {
            return Professor::with('user')->find($professor['id'])?->user;
        }

        $tenant = Tenant::find($professor['tenant_id']);

        if ($tenant === null) {
            return null;
        }

        return $tenant->run(fn (): ?User => Professor::with('user')->find($professor['id'])?->user);
    }
}
