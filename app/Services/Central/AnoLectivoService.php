<?php

namespace App\Services\Central;

use App\Models\Central\AnoLectivo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AnoLectivoService
{
    public function defaultId(): ?string
    {
        return $this->current()?->getKey()
            ?? $this->next()?->getKey()
            ?? $this->latest()?->getKey();
    }

    public function current(): ?AnoLectivo
    {
        return AnoLectivo::query()
            ->where('data_inicio', '<=', now())
            ->where('data_fim', '>=', now())
            ->orderByDesc('data_inicio')
            ->first();
    }

    public function criar(int $anoInicio): AnoLectivo
    {
        return DB::connection((new AnoLectivo)->getConnectionName())
            ->transaction(function () use ($anoInicio): AnoLectivo {
                $periodo = $this->periodo($anoInicio);

                if (AnoLectivo::withTrashed()->where('ano_inicio', $anoInicio)->exists()) {
                    throw ValidationException::withMessages([
                        'ano_inicio' => 'Já existe ou existiu um ano lectivo para este período.',
                    ]);
                }

                $agora = now();
                $emCurso = $agora->betweenIncluded($periodo['inicio'], $periodo['fim']);

                if ($emCurso) {
                    AnoLectivo::query()->update(['activo' => false]);
                }

                return AnoLectivo::query()->create([
                    'ano_inicio' => $anoInicio,
                    'nome' => "{$anoInicio}/".($anoInicio + 1),
                    'data_inicio' => $periodo['inicio'],
                    'data_fim' => $periodo['fim'],
                    'activo' => $emCurso,
                    'estado' => $emCurso ? 'em_curso' : ($periodo['fim']->lt($agora) ? 'encerrado' : 'planeado'),
                ]);
            });
    }

    public function arquivar(AnoLectivo $anoLectivo): void
    {
        if ($anoLectivo->activo) {
            throw ValidationException::withMessages([
                'ano_inicio' => "O ano lectivo {$anoLectivo->nome} não pode ser arquivado porque está activo e é a referência central actualmente usada pelos tenants. O arquivamento ficará disponível quando este ano deixar de estar activo; a mudança para o próximo ano é automática.",
            ]);
        }

        $anoLectivo->delete();
    }

    public function restaurar(AnoLectivo $anoLectivo): void
    {
        if (! $anoLectivo->trashed()) {
            return;
        }

        $anoLectivo->restore();
    }

    public function next(): ?AnoLectivo
    {
        return AnoLectivo::query()
            ->where('data_inicio', '>', now())
            ->orderBy('data_inicio')
            ->first();
    }

    public function latest(): ?AnoLectivo
    {
        return AnoLectivo::query()
            ->orderByDesc('data_inicio')
            ->first();
    }

    public function find(string $id): ?AnoLectivo
    {
        return AnoLectivo::query()->find($id);
    }

    public function all(): Collection
    {
        return AnoLectivo::query()
            ->orderByDesc('data_inicio')
            ->get();
    }

    public function sincronizarEstado(): void
    {
        DB::connection((new AnoLectivo)->getConnectionName())
            ->transaction(function (): void {
                $agora = now();
                $anoInicioActual = $this->anoInicioDaData($agora);
                $periodoActual = $this->periodo($anoInicioActual);
                $actual = AnoLectivo::withTrashed()
                    ->where('ano_inicio', $anoInicioActual)
                    ->first();

                if ($actual === null) {
                    $actual = AnoLectivo::query()->create([
                        'ano_inicio' => $anoInicioActual,
                        'nome' => "{$anoInicioActual}/".($anoInicioActual + 1),
                        'data_inicio' => $periodoActual['inicio'],
                        'data_fim' => $periodoActual['fim'],
                        'activo' => false,
                        'estado' => 'planeado',
                    ]);
                } elseif ($actual->trashed()) {
                    $actual->restore();
                }

                if (! $actual->data_inicio->equalTo($periodoActual['inicio'])
                    || ! $actual->data_fim->equalTo($periodoActual['fim'])) {
                    throw new \RuntimeException(
                        "O ano lectivo {$actual->nome} tem um período incompatível com o calendário oficial."
                    );
                }

                AnoLectivo::query()
                    ->where('data_fim', '<', $agora)
                    ->update(['activo' => false, 'estado' => 'encerrado']);

                AnoLectivo::query()
                    ->where('data_inicio', '>', $agora)
                    ->whereKeyNot($actual->getKey())
                    ->update(['activo' => false, 'estado' => 'planeado']);

                AnoLectivo::query()
                    ->whereKeyNot($actual->getKey())
                    ->where('activo', true)
                    ->update(['activo' => false]);

                $actual->update([
                    'activo' => true,
                    'estado' => 'em_curso',
                ]);

                $this->criarProximoSeNecessario($actual);
            });
    }

    private function anoInicioDaData(Carbon $data): int
    {
        $ano = $data->year;
        $inicio = Carbon::create(
            $ano,
            (int) config('ano-lectivo.inicio_mes'),
            (int) config('ano-lectivo.inicio_dia'),
        );

        return $data->lt($inicio) ? $ano - 1 : $ano;
    }

    private function criarProximoSeNecessario(AnoLectivo $actual): void
    {
        $minutosRestantes = now()->diffInMinutes($actual->data_fim, false);

        if ($minutosRestantes > config('ano-lectivo.antecedencia_criacao_minutos')) {
            return;
        }

        $anoInicio = $actual->ano_inicio + 1;
        $periodo = $this->periodo($anoInicio);

        $proximo = AnoLectivo::withTrashed()->where('ano_inicio', $anoInicio)->first();

        if ($proximo?->trashed()) {
            Log::warning('O próximo ano lectivo está arquivado; criação automática ignorada.', [
                'ano_inicio' => $anoInicio,
                'ano_lectivo_id' => $proximo->getKey(),
            ]);

            return;
        }

        if ($proximo !== null
            && (! $proximo->data_inicio->equalTo($periodo['inicio'])
                || ! $proximo->data_fim->equalTo($periodo['fim']))) {
            Log::error('Ano lectivo próximo existente com datas incompatíveis; criação automática interrompida.', [
                'ano_inicio' => $anoInicio,
                'ano_lectivo_id' => $proximo->getKey(),
            ]);

            return;
        }

        if ($proximo === null) {
            $proximo = AnoLectivo::query()->create([
                'ano_inicio' => $anoInicio,
                'nome' => "{$anoInicio}/".($anoInicio + 1),
                'data_inicio' => $periodo['inicio'],
                'data_fim' => $periodo['fim'],
                'activo' => false,
                'estado' => config('ano-lectivo.status_inicial_proximo_ano'),
            ]);
        }

        if ($proximo->estado === 'planeado') {
            $proximo->update([
                'estado' => config('ano-lectivo.status_inicial_proximo_ano'),
            ]);
        }
    }

    /** @return array{inicio: Carbon, fim: Carbon} */
    private function periodo(int $anoInicio): array
    {
        $inicio = Carbon::create(
            $anoInicio,
            (int) config('ano-lectivo.inicio_mes'),
            (int) config('ano-lectivo.inicio_dia'),
            (int) config('ano-lectivo.inicio_hora'),
            (int) config('ano-lectivo.inicio_minuto'),
            0,
        );

        return [
            'inicio' => $inicio,
            'fim' => Carbon::create(
                $anoInicio + 1,
                (int) config('ano-lectivo.fim_mes'),
                (int) config('ano-lectivo.fim_dia'),
                (int) config('ano-lectivo.fim_hora'),
                (int) config('ano-lectivo.fim_minuto'),
                59,
            ),
        ];
    }
}
