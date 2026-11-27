<?php

namespace App\Exports\PautaFinal\Shared\Suporte;

use Illuminate\Support\Str;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Centraliza a leitura e normalização de rótulos dos templates finais.
 */
final class TextoPlanilha
{
    /**
     * Extrai o texto simples de uma célula, incluindo conteúdos RichText.
     */
    public static function celula(Worksheet $folha, string $referencia): string
    {
        $valor = $folha->getCell($referencia)->getValue();

        return $valor instanceof RichText ? $valor->getPlainText() : (string) ($valor ?? '');
    }

    /**
     * Normaliza rótulos para comparação, ignorando espaços e pontuação.
     */
    public static function normalizar(string $texto): string
    {
        return preg_replace('/[^A-Z0-9]/', '', Str::ascii(mb_strtoupper(trim($texto), 'UTF-8'))) ?? '';
    }

    /**
     * Localiza uma coluna pelo rótulo apresentado numa linha do template.
     */
    public static function colunaCabecalho(Worksheet $folha, int $linha, string $rotulo): int
    {
        $alvo = self::normalizar($rotulo);
        $ultimaColuna = Coordinate::columnIndexFromString($folha->getHighestColumn());

        for ($coluna = 1; $coluna <= $ultimaColuna; $coluna++) {
            $celula = CoordenadasPlanilha::celula($coluna, $linha);

            if (self::normalizar(self::celula($folha, $celula)) === $alvo) {
                return $coluna;
            }
        }

        throw new InvalidArgumentException("O template não contém a coluna [{$rotulo}].");
    }
}
