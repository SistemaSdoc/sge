<?php

namespace App\Services\Tenant\Pauta;

use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Services\Tenant\Core\RegraAcademicaService;
use App\Services\Tenant\Pauta\Generators\PautaFinalGenerator;
use App\Services\Tenant\Pauta\Generators\PautaRecursoGenerator;
use App\Services\Tenant\Pauta\Generators\PautaTrimestralGenerator;

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
        $resultadoFinal = $this->regraAcademicaService->resolverSituacaoAcademica($ta);

        if ($resultadoFinal['situacao'] === 'recurso') {
            $resultadoRecurso = $this->regraAcademicaService->resolverSituacaoRecurso($ta);

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
