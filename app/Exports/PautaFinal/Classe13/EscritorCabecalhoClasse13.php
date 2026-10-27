<?php

namespace App\Exports\PautaFinal\Classe13;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Preenche o cabeçalho e as assinaturas do template final da 13.ª classe.
 */
final class EscritorCabecalhoClasse13
{
    /**
     * Escreve os dados dinâmicos e mantém os textos institucionais fixos do modelo.
     *
     * @param  array<string, string|null>  $cabecalho
     */
    public function escrever(Worksheet $folha, array $cabecalho, int $linhasAdicionais): void
    {
        $classe = trim((string) ($cabecalho['class'] ?? ''));

        if (! str_contains(mb_strtolower($classe), 'classe')) {
            $classe .= ' CLASSE';
        }

        $turma = mb_strtoupper(trim((string) ($cabecalho['turma'] ?? '')), 'UTF-8');
        $area = trim((string) ($cabecalho['area'] ?? ''));
        $curso = trim((string) ($cabecalho['course'] ?? ''));
        $linhaAssinaturas = 19 + $linhasAdicionais;

        $folha->setCellValue('C3', $cabecalho['director'] ?? null);
        $folha->setCellValue('H4', mb_strtoupper((string) ($cabecalho['institution'] ?? ''), 'UTF-8'));
        $folha->setCellValue('A6', 'PAUTA DE APROVEITAMENTO  ANO LECTIVO : '.($cabecalho['year'] ?? ''));
        $folha->setCellValue(
            'A7',
            mb_strtoupper($classe, 'UTF-8').'    TURMA : '.$turma
                .'   ÁREA DE FORMAÇÃO : '.$area.'        CURSO: '.$curso,
        );
        $folha->setCellValue('BB10', 'SALA : '.($cabecalho['room'] ?? ''));
        $folha->setCellValue('C'.$linhaAssinaturas, $cabecalho['class_coordinator'] ?? null);
        $folha->setCellValue('P'.$linhaAssinaturas, $cabecalho['course_coordinator'] ?? null);

        // A coluna AL contém um exemplo sem fonte confirmada no modelo.
        $folha->setCellValue('AL'.$linhaAssinaturas, null);
        $folha->setCellValue('AM'.$linhaAssinaturas, $cabecalho['subdirector'] ?? null);
    }
}
