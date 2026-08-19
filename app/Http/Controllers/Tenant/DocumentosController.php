<?php

namespace App\Http\Controllers\Tenant;

use App\Models\Tenant\Aluno;
use App\Models\Tenant\ItemPagavel;
use App\Services\CertificadoService;
use App\Services\DeclaracaoComNotaService;
use App\Services\DeclaracaoSemNotaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\Process\Process;

class DocumentosController extends Controller
{
    public function __construct(
        private DeclaracaoSemNotaService $declaracaoService,
        private DeclaracaoComNotaService $declaracaoComNotaService,
        private CertificadoService $certificadoService,
    ) {
    }

    // ── Listagem ──────────────────────────────────────────────────────────
    public function index()
    {
        $documentos = ItemPagavel::where('instituicao_id', auth()->user()->instituicao_id)
            ->where('tipo', 'documento')
            ->where('ativo', 1)
            ->get(['id', 'nome', 'curso_classe_id', 'valor']);

        return Inertia::render('documentos/index', [
            'documentos' => $documentos,
        ]);
    }

    // ── Pesquisar Aluno ───────────────────────────────────────────────────
    public function pesquisarAluno(Request $request)
    {
        $q = trim($request->query('q', ''));

        if (strlen($q) < 3) {
            return response()->json([]);
        }

        $alunos = Aluno::query()
            ->where('instituicao_id', auth()->user()->instituicao_id)
            ->where(function ($query) use ($q) {
                $query->where('matricula', 'like', "%{$q}%")
                    ->orWhere('numero_processo', 'like', "%{$q}%")
                    ->orWhereHas(
                        'inscricao.candidato',
                        fn($q2) => $q2->where('nome', 'like', "%{$q}%")
                    );
            })
            ->with([
                'inscricao.candidato',
                'inscricao.cursoClasseTurno.cursoClasse.classe',
                'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
            ])
            ->limit(10)
            ->get();

        if ($alunos->isEmpty()) {
            return response()->json([]);
        }

        $resultado = $alunos->map(function ($aluno) {
            $nomeAluno = $aluno->inscricao?->candidato?->nome;

            if (!$nomeAluno) {
                return null;
            }

            $turma = $aluno->turmaActual()
                ->with([
                    'cursoClasseTurno.cursoClasse.classe',
                    'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
                ])
                ->first();

            $cursoClasseTurno = $turma?->cursoClasseTurno
                ?? $aluno->inscricao?->cursoClasseTurno;

            $cursoClasse = $cursoClasseTurno?->cursoClasse;
            $curso = $cursoClasse?->cursoTutelado?->instituicaoCurso?->curso;
            $classe = $cursoClasse?->classe;

            $turmas = $aluno->turmas()
                ->with([
                    'cursoClasseTurno.cursoClasse.classe',
                    'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
                ])
                ->get();

            $classes = $turmas->map(function ($t) {
                $cc = $t->cursoClasseTurno->cursoClasse ?? null;
                $curso = $cc?->cursoTutelado?->instituicaoCurso?->curso;
                $classe = $cc?->classe;

                return [
                    'turma_id' => $t->id,
                    'curso_classe_id' => $cc?->id,
                    'curso' => $curso?->nome ?? '—',
                    'classe' => $classe?->nome ?? '—',
                ];
            })->unique('curso_classe_id')->values();

            return [
                'id' => $aluno->id,
                'nome' => $nomeAluno,
                'matricula' => $aluno->matricula,
                'numero_processo' => $aluno->numero_processo,
                'curso' => $curso?->nome ?? '—',
                'classe' => $classe?->nome ?? '—',
                'curso_classe_id' => $cursoClasse?->id,
                'classes' => $classes,
            ];
        })->filter()->values();

        return response()->json($resultado);
    }

