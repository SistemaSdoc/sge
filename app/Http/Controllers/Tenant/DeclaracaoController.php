<?php

namespace App\Http\Controllers\Tenant;

use App\Models\Tenant\Aluno;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Services\DeclaracaoSemNotaService;
use Symfony\Component\Process\Process;

class DeclaracaoController extends Controller
{
    public function __construct(private DeclaracaoSemNotaService $service) {}

    public function download(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        Aluno $aluno
    ) {
        $this->authorize('view', $aluno);

        // 1. Gerar o .docx preenchido
        $docx = $this->service->gerar($instituicao, $cursoTutelado, $cursoClasse, $cursoClasseTurno, $turma, $aluno);

        // 2. Converter para PDF com LibreOffice (mesmo que o soffice.py nos testes)
        $outDir = sys_get_temp_dir();
        $process = new Process([
            '/usr/bin/soffice',
            '--headless',
            '--convert-to', 'pdf',
            '--outdir', $outDir,
            $docx,
        ]);
        $process->setTimeout(30);
        $process->run();

        // Caminho do PDF gerado pelo LibreOffice
        $pdf = $outDir.'/'.pathinfo($docx, PATHINFO_FILENAME).'.pdf';

        $candidato = $aluno->inscricao->candidato;
        $nome = 'Declaracao_'.str_replace(' ', '_', $candidato->nome).'.pdf';

        return response()
            ->download($pdf, $nome, ['Content-Type' => 'application/pdf'])
            ->deleteFileAfterSend(true);
    }
}
