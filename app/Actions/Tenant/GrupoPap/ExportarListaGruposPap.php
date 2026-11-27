<?php

namespace App\Actions\Tenant\GrupoPap;

use App\Exports\GrupoPap\ExportarListaGruposPapExcel;
use App\Exports\GrupoPap\ExportarListaGruposPapPdf;
use App\Models\Central\AnoLectivo;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;
use App\Services\Tenant\AnoLectivo\AnoLectivoResolverService;
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
        Request $request,
        GrupoPapViewService $grupoPapViewService,
        AnoLectivoResolverService $anoLectivoResolverService,
        ExportarListaGruposPapPdf $exportarListaGruposPapPdf,
    ): BinaryFileResponse {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        Gate::forUser($user)->authorize('viewAny', GrupoPap::class);

        $filters = $request->validate([
            'instituicao_id' => ['required', 'string'],
            'curso_id' => ['required', 'string'],
            'ano_lectivo_id' => ['required', 'string'],
            'search' => ['nullable', 'string', 'max:255'],
            'formato' => ['nullable', 'in:pdf,excel'],
        ]);

        $canSelectInstitution = ($user->hasRole('Secretario do Curso')
            && $user->instituicao?->tipo === 'instituto')
            || $user->can('selecionarInstituicao', GrupoPap::class);

        $institutionId = (string) $filters['instituicao_id'];

        abort_unless($canSelectInstitution || $institutionId === (string) $user->instituicao_id, 403);

        $institution = $grupoPapViewService->papInstitutions($user)->firstWhere('id', $institutionId);

        abort_unless($institution !== null, 404);

        $course = $grupoPapViewService->courseFilterOptions($grupoPapViewService->tutoredCourses($user, $institutionId))
            ->firstWhere('id', (string) $filters['curso_id']);

        abort_unless($course !== null, 404);

        $canSelectSchoolYear = $user->hasRole('Secretario do Curso') || $user->can('selecionarAnoLectivo', GrupoPap::class);

        $schoolYearId = (string) $filters['ano_lectivo_id'];

        if (! $canSelectSchoolYear) {
            $defaultSchoolYearId = $anoLectivoResolverService->obterAnoLectivoDefault();

            abort_unless($defaultSchoolYearId !== null && $schoolYearId === (string) $defaultSchoolYearId, 403);
        }

        $schoolYear = AnoLectivo::query()->findOrFail($schoolYearId);

        $groups = $grupoPapViewService->index(
            $user,
            $schoolYearId,
            $institutionId,
            (string) $filters['curso_id'],
            $filters['search'] ?? null,
            true,
        );

        $className = $groups->pluck('turma.cursoClasseTurno.cursoClasse.classe.nome')
            ->filter()
            ->unique()
            ->sort()
            ->implode(' / ') ?: '13ª';

        if (($filters['formato'] ?? 'pdf') === 'excel') {
            $fileName = 'lista_grupos_pap_'.Str::slug(implode('_', [
                $institution['nome'],
                $course['nome'],
                $schoolYear->nome,
            ])).'.xlsx';

            return Excel::download(new ExportarListaGruposPapExcel(
                $groups,
                $institution['nome'],
                $course['nome'],
                $className,
                $schoolYear->nome,
            ), $fileName);
        }

        return $exportarListaGruposPapPdf->gerar(
            $groups,
            $institution['nome'],
            $course['nome'],
            $className,
            $schoolYear->nome,
        );
    }
}
