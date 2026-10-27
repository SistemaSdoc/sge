<?php

namespace App\Exports\PautaTrimestral\Cabecalho;

use App\Exports\PautaTrimestral\Suporte\CoordenadasPlanilha;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Preenche os dados lectivos e ajusta a área e o curso no cabeçalho.
 */
readonly class DadosLetivos
{
    /**
     * @param  array<string, string|int|null>  $cabecalho
     */
    public function __construct(private readonly array $cabecalho) {}

    /**
     * Guarda o estilo original do curso para preservar o layout do modelo.
     *
     * @return array{estilo: array<string, mixed>, tamanho_letra: int|float|null}
     */
    public function capturarModelo(Worksheet $folha): array
    {
        $estiloCurso = $folha->getStyle('N10');

        return [
            'estilo' => $estiloCurso->exportArray(),
            'tamanho_letra' => $estiloCurso->getFont()->getSize(),
        ];
    }

    /**
     * Escreve a área de formação, o curso, o trimestre, o ano e a sala.
     *
     * @param  array{estilo: array<string, mixed>, tamanho_letra: int|float|null}  $modelo
     */
    public function escrever(
        Worksheet $folha,
        array $modelo,
        int $colunaResultado,
        string $trimestreRomano
    ): void {
        $folha->setCellValue('B10', 'AREA DE FORMAÇÃO: '.$this->valorCabecalho('area'));

        $folha->getStyle('B10')->getFont()->setSize($modelo['tamanho_letra']);

        $inicioCurso = max(3, $colunaResultado - 19);

        $fimCurso = max($inicioCurso, $colunaResultado - 4);

        $intervaloArea = 'B10:'.CoordenadasPlanilha::celula($inicioCurso - 1, 10);

        if ($inicioCurso > 3) {
            $folha->mergeCells($intervaloArea);
        }

        $this->aplicarEstiloLinhaCabecalho($folha, $intervaloArea);

        $intervaloCurso = CoordenadasPlanilha::celula($inicioCurso, 10).':'.CoordenadasPlanilha::celula($fimCurso, 10);

        $folha->setCellValue('N10', null);

        if ($fimCurso > $inicioCurso) {
            $folha->mergeCells($intervaloCurso);
        }

        $folha->setCellValue(CoordenadasPlanilha::celula($inicioCurso, 10), 'CURSO: '.$this->valorCabecalho('course'));

        $folha->getStyle($intervaloCurso)->applyFromArray($modelo['estilo']);

        $this->aplicarEstiloLinhaCabecalho($folha, $intervaloCurso);

        $folha->setCellValue(CoordenadasPlanilha::celula($colunaResultado, 8), "{$trimestreRomano} TRIMESTRE");

        $folha->setCellValue(CoordenadasPlanilha::celula($colunaResultado, 9), 'Ano : '.($this->cabecalho['year'] ?? ''));

        $folha->setCellValue(CoordenadasPlanilha::celula($colunaResultado, 10), 'Sala :'.($this->cabecalho['room'] ?? ''));
    }

    /**
     * Devolve o valor do cabeçalho em maiúsculas para manter o texto consistente com o modelo.
     */
    private function valorCabecalho(string $chave): string
    {
        return mb_strtoupper((string) ($this->cabecalho[$chave] ?? ''), 'UTF-8');
    }

    /**
     * Centraliza e ajusta o alinhamento da linha do cabeçalho para manter o layout do template.
     */
    private function aplicarEstiloLinhaCabecalho(Worksheet $folha, string $intervalo): void
    {
        $folha->getStyle($intervalo)->getAlignment()
            ->setHorizontal('center')
            ->setVertical('center')
            ->setWrapText(false)
            ->setShrinkToFit(true);
    }
}
