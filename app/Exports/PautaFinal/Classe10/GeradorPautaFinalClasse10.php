<?php

namespace App\Exports\PautaFinal\Classe10;

use App\Exports\PautaFinal\Shared\Assinaturas\AssinaturasPautaFinal;
use App\Exports\PautaFinal\Shared\Disciplinas\BlocosDisciplinas;
use App\Exports\PautaFinal\Shared\Disciplinas\CopiadorBlocoDisciplina;
use App\Exports\PautaFinal\Shared\Layout\ApresentacaoPautaFinal;
use App\Exports\PautaFinal\Shared\Layout\EstilosPautaFinal;
use App\Exports\PautaFinal\Shared\Layout\InsigniaInstituicao;
use App\Exports\PautaFinal\Shared\Resultados\ResultadoPautaFinal;
use App\Exports\PautaFinal\Shared\Suporte\CoordenadasPlanilha;
use App\Exports\PautaFinal\Shared\Suporte\TextoPlanilha;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Coordena exclusivamente a geração do layout final da 10.ª classe.
 */
final class GeradorPautaFinalClasse10
{
    /**
     * @param  array<string, string|null>  $cabecalho
     * @param  list<array{id: string, name: string, sigla: string}>  $disciplinas
     * @param  list<array<string, mixed>>  $alunos
     */
    public function __construct(
        private readonly array $cabecalho,
        private readonly array $disciplinas,
        private readonly array $alunos,
    ) {}

    /**
     * Redimensiona e preenche cabeçalho, disciplinas, alunos e assinaturas.
     */
    public function gerar(Worksheet $folha): void
    {
        $modeloCabecalho = (new ModeloCabecalhoClasse10)->capturar($folha);

        $assinaturas = new AssinaturasPautaFinal;

        $modeloAssinaturas = $assinaturas->capturarModelo($folha);

        $blocos = new BlocosDisciplinas;

        $disciplinas = (new RedimensionadorDisciplinasClasse10($blocos, new CopiadorBlocoDisciplina))
            ->redimensionar($folha, $this->disciplinas);

        if (\count($folha->getDrawingCollection()) === 0) {
            (new InsigniaInstituicao)->inserir($folha);
        }

        $colunas = $blocos->colunas($blocos->obter($folha, false));

        $colunaResultado = TextoPlanilha::colunaCabecalho($folha, 13, 'RESULTADO');

        $colunaHonra = TextoPlanilha::colunaCabecalho($folha, 13, 'Q. HONRA');

        $resultados = new ResultadoPautaFinal;

        $apresentacao = new ApresentacaoPautaFinal($blocos, $resultados);

        $apresentacao->estilizarDisciplinas($folha, $colunas, false);

        $apresentacao->alinharLegendaEef($folha);

        $apresentacao->ajustarLarguraResultado($folha, $colunaResultado, $this->alunos, $disciplinas);

        $estilos = new EstilosPautaFinal;

        $estilos->expandirLinhas($folha, \count($this->alunos));

        (new EscritorCabecalhoClasse10)->escrever($folha, $this->cabecalho, $modeloCabecalho, $colunaResultado);

        $colunasMedia = array_map(fn (int $coluna): int => $coluna + 3, $colunas);

        (new EscritorAlunosClasse10(new EscritorNotasClasse10($estilos), $resultados, $estilos))
            ->escrever(
                $folha,
                $disciplinas,
                $this->alunos,
                $colunas,
                $colunaResultado,
                $colunaHonra,
                $colunasMedia,
            );

        $linhaAssinaturas = 19 + max(0, \count($this->alunos) - 1);

        $nomes = [$this->cabecalho['class_coordinator'] ?? null, $this->cabecalho['course_coordinator'] ?? null, $this->cabecalho['subdirector'] ?? null];

        $assinaturas->escrever($folha, $modeloAssinaturas, $nomes, $linhaAssinaturas, $colunaResultado);

        $folha->getPageSetup()->setPrintArea('A1:'.CoordenadasPlanilha::celula($colunaHonra, $linhaAssinaturas + 1));
    }
}
