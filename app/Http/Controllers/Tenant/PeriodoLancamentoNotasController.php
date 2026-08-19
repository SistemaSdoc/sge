<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\PeriodoLancamentoNotas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PeriodoLancamentoNotasController extends Controller
{
    public function edit(Request $request, Instituicao $instituicao): Response
    {
        abort_unless($request->user()->can('pautas.gerirPrazos'), 403);

        $anoLectivo = AnoLectivo::activo();

        abort_if($anoLectivo === null, 404, 'Não existe um ano lectivo activo.');

        $periodosExistentes = PeriodoLancamentoNotas::where('instituicao_id', $instituicao->id)
            ->where('ano_lectivo_id', $anoLectivo->id)
            ->get()
            ->keyBy('periodo');

        $periodos = collect([1, 2, 3])->map(fn (int $periodo) => [
            'periodo' => $periodo,
            'data_inicio' => $periodosExistentes->get($periodo)?->data_inicio?->format('Y-m-d\TH:i') ?? '',
            'data_limite' => $periodosExistentes->get($periodo)?->data_limite?->format('Y-m-d\TH:i') ?? '',
            'tem_prazo' => $periodosExistentes->has($periodo),
            'dentro_do_prazo' => $periodosExistentes->get($periodo)?->dentroDoPrazo() ?? false,
        ]);

        $periodoInicial = $periodos->first(
            fn (array $periodo) => ! $periodo['tem_prazo']
        )['periodo'] ?? 1;

        return Inertia::render('pautas/prazos-lancamento-notas/edit', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
            ],
            'anoLectivo' => [
                'id' => $anoLectivo->id,
                'nome' => $anoLectivo->nome,
            ],
            'periodos' => $periodos,
            'periodoInicial' => $periodoInicial,
        ]);
    }

    public function update(Request $request, Instituicao $instituicao): RedirectResponse
    {
        abort_unless($request->user()->can('pautas.gerirPrazos'), 403);

        $anoLectivo = AnoLectivo::activo();

        abort_if($anoLectivo === null, 404, 'Não existe um ano lectivo activo.');

        $validated = $request->validate([
            'periodo' => ['required', 'integer', 'in:1,2,3'],
            'data_inicio' => ['required', 'date'],
            'data_limite' => ['required', 'date'],
        ]);

        $periodo = (int) $validated['periodo'];
        $periodosExistentes = PeriodoLancamentoNotas::where('instituicao_id', $instituicao->id)
            ->where('ano_lectivo_id', $anoLectivo->id)
            ->get()
            ->keyBy('periodo');

        $periodoAnterior = $periodosExistentes->get($periodo - 1);

        if (
            $periodo > 1
            && (
                ! $periodoAnterior
                || ! $periodoAnterior->data_inicio
                || ! $periodoAnterior->data_limite
            )
        ) {
            throw ValidationException::withMessages([
                'periodo' => 'Só podes configurar este trimestre depois de definires o anterior.',
            ]);
        }

        $inicio = Carbon::parse($validated['data_inicio']);
        $limite = Carbon::parse($validated['data_limite']);

        if ($inicio->gt($limite)) {
            throw ValidationException::withMessages([
                'data_limite' => 'A data limite deve ser igual ou posterior à data de início.',
            ]);
        }

        PeriodoLancamentoNotas::updateOrCreate(
            [
                'instituicao_id' => $instituicao->id,
                'ano_lectivo_id' => $anoLectivo->id,
                'periodo' => $periodo,
            ],
            [
                'data_inicio' => $inicio,
                'data_limite' => $limite,
            ]
        );

        return back()->with('success', 'Prazo de lançamento guardado com sucesso.');
    }
}
