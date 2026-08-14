<?php

namespace App\Services;

use App\Helpers\BrowsershotHelper;
use App\Models\Aluno;
use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Models\TurmaDisciplinaProfessor;
use App\Models\ElementoGrupoPap;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Spatie\Browsershot\Browsershot;

class CertificadoService
{
    // ─── Cálculo central ──────────────────────────────────────────────────────
    public function calcular(Aluno $aluno, Turma $turma): array
    {
        $nomesPapEcs = ['prova de aptidão profissional', 'estágio curricular supervisionado'];

        $turmasDoAluno = TurmaAluno::where('aluno_id', $aluno->id)->pluck('turma_id');

        $tdps = TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->whereIn('turma_id', $turmasDoAluno)
            ->get();

        $porDisciplina = [];

        foreach ($tdps as $tdp) {
            $disciplina = $tdp->classeTurnoDisciplina?->disciplina;
            if (! $disciplina) continue;

            $turmaAluno = TurmaAluno::where('turma_id', $tdp->turma_id)
                ->where('aluno_id', $aluno->id)
                ->first();
            if (! $turmaAluno) continue;

            $nota = $turmaAluno->notas()
                ->where('turma_disciplina_professor_id', $tdp->id)
                ->whereNotNull('media_final')
                ->first();
            if (! $nota) continue;

            $mediaArredondada = round((float) $nota->media_final * 2) / 2;
            $id = $disciplina->id;

            if (! isset($porDisciplina[$id])) {
                $porDisciplina[$id] = [
                    'disciplina' => $disciplina->nome,
                    'componente' => $disciplina->componente ?? 'tecnica',
                    'medias'     => [],
                ];
            }

            $porDisciplina[$id]['medias'][] = $mediaArredondada;
        }

        $notas           = [];
        $somaMedias      = 0;
        $totalDisciplinas = 0;
        $notaEcs         = null;

        foreach ($porDisciplina as $item) {
            $mediaFinal = round(
                (array_sum($item['medias']) / count($item['medias'])) * 2
            ) / 2;

            $componente = $item['componente'];
            $nomeDisc   = strtolower($item['disciplina']);

            if (str_contains($nomeDisc, 'estágio')) {
                $notaEcs = $mediaFinal;
            }

            if (! in_array($nomeDisc, $nomesPapEcs)) {
                $notas[$componente][] = [
                    'disciplina'  => $item['disciplina'],
                    'media_final' => $mediaFinal,
                    'extenso'     => $this->numeroParaExtenso($mediaFinal),
                ];
                $somaMedias += $mediaFinal;
                $totalDisciplinas++;
            }
        }

        $mediaPC = $totalDisciplinas > 0
            ? round(($somaMedias / $totalDisciplinas) * 2) / 2
            : null;

        $elementoPap = ElementoGrupoPap::whereHas(
            'grupoPap',
            fn ($q) => $q->whereIn('turma_id', $turmasDoAluno)
        )->where('aluno_id', $aluno->id)->first();

        $notaPap = $elementoPap?->nota_individual
            ?? $elementoPap?->grupoPap?->nota_final
            ?? null;

        if ($notaPap !== null) {
            $notaPap = round((float) $notaPap * 2) / 2;
        }

        $classificacaoFinal = null;
        if ($mediaPC !== null && $notaPap !== null && $notaEcs !== null) {
            $classificacaoFinal = round(((4 * $mediaPC) + $notaPap + $notaEcs) / 6 * 2) / 2;
        }

        return [
            'notas'                       => $notas,
            'media_pc'                    => $mediaPC,
            'media_pc_extenso'            => $this->numeroParaExtenso($mediaPC),
            'nota_pap'                    => $notaPap,
            'nota_pap_extenso'            => $this->numeroParaExtenso($notaPap),
            'nota_ecs'                    => $notaEcs,
            'nota_ecs_extenso'            => $this->numeroParaExtenso($notaEcs),
            'classificacao_final'         => $classificacaoFinal,
            'classificacao_final_extenso' => $this->numeroParaExtenso($classificacaoFinal),
        ];
    }

    // ─── Gera PDF e devolve os bytes ──────────────────────────────────────────
    public function gerarPdf(Aluno $aluno, Turma $turma): string
    {
        $aluno->load([
            'inscricao.candidato',
            'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
            'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao',
        ]);

        $instituicaoCurso = $aluno->inscricao->cursoClasseTurno->cursoClasse->cursoTutelado->instituicaoCurso;
        $candidato        = $aluno->inscricao->candidato;
        $calc             = $this->calcular($aluno, $turma);

        $url    = url('/certificados/' . $aluno->id . '/verificar');
        $result = (new Builder(writer: new PngWriter, data: $url, size: 120, margin: 10))->build();
        $qrcode = base64_encode($result->getString());

        $dados = array_merge($calc, [
            'instituicao' => $instituicaoCurso->instituicao,
            'curso'       => $instituicaoCurso->curso,
            'turma'       => $turma,
            'candidato'   => $candidato,
            'aluno'       => $aluno,
            'ano_letivo'  => date('Y') . '/' . (date('Y') + 1),
            'qrcode'      => $qrcode,
        ]);

        $html = view('certificados.certificado', $dados)->render();

        return Browsershot::html($html)
            ->setChromePath(BrowsershotHelper::getChromePath())
            ->setNodeBinary(BrowsershotHelper::getNodeBinary())
            ->setNpmBinary(BrowsershotHelper::getNpmBinary())
            ->addChromiumArguments([
                '--no-sandbox', '--disable-setuid-sandbox',
                '--disable-dev-shm-usage', '--disable-gpu',
                '--headless=new', '--no-zygote', '--single-process',
            ])
            ->timeout(60000)
            ->format('A4')
            ->portrait()
            ->noSandbox()
            ->waitUntilNetworkIdle()
            ->margins(0, 0, 0, 0)
            ->pdf();
    }

    // ─── Helper ───────────────────────────────────────────────────────────────
    private function numeroParaExtenso(?float $numero): string
    {
        if ($numero === null) return '—';

        $chave = (int) round($numero);
        $mapa  = [
            0 => 'Zero', 1 => 'Um', 2 => 'Dois', 3 => 'Três',
            4 => 'Quatro', 5 => 'Cinco', 6 => 'Seis', 7 => 'Sete',
            8 => 'Oito', 9 => 'Nove', 10 => 'Dez', 11 => 'Onze',
            12 => 'Doze', 13 => 'Treze', 14 => 'Catorze', 15 => 'Quinze',
            16 => 'Dezasseis', 17 => 'Dezassete', 18 => 'Dezoito',
            19 => 'Dezanove', 20 => 'Vinte',
        ];

        return ($mapa[$chave] ?? (string) $chave) . ' Valores';
    }
}