    // ─── Exportar PDF ─────────────────────────────────────────────────────────
    public function exportar(Request $request)
    {
        $request->validate([
            'item_pagavel_id' => 'required|uuid|exists:itens_pagaveis,id',
            'aluno_id' => 'required|uuid|exists:alunos,id',
            'classe_id' => 'nullable|uuid|exists:curso_classe,id',
            'efeito' => 'nullable|string|max:255',
        ]);

        // Verificar que o documento pertence à instituição do utilizador
        $item = ItemPagavel::where('id', $request->item_pagavel_id)
            ->where('tipo', 'documento')
            ->where('instituicao_id', auth()->user()->instituicao_id)
            ->firstOrFail();

        // Resolver aluno com tudo o que os services precisam
        $aluno = Aluno::where('id', $request->aluno_id)
            ->with([
                'inscricao.candidato',
                'inscricao.anoLectivo',
                'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
                'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao',
            ])
            ->firstOrFail();

        // Tenta usar a turma actual do aluno; se não existir, usa a primeira turma disponível.
        $turma = $aluno->turmaActual()
            ->when(
                $request->classe_id,
                fn($q) =>
                $q->whereHas(
                    'cursoClasseTurno.cursoClasse',
                    fn($q2) =>
                    $q2->where('id', $request->classe_id)
                )
            )
            ->with([
                'anoLectivo',
                'cursoClasseTurno.cursoClasse.classe',
                'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
                'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao',
            ])
            ->first();

        if (!$turma) {
            $turma = $aluno->turmas()
                ->when(
                    $request->classe_id,
                    fn($q) =>
                    $q->whereHas(
                        'cursoClasseTurno.cursoClasse',
                        fn($q2) =>
                        $q2->where('id', $request->classe_id)
                    )
                )
                ->with([
                    'anoLectivo',
                    'cursoClasseTurno.cursoClasse.classe',
                    'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
                    'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao',
                ])
                ->first();
        }

        if (!$turma) {
            abort(422, 'Aluno sem turma associada para emitir este documento.');
        }

        $nome = strtolower($item->nome);
        $candidato = $aluno->inscricao->candidato;

        // ── Certificado ───────────────────────────────────────────────────────
        if (str_contains($nome, 'certificado')) {
            $classeNome = $turma->cursoClasseTurno->cursoClasse->classe->nome ?? '';

            if (!str_contains($classeNome, '13ª')) {
                abort(response()->json(['message' => 'O certificado só pode ser emitido para alunos da 13ª classe.']));
            }

            $pdf = $this->certificadoService->gerarPdf($aluno, $turma);

            return response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header(
                    'Content-Disposition',
                    'attachment; filename="Certificado_' . str_replace(' ', '_', $candidato->nome) . '.pdf"'
                );
        }

        // ── Declaração sem notas ─────────────────────────────────────────────
        if (
            str_contains($nome, 'declaração sem notas')
            || str_contains($nome, 'declaracao sem notas')
        ) {
            $classeNome = $turma->cursoClasseTurno->cursoClasse->classe->nome ?? '';

            if (str_contains($classeNome, '13ª')) {
                abort(response()->json(['message' => 'A declaração sem notas não pode ser emitida para alunos da 13ª classe.']));
            }

            $cct = $turma->cursoClasseTurno;
            $cc = $cct->cursoClasse;
            $ct = $cc->cursoTutelado;
            $inst = $ct->instituicaoCurso->instituicao;

            $efeito = $request->input('efeito');
            $docx = $this->declaracaoService->gerar($inst, $ct, $cc, $cct, $turma, $aluno, $efeito);

            $outDir = sys_get_temp_dir();
            $process = new Process([
                '/usr/bin/soffice',
                '--headless',
                '--convert-to',
                'pdf',
                '--outdir',
                $outDir,
                $docx,
            ]);
            $process->setTimeout(30);
            $process->run();

            $pdf = $outDir . '/' . pathinfo($docx, PATHINFO_FILENAME) . '.pdf';
            $nomeFicheiro = 'Declaracao_Sem_Notas_' . str_replace(' ', '_', $candidato->nome) . '.pdf';

            return response()
                ->download($pdf, $nomeFicheiro, ['Content-Type' => 'application/pdf'])
                ->deleteFileAfterSend(true);
        }

        // ── Declaração com notas por classe ───────────────────────────────────
        if (
            str_contains($nome, 'declaração com notas')
            || str_contains($nome, 'declaracao com notas')
        ) {
            $classeNome = $turma->cursoClasseTurno->cursoClasse->classe->nome ?? '';

            if (str_contains($classeNome, '13ª')) {
                abort(response()->json(['message' => 'A declaração com notas não pode ser emitida para alunos da 13ª classe.']));
            }

            $efeito = $request->input('efeito');
            $docx = $this->declaracaoComNotaService->gerar($aluno, $turma, $efeito);

            $outDir = sys_get_temp_dir();
            $process = new Process([
                '/usr/bin/soffice',
                '--headless',
                '--convert-to',
                'pdf',
                '--outdir',
                $outDir,
                $docx,
            ]);
            $process->setTimeout(30);
            $process->run();

            $pdf = $outDir . '/' . pathinfo($docx, PATHINFO_FILENAME) . '.pdf';
            $nomeFicheiro = 'Declaracao_Com_Notas_' . str_replace(' ', '_', $candidato->nome) . '.pdf';

            return response()
                ->download($pdf, $nomeFicheiro, ['Content-Type' => 'application/pdf'])
                ->deleteFileAfterSend(true);
        }

        abort(422, 'Tipo de documento não reconhecido.');
    }
}