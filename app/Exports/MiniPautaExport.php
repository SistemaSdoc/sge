<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MiniPautaExport implements WithMultipleSheets
{
    protected array $disciplinas;

    protected string $curso;

    protected string $turma;

    protected string $anoLetivo;

    protected string $instituicao;

    protected string $sala;

    protected string $classe;

    /**
     * @param  array  $disciplinas  Array de disciplinas, cada uma com:
     *                              [
     *                              'nome'    => 'LÍNGUA PORTUGUESA',
     *                              'sigla'   => 'LP',          // nome do separador (sheet)
     *                              'alunos'  => [              // colecção/array de alunos
     *                              [
     *                              'numero'  => 1,
     *                              'nome'    => 'Nome Completo do Aluno',
     *                              'processo'=> '12345',
     *                              'notas'   => [
     *                              1 => ['mac'=>12, 'npp'=>13, 'npt'=>14, 'mt'=>13, 'faltas'=>2, 'situacao'=>null],
     *                              2 => ['mac'=>11, 'npp'=>12, 'npt'=>12, 'mt'=>12, 'faltas'=>1, 'situacao'=>null],
     *                              3 => ['mac'=>10, 'npp'=>11, 'npt'=>11, 'mt'=>11, 'faltas'=>0, 'situacao'=>'Aprovado'],
     *                              ],
     *                              'mfd'     => 12,        // Média Final da Disciplina
     *                              'resultado' => 'Aprovado',
     *                              ],
     *                              ],
     *                              ]
     */
    public function __construct(
        array $disciplinas,
        string $curso,
        string $turma,
        string $anoLetivo,
        string $instituicao = 'INSTITUTO MÉDIO COMERCIAL DE LUANDA',
        string $sala = '',
        string $classe = ''
    ) {
        $this->disciplinas = $disciplinas;
        $this->curso = $curso;
        $this->turma = $turma;
        $this->anoLetivo = $anoLetivo;
        $this->instituicao = $instituicao;
        $this->sala = $sala;
        $this->classe = $classe;
    }

    public function sheets(): array
    {
        return collect($this->disciplinas)->map(fn ($disc) => new MiniPautaSheetExport(
            disciplinaNome: $disc['nome'],
            sigla: $disc['sigla'],
            alunos: $disc['alunos'],
            curso: $this->curso,
            turma: $this->turma,
            anoLetivo: $this->anoLetivo,
            instituicao: $this->instituicao,
            sala: $this->sala,
            classe: $this->classe,
        )
        )->toArray();
    }
}
