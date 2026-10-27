<?php

namespace App\Exports\PautaFinal\Classe13;

use App\Services\Tenant\Pauta\PautaFinalDisciplineMapper;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Preenche notas históricas e actuais nas colunas próprias da 13.ª classe.
 */
final class EscritorNotasClasse13
{
    /**
     * Disciplinas concluídas em anos anteriores, apresentadas numa coluna final.
     *
     * @var array<string, string>
     */
    private const DISCIPLINAS_TRANSPORTADAS = [
        'lp' => 'D',
        'ing' => 'E',
        'fai' => 'F',
        'oae' => 'G',
        'imei' => 'H',
        'direito' => 'I',
        'bd' => 'J',
        'redes' => 'K',
        'tic' => 'L',
    ];

    /**
     * Histórico por ordem de classe para os blocos que expõem colunas CA.
     *
     * @var array<string, array<string, string>>
     */
    private const COLUNAS_HISTORICAS = [
        'mat' => ['10' => 'M', '11' => 'N', '12' => 'O'],
        'mat2' => ['10' => 'M', '11' => 'N', '12' => 'O'],
        'tlp' => ['10' => 'Z', '11' => 'AA', '12' => 'AB'],
        'bdrc' => ['12' => 'AG'],
        'iag' => ['12' => 'AM'],
    ];

    /**
     * Blocos das disciplinas da 13.ª: chaves, componentes e coluna CFD.
     *
     * @var array<string, array{chaves: list<string>, faltas: string|null, media: string, cfd: string, honra: string}>
     */
    private const BLOCOS_ACTUAIS = [
        'matematica' => ['chaves' => ['mat2', 'mat'], 'faltas' => 'P', 'media' => 'Q', 'cfd' => 'T', 'honra' => 'T'],
        'sistema' => ['chaves' => ['sitinfo'], 'faltas' => 'U', 'media' => 'V', 'cfd' => 'Y', 'honra' => 'Y'],
        'tlp' => ['chaves' => ['tlp'], 'faltas' => 'AC', 'media' => 'AD', 'cfd' => 'AF', 'honra' => 'AF'],
        'bdrc' => ['chaves' => ['bdrc'], 'faltas' => 'AH', 'media' => 'AI', 'cfd' => 'AL', 'honra' => 'AL'],
        'iag' => ['chaves' => ['iag'], 'faltas' => 'AN', 'media' => 'AO', 'cfd' => 'AR', 'honra' => 'AR'],
        'empreendedorismo' => ['chaves' => ['emp'], 'faltas' => 'AS', 'media' => 'AT', 'cfd' => 'AV', 'honra' => 'AV'],
        'projecto' => ['chaves' => ['pt'], 'faltas' => 'AW', 'media' => 'AX', 'cfd' => 'AZ', 'honra' => 'AZ'],
    ];

    /**
     * Injeta o mapper usado para identificar as disciplinas da turma.
     */
    public function __construct(private readonly PautaFinalDisciplineMapper $disciplineMapper) {}

    /**
     * Escreve histórico, notas actuais e CFD de um aluno.
     *
     * @param  array<string, mixed>  $aluno
     * @param  list<array{id: string, name: string, sigla: string}>  $disciplinas
     */
    public function escrever(Worksheet $folha, int $linha, array $aluno, array $disciplinas): void
    {
        $historico = $aluno['previous_grades'] ?? [];
        $notas = $aluno['grades'] ?? [];
        $chavesActuais = $this->obterChavesActuais($disciplinas);

        $this->escreverDisciplinasTransportadas($folha, $linha, $historico, $notas);
        $this->escreverHistoricoDosBlocos($folha, $linha, $historico);

        foreach (self::BLOCOS_ACTUAIS as $bloco) {
            if (array_intersect($bloco['chaves'], $chavesActuais) === []) {
                continue;
            }

            $nota = $this->obterNotaActual($notas, $bloco['chaves']);

            if ($nota === []) {
                continue;
            }

            if ($bloco['faltas'] !== null && isset($nota['faltas'])) {
                $folha->setCellValue($bloco['faltas'].$linha, (int) $nota['faltas']);
            }

            if (! array_key_exists('mf', $nota) || $nota['mf'] === null) {
                continue;
            }

            $folha->setCellValue($bloco['media'].$linha, (float) $nota['mf']);
            $this->escreverCfd($folha, $linha, $bloco);
        }

        // PAP tem uma única coluna de resultado e não um bloco de CFD.
        $notaPap = $notas['pap'] ?? null;

        if (is_array($notaPap) && isset($notaPap['mf'])) {
            $folha->setCellValue('BA'.$linha, (float) $notaPap['mf']);
        }
    }

