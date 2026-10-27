<?php

namespace App\Exports\PautaFinal\Classe12;

use App\Exports\PautaFinal\Shared\Layout\EstilosPautaFinal;
use App\Exports\PautaFinal\Shared\Resultados\ResultadoPautaFinal;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Orquestra o preenchimento do layout fixo de pauta final da 12.ª classe.
 */
final class EscritorPautaFinalClasse12
{
    /**
     * @param  array<string, string|null>  $cabecalho
     * @param  list<array<string, mixed>>  $alunos
     */
    public function __construct(
        private readonly array $cabecalho,
        private readonly array $alunos,
    ) {}

    /**
     * Preenche identificação, notas, resultados e a área de impressão.
     */
    public function escrever(Worksheet $folha): void
    {
        $linhaModelo = 21;
        $alunos = collect($this->alunos)
            ->sortBy(fn (array $aluno): string => $aluno['name'] ?? '', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        if ($alunos->count() > 1) {
            $folha->insertNewRowBefore($linhaModelo + 1, $alunos->count() - 1);
        }

        (new EscritorCabecalhoClasse12)->escrever($folha, $this->cabecalho, $alunos->count());
        $notas = new EscritorNotasClasse12;
        $resultados = new ResultadoPautaFinal;
        $estilos = new EstilosPautaFinal;

        foreach ($alunos as $indice => $aluno) {
            $linha = $linhaModelo + $indice;
            if ($indice > 0) {
                $estilos->clonarLinha($folha, $linhaModelo, $linha);
            }

            $folha->setCellValue('A'.$linha, $indice + 1);
            $folha->setCellValue('B'.$linha, $aluno['number'] ?? ($indice + 1));
            $folha->setCellValue('C'.$linha, $aluno['name'] ?? '');
            $notas->escrever($folha, $linha, $aluno);

            $folha->setCellValue('BL'.$linha, $resultados->classe12($aluno));
            $estilos->resultado($folha, 'BL'.$linha, (string) ($aluno['result'] ?? ''));
            $folha->setCellValue('BM'.$linha, '=SUM(Q'.$linha.',Y'.$linha.',AF'.$linha.',AM'.$linha.',AS'.$linha.',AW'.$linha.',BC'.$linha.',BG'.$linha.',BK'.$linha.')/9');
        }

        $folha->getPageSetup()->setPrintArea('A1:BM'.$folha->getHighestRow());
    }
}
