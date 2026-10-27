<?php

namespace App\Exports\MiniPauta;

use App\Exports\MiniPauta\Alunos\EscritorLinhasAlunosMiniPauta;
use App\Exports\MiniPauta\Alunos\EstruturaTabelasAlunosMiniPauta;
use App\Exports\MiniPauta\Cabecalho\EscritorCabecalhoMiniPauta;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Preenche o template da mini-pauta sem reconstruir a folha manualmente.
 */
final class PreenchedorMiniPautaTemplate
{
    public const DATA_START_ROW = EstruturaTabelasAlunosMiniPauta::DATA_START_ROW;

    /**
     * @param  array<int, array<string, mixed>>  $alunos
     * @param  array<int, array<string, mixed>>  $alunosCadeirantes
     */
    public function __construct(
        private readonly string $disciplinaNome,
        private readonly string $sigla,
        private readonly array $alunos,
        private readonly string $curso,
        private readonly string $turma,
        private readonly string $anoLetivo,
        private readonly string $instituicao,
        private readonly string $sala,
        private readonly string $classe,
        private readonly array $alunosCadeirantes = [],
    ) {}

    /**
     * Preenche a folha do workbook com os dados da disciplina e dos alunos.
     */
    public function preencher(Worksheet $folha): void
    {
        $estrutura = new EstruturaTabelasAlunosMiniPauta;
        $estrutura->expandirTabelaPrincipal($folha, count($this->alunos));
        $linhaInicioCadeirantes = $estrutura->expandirTabelaCadeirante($folha, count($this->alunosCadeirantes));

        $cabecalho = new EscritorCabecalhoMiniPauta(
            cabecalho: [
                'instituicao' => $this->instituicao,
                'curso' => $this->curso,
                'disciplina' => $this->disciplinaNome,
                'classe' => $this->classe,
                'turma' => $this->turma,
                'anoLetivo' => $this->anoLetivo,
                'sala' => $this->sala,
            ],
            alunos: $this->alunos,
        );
        $cabecalho->escrever($folha);

        $escritorAlunos = new EscritorLinhasAlunosMiniPauta;
        
        $escritorAlunos->escrever($folha, $this->alunos, self::DATA_START_ROW);

        if ($linhaInicioCadeirantes !== null) {
            $escritorAlunos->escrever($folha, $this->alunosCadeirantes, $linhaInicioCadeirantes);
        }
    }
}
