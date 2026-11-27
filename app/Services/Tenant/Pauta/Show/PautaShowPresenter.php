<?php

namespace App\Services\Tenant\Pauta\Show;

use App\Models\Tenant\Turma;
use App\Services\Tenant\Pauta\PautaService;
use Illuminate\Http\Request;

/**
 * Prepara os dados da pauta apresentados na página de uma turma.
 */
final class PautaShowPresenter
{
    /**
     * Injeta o serviço que gera as notas e resultados da pauta.
     */
    public function __construct(private readonly PautaService $pautaService) {}

    /**
     * Valida o ano lectivo e monta as props da página da pauta.
     *
     * @return array<string, mixed>
     */
    public function apresentar(Turma $turma, Request $request): array
    {
        $anoLectivoId = $request->query('ano_lectivo_id', $turma->ano_lectivo_id);

        abort_if((string) $turma->ano_lectivo_id !== (string) $anoLectivoId, 404);

        $periodo = $request->query('periodo', '1');

        $perPage = min((int) $request->query('per_page', 10), 100);

        $filtro = $request->query('filtro');

        return [
            'cursoTutelado' => $turma->cursoClasseTurno?->cursoClasse?->cursoTutelado?->only('id'),
            'pauta' => $this->pautaService->gerarPauta($turma, $periodo, $perPage, $filtro),
            'periodo' => $periodo,
            'filtro' => $filtro,
            'anoLectivo' => $turma->anoLectivo,
        ];
    }

    /**
     * Define as relações necessárias para apresentar a pauta.
     *
     * @return list<string>
     */
    public function relacoesNecessarias(): array
    {
        return [
            'cursoClasseTurno.cursoClasse.cursoTutelado',
            'cursoClasseTurno.cursoClasse.classe:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'cursoClasseTurno.turno:id,nome',
            'anoLectivo:id,nome',
        ];
    }
}
