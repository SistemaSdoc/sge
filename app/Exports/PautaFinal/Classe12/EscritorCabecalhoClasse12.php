<?php

namespace App\Exports\PautaFinal\Classe12;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Preenche os campos dinâmicos do cabeçalho e das assinaturas da 12.ª classe.
 */
final class EscritorCabecalhoClasse12
{
    /**
     * Escreve a identificação institucional, letiva e os responsáveis.
     *
     * @param  array<string, string|null>  $cabecalho
     */
    public function escrever(Worksheet $folha, array $cabecalho, int $quantidadeAlunos): void
    {
        $classe = trim((string) ($cabecalho['class'] ?? ''));
        if (! str_contains(mb_strtolower($classe), 'classe')) {
            $classe .= ' CLASSE';
        }

        $curso = trim((string) ($cabecalho['course'] ?? $cabecalho['area'] ?? ''));
        $turma = trim((string) ($cabecalho['turma'] ?? ''));
        $turno = mb_strtoupper(trim((string) ($cabecalho['shift'] ?? '')), 'UTF-8');
        $linhaAssinaturas = 26 + max(0, $quantidadeAlunos - 1);

        $folha->setCellValue('A10', $cabecalho['director'] ?? null);
        $folha->setCellValue('M11', mb_strtoupper((string) ($cabecalho['institution'] ?? ''), 'UTF-8'));
        $folha->setCellValue('A13', 'PAUTA DE APROVEITAMENTO  ANO LECTIVO : '.($cabecalho['year'] ?? ''));
        $folha->setCellValue(
            'A15',
            mb_strtoupper($classe, 'UTF-8').'    TURMA : '.mb_strtoupper($turma, 'UTF-8')
                .'   ÁREA DE FORMAÇÃO : '.$curso.'        CURSO: '.$curso,
        );
        $folha->setCellValue('A16', '( Regime: REGULAR-'.$turno.')');
        $folha->setCellValue('BL18', 'SALA : '.($cabecalho['room'] ?? ''));
        $folha->setCellValue('C'.$linhaAssinaturas, $cabecalho['class_coordinator'] ?? null);
        $folha->setCellValue('M'.$linhaAssinaturas, $cabecalho['course_coordinator'] ?? null);
        $folha->setCellValue('AK'.$linhaAssinaturas, $cabecalho['subdirector'] ?? null);
    }
}
