<?php

namespace App\Exports\MiniPauta\Cabecalho;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Escreve o cabeçalho e as estatísticas da mini-pauta.
 */
final readonly class EscritorCabecalhoMiniPauta
{
    private const array ROTULOS_NOTAS = [
        'C10' => 'MAC', 'D10' => 'NPP', 'E10' => 'NPT', 'F10' => 'MT1', 'G10' => 'F. I',
        'H10' => 'MAC', 'I10' => 'NPP', 'J10' => 'NPT', 'K10' => 'MT2', 'L10' => 'F. I',
        'M10' => 'MAC', 'N10' => 'NPP', 'O10' => 'NPT', 'P10' => 'MT3', 'Q10' => 'MFD',
        'R10' => 'EX', 'S10' => 'MF', 'T10' => 'F.I', 'U10' => 'RESUL',
    ];

    /**
     * @param  array{instituicao: string, curso: string, disciplina: string, classe: string, turma: string, anoLetivo: string, sala: string}  $cabecalho
     * @param  array<int, array<string, mixed>>  $alunos
     */
    public function __construct(
        private array $cabecalho,
        private array $alunos,
    ) {}

    /**
     * Preenche os dados letivos, institucionais e estatísticos.
     */
    public function escrever(Worksheet $folha): void
    {
        $folha->setCellValue('A2', mb_strtoupper($this->cabecalho['instituicao']));

        $folha->setCellValue('A3', 'ESTATÍSTICA');

        $folha->setCellValue('C3', '1º TRIM');

        $folha->setCellValue('H3', '2º TRIM');

        $folha->setCellValue('M3', '3º TRIM');

        $folha->setCellValue('U3', 'CLASSE');

        $folha->setCellValue('U4', $this->cabecalho['classe']);

        $folha->setCellValue('U5', 'TURMA');

        $folha->setCellValue('U6', $this->cabecalho['turma']);

        $folha->setCellValue('A7', 'CURSO: '.mb_strtoupper($this->cabecalho['curso']));

        $folha->setCellValue('U7', $this->cabecalho['anoLetivo']);

        $folha->setCellValue('U8', 'SALA');

        $folha->setCellValue('U9', $this->cabecalho['sala']);

        $folha->setCellValue('A9', 'Nº');

        $folha->setCellValue('B9', 'NOME');

        foreach (self::ROTULOS_NOTAS as $celula => $valor) {
            $folha->setCellValue($celula, $valor);
        }

        foreach (['A2', 'A3', 'C3', 'H3', 'M3', 'U3', 'U4', 'U5', 'U6', 'A7', 'A8', 'U7', 'U8', 'U9', 'A9', 'B9', ...array_keys(self::ROTULOS_NOTAS)] as $celula) {
            $folha->getStyle($celula)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        $this->escreverEstatisticas($folha);

        $folha->setCellValue('A8', 'DISCIPLINA: '.mb_strtoupper($this->cabecalho['disciplina']));
    }

    private function escreverEstatisticas(Worksheet $folha): void
    {
        $positivas = \count(array_filter($this->alunos, fn (array $aluno): bool => ($aluno['resultado'] ?? '') === 'APTO'));

        $negativas = \count(array_filter($this->alunos, fn (array $aluno): bool => ($aluno['resultado'] ?? '') === 'N/APTO'));

        $reprovFalt = \count(array_filter($this->alunos, fn (array $aluno): bool => ($aluno['resultado'] ?? '') === 'EEF'));

        $folha->setCellValue('B4', $positivas);

        $folha->setCellValue('B5', $negativas);

        $folha->setCellValue('B6', $reprovFalt);

        $folha->setCellValue('C4', '');
        
        $folha->setCellValue('C5', 'O Sub-Director');
        
        $folha->setCellValue('C6', '');
    }
}
