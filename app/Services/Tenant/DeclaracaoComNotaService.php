<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Aluno;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use Carbon\Carbon;
use ZipArchive;

class DeclaracaoComNotaService
{
    private string $template;

    public function __construct()
    {
        $this->template = storage_path('app/templates/Declaracao_com_notas_template.docx');
    }

    public function obterAnoLectivoNome(Aluno $aluno, Turma $turma): string
    {
        return $turma->anoLectivo?->nome
            ?? $aluno->inscricao?->anoLectivo?->nome
            ?? date('Y') . '/' . (date('Y') + 1);
    }

    public function gerar(Aluno $aluno, Turma $turma, ?string $efeito = null): string
    {
        Carbon::setLocale('pt');

        $aluno->load(['inscricao.candidato', 'inscricao.anoLectivo']);
        $turma->load([
            'anoLectivo',
            'cursoClasseTurno.cursoClasse.classe',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao',
            'cursoClasseTurno.turno',
        ]);

        $cct = $turma->cursoClasseTurno;
        $cursoClasse = $cct->cursoClasse;
        $instituicao = $cursoClasse->cursoTutelado->instituicaoCurso->instituicao;
        $curso = $cursoClasse->cursoTutelado->instituicaoCurso->curso;
        $classe = $cursoClasse->classe;
        $candidato = $aluno->inscricao?->candidato;
        $anoLectivo = $turma->anoLectivo ?? $aluno->inscricao?->anoLectivo;

        $tipo = match ($instituicao->tipo) {
            'colegio' => 'Colégio',
            default => 'Instituto',
        };

        $turmaAluno = TurmaAluno::where('aluno_id', $aluno->id)
            ->where('turma_id', $turma->id)
            ->first();

        $turno = $cct->turno?->nome ?? '';
        $curriculum = match (true) {
            str_contains(strtolower($turno), 'noite') => 'Noturno',
            default => 'Diurno',
        };

        $numeroDeclaracao = TurmaAluno::whereHas(
            'turma',
            fn($q) =>
            $q->where('ano_lectivo_id', $anoLectivo->id)
        )->where('created_at', '<=', $turmaAluno->created_at)
            ->count();

        $dados = $this->calcularDados($aluno, $turma);
        $todasDisciplinas = array_merge(
            $dados['notas']['sociocultural'] ?? [],
            $dados['notas']['cientifica'] ?? [],
            $dados['notas']['tecnica'] ?? [],
        );

        $resultado = ($dados['classificacao_final'] !== null && $dados['classificacao_final'] >= 10)
            ? 'APTO'
            : 'NÃO APTO';

        // ── Substituições simples (mesmo padrão do DeclaracaoSemNotaService) ──
        $substituicoes = [
            'nome da instituição ou colégio' => mb_strtoupper($instituicao->nome, 'UTF-8'),
            'declaracao_numero' => 'Nº' . str_pad($numeroDeclaracao, 3, '0', STR_PAD_LEFT) . '/SP/' . now()->year,
            'resultadofinal' => $resultado ?? 'Não Apto',
            'RSLTFINAL' => $resultado,
            '[finalidade do doc.]' => $efeito ?? 'de frequência e aproveitamento escolar',
            'ex João Silva' => mb_strtoupper($candidato->nome, 'UTF-8'),
            '[Nome dos encarregados]' => $candidato->filiacao ?? '_______________',
            'Curriculum Diúrno' => 'Curriculum ' . $curriculum,
            '[Instituto/Colégio]' => $tipo,
            '[2025/26]' => $anoLectivo->nome,
            '[10ª]' => $classe->nome,
            '[nome do curso]' => $curso->nome,
            '[turma]' => $turma->nome,
            '[informática]' => $curso->area ?? $curso->nome,
            '[número do aluno da turma]' => (string) ($turmaAluno?->numero_na_turma ?? '___'),
            '[número de processo]' => $candidato->numero_estudante,
            '[classe_tabela]' => $classe->nome . ' Classe',
            '[media_final]' => $dados['classificacao_final'] !== null
                ? number_format($dados['classificacao_final'], 1)
                : '—',
            '[media_extenso]' => $dados['classificacao_final_extenso'],
            '[data]' => now()->locale('pt')->isoFormat('D [de] MMMM [de] YYYY'),
            '[subdirector]' => $instituicao->subdirector ?? '_______________',
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'decl_cn_') . '.docx';
        copy($this->template, $tmp);

        $zip = new ZipArchive();
        $zip->open($tmp);
        $xml = $zip->getFromName('word/document.xml');

        // Substituições simples
        foreach ($substituicoes as $placeholder => $valor) {
            $xml = str_replace(
                $placeholder,
                htmlspecialchars(
                    (string) $valor,
                    ENT_XML1 | ENT_QUOTES,
                    'UTF-8'
                ),
                $xml
            );
        }

        // ── Bloco de disciplinas ──
        // No template, coloca um parágrafo com apenas o texto: [disciplinas]
        // Este método substitui esse parágrafo inteiro pelo XML gerado.
        $xmlDisciplinas = $this->gerarXmlDisciplinas($todasDisciplinas);
        // Substitui o parágrafo inteiro que contém [disciplinas]
        $xml = preg_replace(
            '/<w:p\b[^>]*>(?:(?!<w:p[ >]).)*?\[disciplinas\].*?<\/w:p>/s',
            $xmlDisciplinas,
            $xml
        );

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        return $tmp;
    }

