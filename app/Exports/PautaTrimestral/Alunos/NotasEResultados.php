<?php

namespace App\Exports\PautaTrimestral\Alunos;

use App\Exports\PautaTrimestral\Suporte\CoordenadasPlanilha;
use App\Exports\ResultadoPautaVisivel;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Escreve as notas e os resultados finais dos alunos na pauta trimestral.
 */
readonly class NotasEResultados
{
    private const string COR_NOTA_APROVADA = '1411D9';

    private const string COR_NOTA_REPROVADA = 'FF0000';

    /**
     * @param  list<array{id: string, name: string, sigla: string, tdp_id: string}>  $disciplinas
     */
    public function __construct(private readonly array $disciplinas) {}

    /**
     * Escreve cada nota e o respetivo resultado final na linha do aluno.
     *
     * @param  list<array{number: int, name: string, grades: array<string, float|int|null>, result?: string}>  $alunos
     * @param  list<int>  $colunasDisciplinas
     */
    public function escrever(
        Worksheet $folha,
        array $alunos,
        array $colunasDisciplinas,
        int $colunaResultado,
    ): void {
        foreach ($alunos as $indiceAluno => $aluno) {
            $linha = 14 + $indiceAluno;

            foreach ($colunasDisciplinas as $indiceDisciplina => $coluna) {
                $idTdp = $this->disciplinas[$indiceDisciplina]['tdp_id'];

                $nota = $aluno['grades'][$idTdp] ?? null;

                if ($nota === null) {
                    continue;
                }

                $celulaNota = CoordenadasPlanilha::celula($coluna, $linha);

                $folha->setCellValue($celulaNota, $nota);

                $folha->getStyle($celulaNota)->applyFromArray([
                    'font' => [
                        'color' => [
                            'rgb' => (float) $nota < 10
                                ? self::COR_NOTA_REPROVADA
                                : self::COR_NOTA_APROVADA,
                        ],
                    ],
                ]);
            }

            $celulaResultado = CoordenadasPlanilha::celula($colunaResultado, $linha);

            $resultado = ResultadoPautaVisivel::normalizar((string) ($aluno['result'] ?? ''));

            $folha->setCellValue($celulaResultado, $resultado);

            $folha->getStyle($celulaResultado)->getAlignment()
                ->setHorizontal('center')
                ->setVertical('center');

            $corResultado = match (mb_strtoupper($resultado, 'UTF-8')) {
                'APTO' => self::COR_NOTA_APROVADA,
                'N/APTO', 'NÃO APTO', 'EEF' => self::COR_NOTA_REPROVADA,
                default => null,
            };

            if ($corResultado !== null) {
                $folha->getStyle($celulaResultado)->getFont()->getColor()->setARGB('FF'.$corResultado);
            }
        }
    }
}
