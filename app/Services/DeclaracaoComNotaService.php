<?php

namespace App\Services;

use App\Helpers\BrowsershotHelper;
use App\Models\Aluno;
use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Models\TurmaDisciplinaProfessor;
use Spatie\Browsershot\Browsershot;

class DeclaracaoComNotaService
{
    public function obterAnoLectivoNome(Aluno $aluno, Turma $turma): string
    {
        return $turma->anoLectivo?->nome
            ?? $aluno->inscricao?->anoLectivo?->nome
            ?? date('Y') . '/' . (date('Y') + 1);
    }

    public function gerarPdf(Aluno $aluno, Turma $turma): string
    {
        $aluno->load([
            'inscricao.candidato',
            'inscricao.anoLectivo',
            'inscricao.cursoClasseTurno.cursoClasse.classe',
            'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
            'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao',
            'inscricao.cursoClasseTurno.turno',
        ]);

        $turma->load([
            'anoLectivo',
            'cursoClasseTurno.cursoClasse.classe',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao',
            'cursoClasseTurno.turno',
        ]);

        $dados = $this->calcularDados($aluno, $turma);

        $inscricao = $aluno->inscricao;
        $cct = $turma->cursoClasseTurno;
        $cursoClasse = $cct->cursoClasse;
        $instituicao = $cursoClasse->cursoTutelado->instituicaoCurso->instituicao;
        $curso = $cursoClasse->cursoTutelado->instituicaoCurso->curso;
        $classe = $cursoClasse->classe;
        $turno = $cct->turno?->nome ?? 'Diúrno';
        $candidato = $inscricao?->candidato;

        // número sequencial da declaração — ajuste a lógica conforme o teu modelo
        $numeroDeclaracao = str_pad(4, '0', STR_PAD_LEFT);

        $html = view('declaracoes.com-notas', array_merge($dados, [
            'instituicao' => $instituicao,
            'curso' => $curso,
            'cursoClasse' => $cursoClasse,
            'classe' => $classe,
            'turma' => $turma,
            'candidato' => $candidato,
            'aluno' => $aluno,
            'ano_lectivo' => $this->obterAnoLectivoNome($aluno, $turma),
            'turno' => ucfirst($turno),
            'area_formacao' => $curso->area_formacao ?? $curso->nome,
            'numero_declaracao' => $numeroDeclaracao,
            'resultado_final' => $dados['classificacao_final'] !== null && $dados['classificacao_final'] >= 10
                ? 'Apto'
                : 'Não Apto',
            // número do aluno na turma — expõe se o modelo tiver esse campo
            'turma_aluno_numero' => optional(
                TurmaAluno::where('aluno_id', $aluno->id)
                    ->where('turma_id', $turma->id)
                    ->first()
            )->numero,
        ]))->render();

        return Browsershot::html($html)
            ->setChromePath(BrowsershotHelper::getChromePath())
            ->setNodeBinary(BrowsershotHelper::getNodeBinary())
            ->setNpmBinary(BrowsershotHelper::getNpmBinary())
            ->addChromiumArguments([
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--headless=new',
                '--no-zygote',
                '--single-process',
            ])
            ->timeout(60000)
            ->format('A4')
            ->portrait()
            ->noSandbox()
            ->waitUntilNetworkIdle()
            ->margins(0, 0, 0, 0)
            ->pdf();
    }

    public function calcularDados(Aluno $aluno, Turma $turma): array
    {
        $turmaAluno = TurmaAluno::where('aluno_id', $aluno->id)
            ->where('turma_id', $turma->id)
            ->first();

        if (!$turmaAluno) {
            return [
                'notas' => [],
                'media_pc' => null,
                'media_pc_extenso' => '—',
                'classificacao_final' => null,
                'classificacao_final_extenso' => '—',
            ];
        }

        $tdps = TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->where('turma_id', $turma->id)
            ->get();

        $notasPorComponente = [
            'sociocultural' => [],
            'cientifica' => [],
            'tecnica' => [],
        ];

        $somaMedias = 0;
        $totalDisciplinas = 0;

        foreach ($tdps as $tdp) {
            $disciplina = $tdp->classeTurnoDisciplina?->disciplina;

            if (!$disciplina) {
                continue;
            }

            $nota = $turmaAluno->notas()
                ->where('turma_disciplina_professor_id', $tdp->id)
                ->where('periodo', 3)
                ->first();

            if (!$nota || $nota->media_final === null) {
                continue;
            }

            $mediaFinal = round((float) $nota->media_final * 2) / 2;
            $componente = strtolower($disciplina->componente ?? 'tecnica');

            if (!isset($notasPorComponente[$componente])) {
                $componente = 'tecnica';
            }

            $notasPorComponente[$componente][] = [
                'disciplina' => $disciplina->nome,
                'media_final' => $mediaFinal,
                'extenso' => $this->numeroParaExtenso($mediaFinal),
            ];

            $somaMedias += $mediaFinal;
            $totalDisciplinas++;
        }

        $mediaPc = $totalDisciplinas > 0
            ? round(($somaMedias / $totalDisciplinas) * 2) / 2
            : null;

        return [
            'notas' => $notasPorComponente,
            'media_pc' => $mediaPc,
            'media_pc_extenso' => $this->numeroParaExtenso($mediaPc),
            'classificacao_final' => $mediaPc,
            'classificacao_final_extenso' => $this->numeroParaExtenso($mediaPc),
        ];
    }

    private function numeroParaExtenso(?float $numero): string
    {
        if ($numero === null) {
            return '—';
        }

        $valor = (int) round($numero);
        $mapa = [
            0 => 'Zero',
            1 => 'Um',
            2 => 'Dois',
            3 => 'Três',
            4 => 'Quatro',
            5 => 'Cinco',
            6 => 'Seis',
            7 => 'Sete',
            8 => 'Oito',
            9 => 'Nove',
            10 => 'Dez',
            11 => 'Onze',
            12 => 'Doze',
            13 => 'Treze',
            14 => 'Catorze',
            15 => 'Quinze',
            16 => 'Dezasseis',
            17 => 'Dezassete',
            18 => 'Dezoito',
            19 => 'Dezanove',
            20 => 'Vinte',
        ];

        return ($mapa[$valor] ?? (string) $valor) . ' valores';
    }
}