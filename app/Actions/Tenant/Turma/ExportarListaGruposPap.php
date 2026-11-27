<?php

namespace App\Actions\Tenant\Turma;

use App\Exports\GrupoPap\ExportarListaGruposPapExcel;
use App\Exports\GrupoPap\ExportarListaGruposPapPdf;
use App\Models\Central\AnoLectivo;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\GrupoPap\GrupoPapViewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportarListaGruposPap
{
    public function __invoke(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        Request $request,
        GrupoPapViewService $grupoPapViewService,
        ExportarListaGruposPapPdf $exportarListaGruposPapPdf,
    ): BinaryFileResponse {
        $filters = $request->validate([
            'formato' => ['nullable', 'in:pdf,excel'],
        ]);

        abort_unless(
            (string) $cursoTutelado->instituicaoCurso?->instituicao_id === (string) $instituicao->getKey()
                && (string) $cursoClasse->curso_tutelado_id === (string) $cursoTutelado->getKey()
                && (string) $cursoClasseTurno->curso_classe_id === (string) $cursoClasse->getKey()
                && (string) $turma->curso_classe_turno_id === (string) $cursoClasseTurno->getKey(),
            404,
        );

        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        Gate::forUser($user)->authorize('viewAny', GrupoPap::class);

        Gate::forUser($user)->authorize('view', $turma);

        $cursoTutelado->loadMissing('instituicaoCurso.instituicao', 'instituicaoCurso.curso');

        $cursoClasse->loadMissing('classe');

        $instituicaoCurso = $cursoTutelado->instituicaoCurso;

        $instituicaoAssociada = $instituicaoCurso?->instituicao;

        $curso = $instituicaoCurso?->curso;

        $classe = $cursoClasse->classe;

        abort_unless(
            $instituicaoAssociada !== null
                && $curso !== null
                && $classe !== null
                && $turma->ano_lectivo_id !== null,
            404,
        );

        $anoLectivo = AnoLectivo::query()->findOrFail($turma->ano_lectivo_id);

        $grupos = $grupoPapViewService->groupsForTurma(
            $user,
            $turma,
            (string) $turma->ano_lectivo_id,
            (string) $instituicaoAssociada->getKey(),
            (string) $curso->getKey(),
        );

        if (($filters['formato'] ?? 'pdf') === 'excel') {
            $fileName = 'lista_grupos_pap_'.Str::slug(implode('_', [
                $instituicaoAssociada->nome,
                $curso->nome,
                $anoLectivo->nome,
            ])).'.xlsx';

            return Excel::download(new ExportarListaGruposPapExcel(
                $grupos,
                $instituicaoAssociada->nome,
                $curso->nome,
                $classe->nome,
                $anoLectivo->nome,
            ), $fileName);
        }

        return $exportarListaGruposPapPdf->gerar(
            $grupos,
            $instituicaoAssociada->nome,
            $curso->nome,
            $classe->nome,
            $anoLectivo->nome,
        );
    }
}
