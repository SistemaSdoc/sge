<?php

namespace App\Http\Controllers;

use App\Models\Turma;
use App\Services\VerificadorPropinaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RelatorioPropinaController extends Controller
{
    private const MESES = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];

    public function __construct(
        private readonly VerificadorPropinaService $verificador
    ) {}

    public function porTurma(Request $request, Turma $turma)
    {
        Log::debug('[RelatorioPropinaController] porTurma - início', [
            'turma_id' => $turma->id,
        ]);

        $this->autorizarTurma($request, $turma);

        return Inertia::render('relatorios/propinas-turma', $this->montarRelatorio($turma));
    }

    public function pdf(Request $request, Turma $turma)
    {
        Log::debug('[RelatorioPropinaController] pdf - início', [
            'turma_id' => $turma->id,
        ]);

        $this->autorizarTurma($request, $turma);

        $dados = $this->montarRelatorio($turma);

        $pdf = Pdf::loadView('pdf.relatorio-propinas', $dados)->setPaper('a4', 'portrait');

        $nomeFicheiro = 'relatorio-propinas-' . Str::slug($dados['turma']['nome']) . '.pdf';

        Log::info('[RelatorioPropinaController] pdf - a gerar download', [
            'turma_id' => $turma->id,
            'nome_ficheiro' => $nomeFicheiro,
        ]);

        return $pdf->download($nomeFicheiro);
    }

    private function montarRelatorio(Turma $turma): array
    {
        Log::debug('[RelatorioPropinaController] montarRelatorio - início', [
            'turma_id' => $turma->id,
        ]);

        $turma->loadMissing([
            'alunosActivos.user',
            'anoLectivo',
            'cursoClasseTurno.cursoClasse.classe',
            'cursoClasseTurno.turno',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
        ]);

        Log::debug('[RelatorioPropinaController] montarRelatorio - alunos activos na turma', [
            'turma_id' => $turma->id,
            'total_alunos' => $turma->alunosActivos->count(),
            'alunos_ids' => $turma->alunosActivos->pluck('id')->toArray(),
        ]);

        $linhas = $turma->alunosActivos
            ->map(function ($aluno) {
                Log::debug('[RelatorioPropinaController] processando aluno', [
                    'aluno_id' => $aluno->id,
                    'aluno_nome' => $aluno->user->nome ?? 'N/A',
                ]);

                $pendencias = $this->verificador->pendenciasDoAluno($aluno);

                Log::debug('[RelatorioPropinaController] pendências retornadas pelo VerificadorPropinaService', [
                    'aluno_id' => $aluno->id,
                    'total_pendencias_brutas' => count($pendencias),
                    'pendencias' => $pendencias,
                ]);

                if (empty($pendencias)) {
                    Log::debug('[RelatorioPropinaController] aluno em dia — não entra no relatório', [
                        'aluno_id' => $aluno->id,
                    ]);
                    return null;
                }

                $meses = collect($pendencias)
                    ->filter(fn ($p) => $p['mes'] !== null)
                    ->map(fn ($p) => [
                        'mes' => $p['mes'],
                        'ano' => $p['ano'],
                        'label' => self::MESES[$p['mes']] . '/' . $p['ano'],
                    ])
                    ->values();

                $itensNaoMensais = collect($pendencias)->filter(fn ($p) => $p['mes'] === null);

                Log::debug('[RelatorioPropinaController] cálculo de meses em falta', [
                    'aluno_id' => $aluno->id,
                    'total_meses_em_falta' => $meses->count(),
                    'meses_em_falta' => $meses->pluck('label')->toArray(),
                    'itens_nao_mensais_em_falta' => $itensNaoMensais->pluck('nome')->toArray(),
                    'valor_total_devido' => collect($pendencias)->sum('valor'),
                ]);

                return [
                    'aluno_id' => $aluno->id,
                    'nome' => $aluno->user->nome,
                    'total_meses' => $meses->count(),
                    'meses' => $meses->all(),
                    'valor_total' => collect($pendencias)->sum('valor'),
                ];
            })
            ->filter()
            ->sortByDesc('total_meses')
            ->values();

        Log::info('[RelatorioPropinaController] montarRelatorio - resumo final por aluno', [
            'turma_id' => $turma->id,
            'total_devedores' => $linhas->count(),
            'resumo_por_aluno' => $linhas->map(fn ($l) => [
                'nome' => $l['nome'],
                'total_meses' => $l['total_meses'],
                'valor_total' => $l['valor_total'],
            ])->toArray(),
        ]);

        $classeNome = $turma->cursoClasseTurno?->cursoClasse?->classe?->nome;
        $cursoNome = $turma->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome;
        $turnoNome = $turma->cursoClasseTurno?->turno?->nome;

        Log::info('[RelatorioPropinaController] relatório gerado', [
            'turma_id' => $turma->id,
            'total_devedores' => $linhas->count(),
            'valor_total_geral' => $linhas->sum('valor_total'),
        ]);

        return [
            'turma' => [
                'id' => $turma->id,
                'nome' => $turma->nome,
                'classe' => $classeNome,
                'curso' => $cursoNome,
                'turno' => $turnoNome,
                'ano_lectivo' => optional($turma->anoLectivo?->data_inicio)->year
                    . '/' . optional($turma->anoLectivo?->data_fim)->year,
            ],
            'linhas' => $linhas,
            'resumo' => [
                'total_alunos' => $turma->alunosActivos->count(),
                'total_devedores' => $linhas->count(),
                'valor_total_geral' => $linhas->sum('valor_total'),
            ],
            'geradoEm' => now()->format('d/m/Y H:i'),
        ];
    }

    private function autorizarTurma(Request $request, Turma $turma): void
    {
        $turma->loadMissing('cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso');

        $instituicaoIdDaTurma = $turma->cursoClasseTurno
            ?->cursoClasse
            ?->cursoTutelado
            ?->instituicaoCurso
            ?->instituicao_id;

        Log::debug('[RelatorioPropinaController] autorizarTurma', [
            'turma_id' => $turma->id,
            'instituicao_id_da_turma' => $instituicaoIdDaTurma,
            'instituicao_id_do_user' => $request->user()->instituicao_id,
            'autorizado' => $instituicaoIdDaTurma === $request->user()->instituicao_id,
        ]);

        abort_unless(
            $instituicaoIdDaTurma === $request->user()->instituicao_id,
            403,
            'Sem acesso a esta turma.'
        );
    }
}