<?php

namespace App\Exports\PautaFinal\Classe12;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Preenche notas históricas e fórmulas específicas do template da 12.ª classe.
 */
final class EscritorNotasClasse12
{
    /**
     * Preenche as notas de um aluno nas colunas fixas por tipo de disciplina.
     *
     * @param  array<string, mixed>  $aluno
     */
    public function escrever(Worksheet $folha, int $linha, array $aluno): void
    {
        $historico = $aluno['previous_grades'] ?? [];
        $notas = $aluno['grades'] ?? [];
        $colunasTransportadas = [
            'lp' => 'D', 'ing' => 'E', 'fai' => 'F', 'edfisica' => 'G',
            'direito' => 'H', 'redes' => 'I', 'bd' => 'J',
        ];

        foreach ($colunasTransportadas as $disciplina => $coluna) {
            $valor = $historico[$disciplina.'-11'] ?? $historico[$disciplina] ?? null;
            if ($valor !== null) {
                $folha->setCellValue($coluna.$linha, (float) $valor);
            }
        }

        $this->escreverDisciplinasTipoDois($folha, $linha, $historico, $notas);
        $this->escreverDisciplinasTipoTres($folha, $linha, $notas);
    }

    /**
     * Preenche disciplinas com médias de base, faltas, exame e CFD.
     *
     * @param  array<string, float|int|null>  $historico
     * @param  array<string, array<string, mixed>>  $notas
     */
    private function escreverDisciplinasTipoDois(Worksheet $folha, int $linha, array $historico, array $notas): void
    {
        $regras = [
            'mat' => ['base10' => 'K', 'base11' => 'L', 'faltas' => 'M', 'mf' => 'N', 'exame' => 'O', 'ca' => 'P', 'cfd' => 'Q'],
            'oae' => ['base10' => 'Z', 'base11' => 'AA', 'faltas' => 'AB', 'mf' => 'AC', 'exame' => 'AD', 'ca' => 'AE', 'cfd' => 'AF'],
            'tic' => ['base10' => 'AG', 'base11' => 'AH', 'faltas' => 'AI', 'mf' => 'AJ', 'exame' => 'AK', 'ca' => 'AL', 'cfd' => 'AM'],
            'iag' => ['base10' => null, 'base11' => 'AN', 'faltas' => 'AO', 'mf' => 'AP', 'exame' => 'AQ', 'ca' => null, 'cfd' => 'AS'],
            'tlp' => ['base10' => 'AX', 'base11' => 'AY', 'faltas' => 'AZ', 'mf' => 'BA', 'exame' => 'BB', 'ca' => null, 'cfd' => 'BC'],
        ];

        foreach ($regras as $disciplina => $colunas) {
            $nota = $notas[$disciplina] ?? [];
            $base10 = $historico[$disciplina] ?? null;
            $base11 = $historico[$disciplina.'-11'] ?? null;

            foreach ([[$colunas['base10'], $base10], [$colunas['base11'], $base11]] as [$coluna, $valor]) {
                if ($coluna !== null && $valor !== null) {
                    $folha->setCellValue($coluna.$linha, (float) $valor);
                }
            }

            foreach (['mf' => 'mf', 'exame' => 'exame', 'ca' => 'ca'] as $campo => $chave) {
                if ($colunas[$campo] !== null && isset($nota[$chave])) {
                    $folha->setCellValue($colunas[$campo].$linha, (float) $nota[$chave]);
                }
            }
            if (isset($nota['faltas'])) {
                $folha->setCellValue($colunas['faltas'].$linha, (int) $nota['faltas']);
            }

            $this->escreverFormulaCfd($folha, $linha, $colunas);
        }
    }

    /**
     * Calcula a CFD conforme as médias disponíveis na pauta.
     *
     * @param  array<string, string|null>  $colunas
     */
    private function escreverFormulaCfd(Worksheet $folha, int $linha, array $colunas): void
    {
        if ($colunas['mf'] === null) {
            return;
        }

        $bases = array_values(array_filter([$colunas['base10'], $colunas['base11']]));
        $media = $colunas['mf'].$linha;
        $exame = $colunas['exame'] === null ? null : $colunas['exame'].$linha;

        if (count($bases) === 2) {
            $calculo = '('.$bases[0].$linha.'+'.$bases[1].$linha.'+'.$media.')/3';
        } elseif (count($bases) === 1) {
            $calculo = '('.$bases[0].$linha.'+'.$media.')/2';
        } elseif ($exame !== null) {
            $calculo = '('.$media.'+'.$exame.')/2';
        } else {
            $calculo = $media;
        }

        $folha->setCellValue($colunas['cfd'].$linha, '=IF('.$media.'="EEF","EEF",'.$calculo.')');
    }

    /**
     * Preenche as disciplinas de terceiro tipo, sem colunas de notas históricas.
     *
     * @param  array<string, array<string, mixed>>  $notas
     */
    private function escreverDisciplinasTipoTres(Worksheet $folha, int $linha, array $notas): void
    {
        $regras = [
            'direito12' => ['faltas' => 'R', 'media' => 'S', 'exame' => 'T', 'cfd' => 'U'],
            'sitinfo' => ['faltas' => 'V', 'media' => 'W', 'exame' => 'X', 'cfd' => 'Y'],
            'emp' => ['faltas' => 'AT', 'media' => 'AU', 'exame' => 'AV', 'cfd' => 'AW'],
            'imei' => ['faltas' => 'BD', 'media' => 'BE', 'exame' => 'BF', 'cfd' => 'BG'],
            'pt' => ['faltas' => 'BH', 'media' => 'BI', 'exame' => 'BJ', 'cfd' => 'BK'],
        ];

        foreach ($regras as $disciplina => $colunas) {
            $nota = $notas[$disciplina] ?? [];
            if ($nota === []) {
                continue;
            }

            foreach (['media' => 'mf', 'exame' => 'exame'] as $campo => $chave) {
                if (isset($nota[$chave])) {
                    $folha->setCellValue($colunas[$campo].$linha, (float) $nota[$chave]);
                }
            }
            if (isset($nota['faltas'])) {
                $folha->setCellValue($colunas['faltas'].$linha, (int) $nota['faltas']);
            }

            if (isset($nota['mf'])) {
                $folha->setCellValue($colunas['cfd'].$linha, $nota['mf']);
            }
        }
    }
}
