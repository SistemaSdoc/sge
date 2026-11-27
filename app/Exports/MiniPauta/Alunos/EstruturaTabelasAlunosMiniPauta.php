<?php

namespace App\Exports\MiniPauta\Alunos;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Dimensiona as tabelas de alunos sem deslocar os seus elementos fixos.
 */
final class EstruturaTabelasAlunosMiniPauta
{
    public const int DATA_START_ROW = 11;

    private const int PROFESSOR_SPACER_ROWS = 2;

    public function expandirTabelaPrincipal(Worksheet $folha, int $quantidade): void
    {
        $linhaProfessor = $this->localizarLinhaProfessor($folha);
        $linhasDisponiveis = max(0, $linhaProfessor - self::DATA_START_ROW - self::PROFESSOR_SPACER_ROWS);
        $linhasExtra = max(0, $quantidade - $linhasDisponiveis);

        if ($linhasExtra > 0) {
            $folha->insertNewRowBefore($linhaProfessor, $linhasExtra);
        }

        $this->aplicarEstiloLinhaModelo($folha, self::DATA_START_ROW, self::DATA_START_ROW, $quantidade);
    }

    /**
     * Expande a tabela inferior e devolve a linha inicial dos seus dados.
     */
    public function expandirTabelaCadeirante(Worksheet $folha, int $quantidade): ?int
    {
        if ($quantidade === 0) {
            return null;
        }

        $linhaModelo = $this->localizarLinhaModeloSegundaTabela($folha);
        $linhasExtra = max(0, $quantidade - 1);

        if ($linhasExtra > 0) {
            $folha->insertNewRowBefore($linhaModelo, $linhasExtra);
        }

        $linhaModeloOriginal = $linhaModelo + $linhasExtra;
        $this->aplicarEstiloLinhaModelo($folha, $linhaModeloOriginal, $linhaModelo, $quantidade);

        return $linhaModelo;
    }

    private function aplicarEstiloLinhaModelo(Worksheet $folha, int $linhaModelo, int $linhaInicio, int $quantidade): void
    {
        if ($quantidade === 0) {
            return;
        }

        for ($linha = $linhaInicio; $linha < $linhaInicio + $quantidade; $linha++) {
            if ($linha !== $linhaModelo) {
                $folha->duplicateStyle($folha->getStyle("A{$linhaModelo}:U{$linhaModelo}"), "A{$linha}:U{$linha}");
            }

            $folha->getRowDimension($linha)->setRowHeight($folha->getRowDimension($linhaModelo)->getRowHeight());
        }
    }

    private function localizarLinhaProfessor(Worksheet $folha): int
    {
        for ($linha = 1; $linha <= $folha->getHighestRow(); $linha++) {
            if (mb_strtoupper(trim((string) $folha->getCell("B{$linha}")->getValue())) === 'PROFESSOR:') {
                return $linha;
            }
        }

        return 13;
    }

    private function localizarLinhaModeloSegundaTabela(Worksheet $folha): int
    {
        $linhaTitulo = null;

        for ($linha = 1; $linha <= $folha->getHighestRow(); $linha++) {
            $valor = trim((string) $folha->getCell("A{$linha}")->getValue());
            if (str_starts_with(mb_strtoupper($valor), 'DISCIPLINA:')) {
                $linhaTitulo = $linha;
                break;
            }
        }

        if ($linhaTitulo === null) {
            return 15;
        }

        for ($linha = $linhaTitulo + 1; $linha <= $folha->getHighestRow(); $linha++) {
            if (trim((string) $folha->getCell("A{$linha}")->getValue()) === 'Nº') {
                return $linha + 1;
            }
        }

        return $linhaTitulo + 3;
    }
}