    /**
     * Devolve as colunas finais das disciplinas actuais reconhecidas pelo template.
     *
     * @param  list<array{id: string, name: string, sigla: string}>  $disciplinas
     * @return list<string>
     */
    public function colunasQuadroHonra(array $disciplinas): array
    {
        $chavesActuais = $this->obterChavesActuais($disciplinas);
        $colunas = [];

        foreach (self::BLOCOS_ACTUAIS as $bloco) {
            if (array_intersect($bloco['chaves'], $chavesActuais) !== []) {
                $colunas[] = $bloco['honra'];
            }
        }

        if (in_array('pap', $chavesActuais, true)) {
            $colunas[] = 'BA';
        }

        return array_values(array_unique($colunas));
    }

    /**
     * Escreve a nota actual disponível ou, na sua ausência, a mais recente nota histórica.
     *
     * @param  array<string, float|int|null>  $historico
     * @param  array<string, array<string, mixed>>  $notas
     */
    private function escreverDisciplinasTransportadas(Worksheet $folha, int $linha, array $historico, array $notas): void
    {
        foreach (self::DISCIPLINAS_TRANSPORTADAS as $disciplina => $coluna) {
            $nota = $this->obterNotaHistoricaMaisRecente($historico, $disciplina);

            if ($nota !== null) {
                $folha->setCellValue($coluna.$linha, (float) $nota);
            }
        }
    }

    /**
     * Escreve as CA 10.ª/11.ª/12.ª nos blocos que as apresentam.
     *
     * @param  array<string, float|int|null>  $historico
     */
    private function escreverHistoricoDosBlocos(Worksheet $folha, int $linha, array $historico): void
    {
        foreach (self::COLUNAS_HISTORICAS as $disciplina => $colunas) {
            foreach ($colunas as $ordemClasse => $coluna) {
                $nota = $historico[$disciplina.'-'.$ordemClasse] ?? null;

                if ($nota !== null) {
                    $folha->setCellValue($coluna.$linha, (float) $nota);
                }
            }
        }
    }

    /**
     * Calcula a CFD com a média das notas históricas visíveis e da MF actual.
     *
     * @param  array{chaves: list<string>, faltas: string|null, media: string, cfd: string, honra: string}  $bloco
     */
    private function escreverCfd(Worksheet $folha, int $linha, array $bloco): void
    {
        $colunasHistoricas = [];

        foreach ($bloco['chaves'] as $chave) {
            foreach (self::COLUNAS_HISTORICAS[$chave] ?? [] as $coluna) {
                $colunasHistoricas[] = $coluna;
            }
        }

        $celulas = array_map(
            fn (string $coluna): string => $coluna.$linha,
            [...array_values(array_unique($colunasHistoricas)), $bloco['media']],
        );
        $mediaActual = $bloco['media'].$linha;

        $folha->setCellValue(
            $bloco['cfd'].$linha,
            '=IF('.$mediaActual.'="EEF","EEF",AVERAGE('.implode(',', $celulas).'))',
        );
    }

    /**
     * Resolve as chaves canónicas das disciplinas apresentadas pela turma.
     *
     * @param  list<array{id: string, name: string, sigla: string}>  $disciplinas
     * @return list<string>
     */
    private function obterChavesActuais(array $disciplinas): array
    {
        $chaves = [];

        foreach ($disciplinas as $disciplina) {
            $chave = $this->disciplineMapper->resolve($disciplina['sigla'], $disciplina['name']);

            if ($chave !== null) {
                $chaves[] = $chave;
            }
        }

        return array_values(array_unique($chaves));
    }

    /**
     * Obtém as notas da primeira chave actual presente no bloco.
     *
     * @param  array<string, array<string, mixed>>  $notas
     * @param  list<string>  $chaves
     * @return array<string, mixed>
     */
    private function obterNotaActual(array $notas, array $chaves): array
    {
        foreach ($chaves as $chave) {
            if (isset($notas[$chave]) && is_array($notas[$chave])) {
                return $notas[$chave];
            }
        }

        return [];
    }

    /**
     * Procura a nota histórica mais recente para uma disciplina transportada.
     *
     * @param  array<string, float|int|null>  $historico
     */
    private function obterNotaHistoricaMaisRecente(array $historico, string $disciplina): float|int|null
    {
        foreach ([12, 11, 10] as $ordemClasse) {
            $nota = $historico[$disciplina.'-'.$ordemClasse] ?? null;

            if ($nota !== null) {
                return $nota;
            }
        }

        return $historico[$disciplina] ?? null;
    }
}
