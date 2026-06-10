<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PautaExport implements WithMultipleSheets
{
    protected array  $disciplinas;
    protected array  $alunos;
    protected string $curso;
    protected string $turma;
    protected string $anoLetivo;
    protected string $instituicao;
    protected string $sala;
    protected string $classe;
    protected string $periodo; // '1', '2', '3' ou 'final'

    /**
     * @param array  $disciplinas  Lista simples de nomes: ['Língua Portuguesa', 'Matemática', ...]
     * @param array  $alunos       Cada aluno:
     *   [
     *     'numero'       => 1,
     *     'nome'         => 'Nome Completo',
     *     'notas'        => [
     *         'Língua Portuguesa' => ['media' => 12.5, 'faltas' => 3],
     *         'Matemática'        => ['media' => 8.0,  'faltas' => 5],
     *         ...
     *     ],
     *     'media_anual'  => 10.5,   // média de todas as disciplinas
     *     'total_faltas' => 8,
     *     'resultado'    => 'Aprovado',
     *   ]
     */
    public function __construct(
        array  $disciplinas,
        array  $alunos,
        string $curso,
        string $turma,
        string $anoLetivo,
        string $instituicao = 'INSTITUTO MÉDIO COMERCIAL DE LUANDA',
        string $sala        = '',
        string $classe      = '',
        string $periodo     = 'final'
    ) {
        $this->disciplinas  = $disciplinas;
        $this->alunos       = $alunos;
        $this->curso        = $curso;
        $this->turma        = $turma;
        $this->anoLetivo    = $anoLetivo;
        $this->instituicao  = $instituicao;
        $this->sala         = $sala;
        $this->classe       = $classe;
        $this->periodo      = $periodo;
    }

    public function sheets(): array
    {
        return [
            new PautaSheetExport(
                disciplinas: $this->disciplinas,
                alunos:      $this->alunos,
                curso:       $this->curso,
                turma:       $this->turma,
                anoLetivo:   $this->anoLetivo,
                instituicao: $this->instituicao,
                sala:        $this->sala,
                classe:      $this->classe,
                periodo:     $this->periodo,
            ),
        ];
    }
}