<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\ItemPagavel;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\CertificadoService;
use App\Services\Tenant\DeclaracaoComNotaService;
use App\Services\Tenant\DeclaracaoSemNotaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    private function emitirCertificado(Aluno $aluno, Turma $turma, $candidato, string $classeNome): mixed
    {
        if (!str_contains($classeNome, '13ª')) {
            abort(response()->json(['message' => 'O certificado só pode ser emitido para alunos da 13ª classe.']));
        }

        // Usa o CertificadoService (agrega todas as turmas do aluno)
        $calc = $this->certificadoService->calcular($aluno, $turma);

        $todasDisciplinas = array_merge(...array_values($calc['notas']));

        if (empty($todasDisciplinas)) {
            abort(response()->json(['message' => 'O aluno não tem notas lançadas para gerar o certificado.']));
        }

        $pdf = $this->certificadoService->gerarPdf($aluno, $turma);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="Certificado_' . str_replace(' ', '_', $candidato->nome) . '.pdf"');
    }

    private function emitirDeclaracaoSemNotas(Aluno $aluno, Turma $turma, $candidato, string $classeNome, ?string $efeito): mixed
    {
        if (str_contains($classeNome, '13ª')) {
            abort(response()->json(['message' => 'A declaração sem notas não pode ser emitida para alunos da 13ª classe.']));
        }

        $cct = $turma->cursoClasseTurno;
        $cc = $cct->cursoClasse;
        $ct = $cc->cursoTutelado;
        $inst = $ct->instituicaoCurso->instituicao;

        $docx = $this->declaracaoService->gerar($inst, $ct, $cc, $cct, $turma, $aluno, $efeito);
        $pdf = $this->converterParaPdf($docx);

        return response()
            ->download($pdf, 'Declaracao_Sem_Notas_' . str_replace(' ', '_', $candidato->nome) . '.pdf', ['Content-Type' => 'application/pdf'])
            ->deleteFileAfterSend(true);
    }

    private function emitirDeclaracaoComNotas(Aluno $aluno, Turma $turma, $candidato, string $classeNome, ?string $efeito): mixed
    {
        if (str_contains($classeNome, '13ª')) {
            abort(response()->json(['message' => 'A declaração com notas não pode ser emitida para alunos da 13ª classe.']));
        }

        // Verifica se tem notas
        $dados = $this->declaracaoComNotaService->calcularDados($aluno, $turma);
        $todasDisciplinas = array_merge(
            $dados['notas']['sociocultural'] ?? [],
            $dados['notas']['cientifica'] ?? [],
            $dados['notas']['tecnica'] ?? [],
        );

        if (empty($todasDisciplinas)) {
            abort(response()->json(['message' => 'O aluno não tem notas lançadas para gerar a declaração com notas.']));
        }

        $docx = $this->declaracaoComNotaService->gerar($aluno, $turma, $efeito);
        $pdf = $this->converterParaPdf($docx);

        return response()
            ->download($pdf, 'Declaracao_Com_Notas_' . str_replace(' ', '_', $candidato->nome) . '.pdf', ['Content-Type' => 'application/pdf'])
            ->deleteFileAfterSend(true);
    }

    private function converterParaPdf(string $docx): string
    {
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

        return $outDir . '/' . pathinfo($docx, PATHINFO_FILENAME) . '.pdf';
    }

    // ── Listagem ──────────────────────────────────────────────────────────
    public function index()
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $documentos = ItemPagavel::where('instituicao_id', $user->instituicao_id)
            ->where('tipo', 'documento')
            ->where('ativo', 1)
            ->with('documento') // ← adiciona
            ->get(['id', 'nome', 'curso_classe_id', 'valor'])
            ->map(fn($item) => [
                'id' => $item->id,
                'nome' => $item->nome,
                'subtipo' => $item->documento?->subtipo,
                'valor' => $item->valor,
                'curso_classe_id' => $item->curso_classe_id,
            ]);

        return Inertia::render('tenant/documentos/index', [
            'documentos' => $documentos,
            'can' => [
                'emitir' => auth()->user()->can('documentos.emitir'),
            ],
        ]);
    }

    // ── Pesquisar Aluno ───────────────────────────────────────────────────
    public function pesquisarAluno(Request $request)
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $q = trim($request->query('q', ''));

        if (strlen($q) < 3) {
            return response()->json([]);
        }

        $alunos = Aluno::query()
            ->where('instituicao_id', $user->instituicao_id)
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
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $request->validate([
            'item_pagavel_id' => 'required|uuid|exists:itens_pagaveis,id',
            'aluno_id' => 'required|uuid|exists:alunos,id',
            'classe_id' => 'nullable|uuid|exists:curso_classe,id',
            'efeito' => 'nullable|string|max:255',
        ]);

        $item = ItemPagavel::where('id', $request->item_pagavel_id)
            ->where('tipo', 'documento')
            ->where('instituicao_id', $user->instituicao_id)
            ->firstOrFail();
        // exportar — carrega o documento associado e autoriza com ele
        $item->load('documento');
        $documento = $item->documento;

        if (!$documento) {
            abort(422, 'Este documento não tem subtipo configurado.');
        }

        $this->authorize('exportar', $documento);

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
                fn($q) => $q->whereHas(
                    'cursoClasseTurno.cursoClasse',
                    fn($q2) => $q2->where('id', $request->classe_id)
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
                    fn($q) => $q->whereHas(
                        'cursoClasseTurno.cursoClasse',
                        fn($q2) => $q2->where('id', $request->classe_id)
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

        $item->load('documento');
        $documento = $item->documento;

        if (!$documento) {
            abort(422, 'Este documento não tem subtipo configurado.');
        }

        $classeNome = $turma->cursoClasseTurno->cursoClasse->classe->nome ?? '';
        $efeito = $request->input('efeito');
        $candidato = $aluno->inscricao->candidato;

        return match ($documento->subtipo) {
            'certificado' => $this->emitirCertificado($aluno, $turma, $candidato, $classeNome),
            'declaracao_sem_notas' => $this->emitirDeclaracaoSemNotas($aluno, $turma, $candidato, $classeNome, $efeito),
            'declaracao_com_notas' => $this->emitirDeclaracaoComNotas($aluno, $turma, $candidato, $classeNome, $efeito),
            default => abort(422, 'Tipo não reconhecido.'),
        };
    }
}
