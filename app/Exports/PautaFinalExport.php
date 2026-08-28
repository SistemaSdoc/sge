<?php

/*
namespace App\Exports;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PautaFinalExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        protected array $disciplinas,
        protected array $alunos,
        protected string $curso,
        protected string $turma,
        protected string $anoLetivo,
        protected string $instituicao,
        protected string $sala,
        protected string $classe,
        protected ?string $areaFormacao = null,
        protected ?string $director = null,
        protected ?string $logoPath = null,
    ) {}

    public function sheets(): array
    {
        return [
            new PautaFinalSheetExport(
                disciplinas: $this->disciplinas,
                alunos: $this->alunos,
                curso: $this->curso,
                turma: $this->turma,
                anoLetivo: $this->anoLetivo,
                instituicao: $this->instituicao,
                sala: $this->sala,
                classe: $this->classe,
                areaFormacao: $this->areaFormacao,
                director: $this->director,
                logoPath: $this->logoPath,
            ),
        ];
    }
}*/
