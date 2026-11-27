<?php

use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;

it('clones group blocks and variable student rows from the PAP template', function () {
    $templatePath = dirname(__DIR__, 2).'/resources/templates/lista-grupos-pap-export.docx';
    $temporaryDirectory = sys_get_temp_dir().'/pap-template-'.bin2hex(random_bytes(6));
    mkdir($temporaryDirectory, 0775, true);
    $previousTemporaryDirectory = Settings::getTempDir();
    $previousOutputEscaping = Settings::isOutputEscapingEnabled();
    Settings::setTempDir($temporaryDirectory);
    Settings::setOutputEscapingEnabled(true);

    $processor = new TemplateProcessor($templatePath);
    $processor->cloneBlock('grupo', 2, true, true);
    $processor->cloneRow('aluno_nome#1', 1);
    $processor->cloneRow('aluno_nome#2', 2);
    $processor->setValues([
        'instituicao' => 'Instituto Teste',
        'curso' => 'Informática',
        'classe' => '13ª',
        'ano_lectivo' => '2026/2027',
        'tema_grupo#1' => 'Tema Alfa & Beta',
        'nome_grupo#1' => 'Grupo 01',
        'caso_estudo#1' => 'Caso <A>',
        'tutor_nome#1' => 'Tutor A',
        'tutor_email#1' => 'tutor-a@example.test',
        'tutor_telefone#1' => '923000001',
        'aluno_nome#1#1' => 'Estudante A1',
        'aluno_bi#1#1' => 'BI-A1',
        'numero_estudante#1#1' => 'EST-A1',
        'aluno_telefone#1#1' => '923000011',
        'aluno_email#1#1' => 'aluno-a1@example.test',
        'tema_grupo#2' => 'Tema Beta',
        'nome_grupo#2' => 'Grupo 02',
        'caso_estudo#2' => 'Caso B',
        'tutor_nome#2' => 'Tutor B',
        'tutor_email#2' => 'tutor-b@example.test',
        'tutor_telefone#2' => '923000002',
        'aluno_nome#2#1' => 'Estudante B1',
        'aluno_bi#2#1' => 'BI-B1',
        'numero_estudante#2#1' => 'EST-B1',
        'aluno_telefone#2#1' => '923000021',
        'aluno_email#2#1' => 'aluno-b1@example.test',
        'aluno_nome#2#2' => 'Estudante B2',
        'aluno_bi#2#2' => 'BI-B2',
        'numero_estudante#2#2' => 'EST-B2',
        'aluno_telefone#2#2' => '923000022',
        'aluno_email#2#2' => 'aluno-b2@example.test',
    ]);

    $outputPath = $temporaryDirectory.'/output.docx';

    try {
        $processor->saveAs($outputPath);
        $archive = new ZipArchive;
        expect($archive->open($outputPath))->toBeTrue();
        $documentXml = $archive->getFromName('word/document.xml');
        $archive->close();

        $document = new DOMDocument;

        expect($document->loadXML($documentXml))->toBeTrue()
            ->and($documentXml)
            ->toContain('Tema Alfa &amp; Beta', 'Caso &lt;A&gt;', 'Tema Beta', 'Estudante A1', 'Estudante B1', 'Estudante B2')
            ->not->toContain('${');
    } finally {
        Settings::setTempDir($previousTemporaryDirectory);
        Settings::setOutputEscapingEnabled($previousOutputEscaping);
        unset($processor);
        foreach (glob($temporaryDirectory.'/*') ?: [] as $temporaryFile) {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
        rmdir($temporaryDirectory);
    }
});

it('removes the group block when there are no matching groups', function () {
    $template = new TemplateProcessor(
        dirname(__DIR__, 2).'/resources/templates/lista-grupos-pap-export.docx',
    );

    $template->cloneBlock('grupo', 0, true, true);
    $template->setValues([
        'instituicao' => 'Instituto Teste',
        'curso' => 'Informática',
        'classe' => '13ª',
        'ano_lectivo' => '2026/2027',
    ]);

    expect($template->getVariables())->toBe([]);
});
