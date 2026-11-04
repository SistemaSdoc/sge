<?php

namespace App\Actions\Tenant\GrupoPap;

use App\Models\Central\AnoLectivo;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;
use App\Services\Tenant\AnoLectivo\AnoLectivoResolverService;
use App\Services\Tenant\GrupoPap\GrupoPapViewService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Writer\HTML;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ExportarListaGruposPap
{
    public function __invoke(
        Request $request,
        GrupoPapViewService $grupoPapViewService,
        AnoLectivoResolverService $anoLectivoResolverService,
    ): BinaryFileResponse {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        Gate::forUser($user)->authorize('viewAny', GrupoPap::class);

        $filters = $request->validate([
            'instituicao_id' => ['required', 'string'],
            'curso_id' => ['required', 'string'],
            'ano_lectivo_id' => ['required', 'string'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $canSelectInstitution = ($user->hasRole('Secretario do Curso')
                && $user->instituicao?->tipo === 'instituto')
            || $user->can('selecionarInstituicao', GrupoPap::class);
        $institutionId = (string) $filters['instituicao_id'];

        abort_unless(
            $canSelectInstitution || $institutionId === (string) $user->instituicao_id,
            403,
        );

        $institution = $grupoPapViewService->papInstitutions($user)
            ->firstWhere('id', $institutionId);
        abort_unless($institution !== null, 404);

        $course = $grupoPapViewService->courseFilterOptions(
            $grupoPapViewService->tutoredCourses($user, $institutionId),
        )->firstWhere('id', (string) $filters['curso_id']);
        abort_unless($course !== null, 404);

        $canSelectSchoolYear = $user->hasRole('Secretario do Curso')
            || $user->can('selecionarAnoLectivo', GrupoPap::class);
        $schoolYearId = (string) $filters['ano_lectivo_id'];
        if (! $canSelectSchoolYear) {
            $defaultSchoolYearId = $anoLectivoResolverService->obterAnoLectivoDefault();
            abort_unless(
                $defaultSchoolYearId !== null && $schoolYearId === (string) $defaultSchoolYearId,
                403,
            );
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

        $templatePath = resource_path('templates/lista-grupos-pap-export.docx');
        abort_unless(is_file($templatePath), 500, 'O template da lista PAP não está disponível.');

        $temporaryDirectory = storage_path('app/private');
        if (! is_dir($temporaryDirectory)
            && ! mkdir($temporaryDirectory, 0775, true)
            && ! is_dir($temporaryDirectory)) {
            throw new \RuntimeException('Não foi possível preparar a pasta temporária da exportação.');
        }

        if (! is_writable($temporaryDirectory)) {
            throw new \RuntimeException('A pasta temporária da exportação não permite escrita.');
        }

        $outputDirectory = $temporaryDirectory;
        $temporaryBasePath = tempnam($outputDirectory, 'pap-grupos-');
        if ($temporaryBasePath === false) {
            throw new \RuntimeException('Não foi possível preparar o ficheiro de exportação.');
        }

        $temporaryPath = $temporaryBasePath.'.docx';
        if (! rename($temporaryBasePath, $temporaryPath)) {
            unlink($temporaryBasePath);

            throw new \RuntimeException('Não foi possível preparar o ficheiro Word da exportação.');
        }
        $pdfPath = substr($temporaryPath, 0, -5).'.pdf';
        $previousPhpWordTempDirectory = Settings::getTempDir();
        $previousOutputEscaping = Settings::isOutputEscapingEnabled();

        try {
            Settings::setTempDir($temporaryDirectory);
            Settings::setOutputEscapingEnabled(true);
            $template = new TemplateProcessor($templatePath);
            $values = [
                'instituicao' => $institution['nome'],
                'curso' => $course['nome'],
                'classe' => $groups
                    ->pluck('turma.cursoClasseTurno.cursoClasse.classe.nome')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->implode(' / ') ?: '13ª',
                'ano_lectivo' => $schoolYear->nome,
            ];

            if ($groups->isEmpty()) {
                $template->cloneBlock('grupo', 0, true, true);
            } else {
                $template->cloneBlock('grupo', $groups->count(), true, true);

                foreach ($groups->values() as $groupIndex => $grupoPap) {
                    $macroIndex = $groupIndex + 1;
                    $tutor = $grupoPap->professor?->user;
                    $values["tema_grupo#{$macroIndex}"] = (string) ($grupoPap->tema_grupo ?? '');
                    $values["nome_grupo#{$macroIndex}"] = (string) ($grupoPap->nome_grupo ?? '');
                    $values["caso_estudo#{$macroIndex}"] = (string) ($grupoPap->estudo_caso ?? '');
                    $values["tutor_nome#{$macroIndex}"] = (string) ($tutor?->nome ?? '');
                    $values["tutor_email#{$macroIndex}"] = (string) ($tutor?->email ?? '');
                    $values["tutor_telefone#{$macroIndex}"] = (string) ($tutor?->telefone ?? '');

                    $students = $grupoPap->elementos
                        ->map(fn ($elementoGrupoPap) => $elementoGrupoPap->aluno)
                        ->filter()
                        ->values();

                    if ($students->isEmpty()) {
                        $template->deleteRow("aluno_nome#{$macroIndex}");

                        continue;
                    }

                    $template->cloneRow("aluno_nome#{$macroIndex}", $students->count());

                    foreach ($students as $studentIndex => $aluno) {
                        $studentMacroIndex = $studentIndex + 1;
                        $studentMacro = "#{$macroIndex}#{$studentMacroIndex}";
                        $candidate = $aluno->inscricao?->candidato;
                        $studentUser = $aluno->user;
                        $values["aluno_nome{$studentMacro}"] = (string) ($candidate?->nome ?? '');
                        $values["aluno_bi{$studentMacro}"] = (string) ($candidate?->bi ?? $studentUser?->bi ?? '');
                        $values["numero_estudante{$studentMacro}"] = (string) ($candidate?->numero_estudante ?? $aluno->numero_processo ?? '');
                        $values["aluno_telefone{$studentMacro}"] = (string) ($candidate?->telefone ?? $studentUser?->telefone ?? '');
                        $values["aluno_email{$studentMacro}"] = (string) ($candidate?->email ?? $studentUser?->email ?? '');
                    }
                }
            }

            $template->setValues($values);
            $unresolvedMacros = $template->getVariables();
            if ($unresolvedMacros !== []) {
                throw new \RuntimeException(
                    'O template contém campos sem preenchimento: '.implode(', ', $unresolvedMacros),
                );
            }
            $template->saveAs($temporaryPath);
            $this->convertToPdf($temporaryPath, $pdfPath, $outputDirectory);
        } catch (Throwable $exception) {
            foreach ([$temporaryPath, $pdfPath] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }

            throw $exception;
        } finally {
            Settings::setTempDir($previousPhpWordTempDirectory);
            Settings::setOutputEscapingEnabled($previousOutputEscaping);
        }

        unlink($temporaryPath);

        $fileName = 'lista_grupos_pap_'.Str::slug(implode('_', [
            $institution['nome'],
            $course['nome'],
            $schoolYear->nome,
        ])).'.pdf';

        return response()
            ->download($pdfPath, $fileName, [
                'Content-Type' => 'application/pdf',
            ])
            ->deleteFileAfterSend(true);
    }

    private function convertToPdf(string $wordPath, string $pdfPath, string $outputDirectory): void
    {
        $cacheDirectory = $outputDirectory.'/dompdf-cache-'.Str::uuid();
        if (! mkdir($cacheDirectory, 0775, true) && ! is_dir($cacheDirectory)) {
            throw new \RuntimeException('Não foi possível preparar o cache do conversor PDF.');
        }

        try {
            $phpWord = IOFactory::load($wordPath);
            $htmlWriter = new HTML($phpWord);
            $html = $htmlWriter->getContent();
            $document = new \DOMDocument('1.0', 'UTF-8');
            if (! $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
                throw new \RuntimeException('Não foi possível preparar o documento para PDF.');
            }

            $xpath = new \DOMXPath($document);
            foreach ($xpath->query('//text()') as $textNode) {
                if ($textNode instanceof \DOMText) {
                    $textNode->data = html_entity_decode(
                        $textNode->data,
                        ENT_QUOTES | ENT_HTML5,
                        'UTF-8',
                    );
                }
            }

            $options = new Options;
            $options->setTempDir($cacheDirectory);
            $options->setFontCache($cacheDirectory);
            $options->setChroot([base_path(), $outputDirectory]);
            $options->setIsRemoteEnabled(false);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($document->saveHTML(), 'UTF-8');
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            $pdfContent = $dompdf->output();
            if (! str_starts_with($pdfContent, '%PDF-')
                || file_put_contents($pdfPath, $pdfContent, LOCK_EX) === false) {
                throw new \RuntimeException('Não foi possível gravar o PDF gerado.');
            }
        } finally {
            File::deleteDirectory($cacheDirectory);
        }
    }
}