    /**
     * Gera parágrafos Word XML para cada disciplina.
     * Formato visual: Matemática......(11)  Onze  Valores
     */
    private function gerarXmlDisciplinas(array $disciplinas): string
    {
        if (empty($disciplinas)) {
            $disciplinas = [
                [
                    'disciplina' => '—',
                    'media_final' => null,
                    'extenso' => '—',
                ]
            ];
        }

        $paragrafos = '';

        foreach ($disciplinas as $linha) {

            $nota = $linha['media_final'] !== null
                ? str_pad(
                    (int) round($linha['media_final']),
                    2,
                    '0',
                    STR_PAD_LEFT
                )
                : '—';

            $nome = $linha['disciplina'];
            $extenso = $linha['extenso'];

            $nomeSafe = htmlspecialchars(
                $nome,
                ENT_XML1 | ENT_QUOTES,
                'UTF-8'
            );

            $notaSafe = htmlspecialchars(
                "($nota)",
                ENT_XML1 | ENT_QUOTES,
                'UTF-8'
            );

            $extensoSafe = htmlspecialchars(
                $extenso,
                ENT_XML1 | ENT_QUOTES,
                'UTF-8'
            );

            $paragrafos .= <<<XML
<w:p>
    <w:pPr>
        <w:tabs>
            <!-- Pontilhado até à coluna da nota -->
            <w:tab
                w:val="right"
                w:leader="dot"
                w:pos="6700"
            />

            <!-- Média por extenso -->
            <w:tab
                w:val="left"
                w:pos="6900"
            />

            <!-- Palavra Valores -->
            <w:tab
                w:val="left"
                w:pos="7800"
            />
        </w:tabs>

        <w:spacing
            w:before="0"
            w:after="0"
            w:line="240"
            w:lineRule="auto"
        />
    </w:pPr>

    <w:r>
        <w:t xml:space="preserve">{$nomeSafe}</w:t>
    </w:r>

    <w:r>
        <w:tab/>
    </w:r>

    <w:r>
        <w:t xml:space="preserve">{$notaSafe}</w:t>
    </w:r>

    <w:r>
        <w:tab/>
    </w:r>

    <w:r>
        <w:t xml:space="preserve">{$extensoSafe}</w:t>
    </w:r>

    <w:r>
        <w:tab/>
    </w:r>

    <w:r>
        <w:t xml:space="preserve">Valores</w:t>
    </w:r>
</w:p>
XML;
        }

        return $paragrafos;
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

        $notasPorComponente = ['sociocultural' => [], 'cientifica' => [], 'tecnica' => []];
        $somaMedias = 0;
        $totalDisciplinas = 0;

        foreach ($tdps as $tdp) {
            $disciplina = $tdp->classeTurnoDisciplina?->disciplina;
            if (!$disciplina)
                continue;

            $nota = $turmaAluno->notas()
                ->where('turma_disciplina_professor_id', $tdp->id)
                ->where('periodo', 3)
                ->first();

            if (!$nota || $nota->media_final === null)
                continue;

            $mediaFinal = round((float) $nota->media_final * 2) / 2;
            $componente = strtolower($disciplina->componente ?? 'tecnica');
            if (!isset($notasPorComponente[$componente]))
                $componente = 'tecnica';

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
        if ($numero === null)
            return '—';

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

        return $mapa[$valor] ?? (string) $valor;
    }
}