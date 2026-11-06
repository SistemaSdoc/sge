<?php

namespace App\Exports\GrupoPap;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;

class ExportarListaGruposPapPdf
{
    public function gerar(
        Collection $groups,
        string $institutionName,
        string $courseName,
        string $className,
        string $schoolYearName,
    ): BinaryFileResponse {
        $templatePath = resource_path('templates/lista-grupos-pap/template.docx');

        abort_unless(is_file($templatePath), 500, 'O template da lista PAP não está disponível.');

        $outputDirectory = storage_path('app/private');

        if (
            ! is_dir($outputDirectory)
            && ! mkdir($outputDirectory, 0775, true)
            && ! is_dir($outputDirectory)
        ) {
            throw new \RuntimeException('Não foi possível preparar a pasta temporária da exportação.');
        }

        if (! is_writable($outputDirectory)) {
            throw new \RuntimeException('A pasta temporária da exportação não permite escrita.');
        }

        $workId = Str::uuid();
        $workDirectory = "{$outputDirectory}/pap-grupos-{$workId}";

        if (! mkdir($workDirectory, 0775)) {
            throw new \RuntimeException('Não foi possível preparar a pasta temporária da exportação.');
        }

        $documentName = basename($workDirectory);

        $wordPath = "{$workDirectory}/{$documentName}.docx";

        $pdfPath = "{$outputDirectory}/{$documentName}.pdf";

        $previousOutputEscaping = Settings::isOutputEscapingEnabled();

        $keepPdf = false;

        try {
            Settings::setOutputEscapingEnabled(true);

            $template = new TemplateProcessor($templatePath);

            $template->setValue('instituicao', $institutionName);

            $template->setValue('curso', $courseName);

            $template->setValue('classe', $className);

            $template->setValue('ano_lectivo', $schoolYearName);

            $template->cloneBlock('grupo', $groups->count(), true, true);

            foreach ($groups->values() as $groupIndex => $grupoPap) {
                $macroIndex = $groupIndex + 1;

                $tutor = $grupoPap->professor?->user;

                $template->setValue(
                    "numero_grupo#{$macroIndex}",
                    str_pad((string) $macroIndex, 2, '0', STR_PAD_LEFT)
                );

                $template->setValue(
                    "tema_grupo#{$macroIndex}",
                    filled($grupoPap->tema_grupo) ? $grupoPap->tema_grupo : 'Tema não definido, ainda.',
                );
                $template->setValue(
                    "caso_estudo#{$macroIndex}",
                    filled($grupoPap->estudo_caso) ? $grupoPap->estudo_caso : 'Não definido, ainda.',
                );

                $tutorInfo = array_filter([
                    filled($tutor?->nome) ? "Prof. {$tutor->nome}" : null,
                    $tutor?->email,
                    $tutor?->telefone,
                ]);
                
                $tutorInfo = $tutorInfo === []
                    ? 'não definido, ainda.'
                    : implode('   ·   ', $tutorInfo);

                $turmaName = $grupoPap->turma?->nome ?: 'não definida';
                $template->setValue("tutor_info#{$macroIndex}", implode('   ·   ', [
                    $tutorInfo,
                    "Turma  {$turmaName}",
                ]));

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
                    $studentNumber = $studentIndex + 1;

                    $studentMacro = "#{$macroIndex}#{$studentNumber}";

                    $candidate = $aluno->inscricao?->candidato;

                    $studentUser = $aluno->user;

                    $template->setValue("aluno_nome{$studentMacro}", (string) ($candidate?->nome ?? ''));

                    $template->setValue("aluno_bi{$studentMacro}", (string) ($candidate?->bi ?? $studentUser?->bi ?? ''));

                    $template->setValue(
                        "numero_estudante{$studentMacro}",
                        (string) ($candidate?->numero_estudante ?? $aluno->numero_processo ?? '')
                    );

                    $template->setValue(
                        "aluno_telefone{$studentMacro}",
                        (string) ($candidate?->telefone ?? $studentUser?->telefone ?? '')
                    );

                    $template->setValue(
                        "aluno_email{$studentMacro}",
                        (string) ($candidate?->email ?? $studentUser?->email ?? '')
                    );
                }
            }

            $unresolvedMacros = $template->getVariables();

            if ($unresolvedMacros !== []) {
                $unresolvedMacroNames = implode(', ', $unresolvedMacros);

                throw new \RuntimeException(
                    "O template contém campos sem preenchimento: {$unresolvedMacroNames}",
                );
            }

            $template->saveAs($wordPath);

            $process = new Process([
                '/usr/bin/soffice',
                '--headless',
                "-env:UserInstallation=file://{$workDirectory}/lo-profile",
                '--convert-to',
                'pdf',
                '--outdir',
                $outputDirectory,
                $wordPath,
            ]);

            $process->setEnv([
                'HOME' => $workDirectory,
                'XDG_CACHE_HOME' => "{$workDirectory}/.cache",
            ]);

            $process->setTimeout(30);

            $process->mustRun();

            if (! is_file($pdfPath) || filesize($pdfPath) === 0) {
                throw new \RuntimeException('O LibreOffice não gerou o PDF da lista PAP.');
            }

            $fileNameSuffix = Str::slug(implode('_', [
                $institutionName,
                $courseName,
                $schoolYearName,
            ]));

            $fileName = "lista_grupos_pap_{$fileNameSuffix}.pdf";

            $response = response()
                ->download($pdfPath, $fileName, ['Content-Type' => 'application/pdf'])
                ->deleteFileAfterSend(true);

            $keepPdf = true;

            return $response;
        } finally {
            Settings::setOutputEscapingEnabled($previousOutputEscaping);

            File::deleteDirectory($workDirectory);

            if (! $keepPdf && is_file($pdfPath)) {
                unlink($pdfPath);
            }
        }
    }
}
