<?php

namespace App\Services\Pauta;

use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Services\Core\RegraAcademicaService;
use App\Services\Pauta\Generators\PautaFinalGenerator;
use App\Services\Pauta\Generators\PautaRecursoGenerator;
use App\Services\Pauta\Generators\PautaTrimestralGenerator;

class PautaService
{
    public function __construct(
        private readonly RegraAcademicaService $regraAcademicaService
    ) {}

    public function gerarPauta(Turma $turma, string|int $periodo, int $perPage = 20, ?string $filtro = null): array
    {
        return $this->resolverGenerator($periodo)->gerar($turma, $perPage, $filtro);
    }

    public function actualizarResultadoAluno(TurmaAluno $ta): void
    {
        $resultadoFinal = $this->regraAcademicaService->avaliarAluno($ta);

        if ($resultadoFinal['situacao'] === 'recurso') {
            $resultadoRecurso = $this->regraAcademicaService->avaliarRecurso($ta);

            $novoResultado = $resultadoRecurso['situacao'] !== 'pendente'
                ? $resultadoRecurso['situacao']
                : 'recurso';

            TurmaAluno::where('id', $ta->id)
                ->update(['resultado' => $novoResultado]);

            return;
        }

        TurmaAluno::where('id', $ta->id)
            ->update(['resultado' => $resultadoFinal['situacao']]);
    }

    private function resolverGenerator(string|int $periodo)
    {
        return match (true) {
            $periodo === 'recurso' || (int) $periodo === 4 => app(PautaRecursoGenerator::class),
            is_numeric($periodo) && (int) $periodo > 0 => app(PautaTrimestralGenerator::class, ['periodo' => (int) $periodo]),
            default => app(PautaFinalGenerator::class),
        };
    }
}
