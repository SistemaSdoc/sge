<?php
/*
namespace App\Exports;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PautaExport implements WithMultipleSheets
{
    use Exportable;

    protected array $disciplinas;
    protected array $alunos;
    protected string $curso;
    protected string $turma;
    protected string $anoLetivo;
    protected string $instituicao;
    protected string $sala;
    protected string $classe;
    protected string $periodo;
    protected ?string $areaFormacao;
    protected ?string $director;
    protected ?string $logoPath;
    protected ?string $coordenadorTurma;
    protected ?string $coordenadorCurso;
    protected ?string $subdirectorPedagogico;

    public function __construct(
        array $disciplinas,
        array $alunos,
        string $curso,
        string $turma,
        string $anoLetivo,
        string $instituicao = 'INSTITUTO MÉDIO COMERCIAL DE LUANDA',
        string $sala = '',
        string $classe = '',
        string $periodo = 'final',
        ?string $areaFormacao = null,
        ?string $director = null,
        ?string $logoPath = null,
        ?string $coordenadorTurma = null,
        ?string $coordenadorCurso = null,
        ?string $subdirectorPedagogico = null
    ) {
        $this->disciplinas = $disciplinas;
        $this->alunos = $alunos;
        $this->curso = $curso;
        $this->turma = $turma;
        $this->anoLetivo = $anoLetivo;
        $this->instituicao = $instituicao;
        $this->sala = $sala;
        $this->classe = $classe;
        $this->periodo = $periodo;
        $this->areaFormacao = $areaFormacao;
        $this->director = $director;
        $this->logoPath = $logoPath;
        $this->coordenadorTurma = $coordenadorTurma;
        $this->coordenadorCurso = $coordenadorCurso;
        $this->subdirectorPedagogico = $subdirectorPedagogico;
    }

    public function sheets(): array
    {
        return [
            new PautaSheetExport(
                disciplinas: $this->disciplinas,
                alunos: $this->alunos,
                curso: $this->curso,
                turma: $this->turma,
                anoLetivo: $this->anoLetivo,
                instituicao: $this->instituicao,
                sala: $this->sala,
                classe: $this->classe,
                periodo: $this->periodo,
                areaFormacao: $this->areaFormacao,
                director: $this->director,
                logoPath: $this->logoPath,
                coordenadorTurma: $this->coordenadorTurma,
                coordenadorCurso: $this->coordenadorCurso,
                subdirectorPedagogico: $this->subdirectorPedagogico,
            ),
        ];
    }
}*/