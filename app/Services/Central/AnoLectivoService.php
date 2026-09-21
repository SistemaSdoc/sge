<?php

namespace App\Services\Central;

use App\Models\Central\AnoLectivo;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AnoLectivoService
{
    /**
     * Resolve o identificador do ano lectivo actual ou, na sua ausência, do próximo ou último ano.
     */
    public function defaultId(): ?string
    {
        return $this->current()?->getKey()
            ?? $this->next()?->getKey()
            ?? $this->latest()?->getKey();
    }

    /**
     * Obtém o ano lectivo activo cujo período inclui a data actual.
     */
    public function current(): ?AnoLectivo
    {
        return AnoLectivo::query()
            ->where('activo', true)
            ->where('data_inicio', '<=', now())
            ->where('data_fim', '>=', now())
            ->orderByDesc('data_inicio')
            ->first();
    }

    /**
     * Cria um ano lectivo com o período e estado calculados a partir do ano de início.
     *
     * @param  int  $anoInicio  Ano de início do período lectivo.
     */
    public function criar(int $anoInicio): AnoLectivo
    {
        return $this->withStateLock(function () use ($anoInicio): AnoLectivo {
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
        });
    }

    /**
     * Arquiva um ano lectivo que não esteja activo.
     */
    public function arquivar(AnoLectivo $anoLectivo): void
    {
        $this->withStateLock(function () use ($anoLectivo): void {
            $anoLectivo->getConnection()->transaction(function () use ($anoLectivo): void {
                $actual = AnoLectivo::query()
                    ->whereKey($anoLectivo->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($actual->activo) {
                    throw ValidationException::withMessages([
                        'ano_inicio' => "O ano lectivo {$actual->nome} não pode ser arquivado porque está activo e é a referência central actualmente usada pelos institutos. O arquivamento ficará disponível quando este ano deixar de estar activo; a mudança para o próximo ano é automática.",
                    ]);
                }

                $actual->delete();
            });
        });
    }

    /**
     * Restaura um ano lectivo arquivado quando não existe outro registo para o mesmo período.
     */
    public function restaurar(AnoLectivo $anoLectivo): void
    {
        $this->withStateLock(function () use ($anoLectivo): void {
            $anoLectivo->getConnection()->transaction(function () use ($anoLectivo): void {
                $actual = AnoLectivo::withTrashed()
                    ->whereKey($anoLectivo->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $actual->trashed()) {
                    return;
                }

                if (AnoLectivo::query()
                    ->where('ano_inicio', $actual->ano_inicio)
                    ->whereKeyNot($actual->getKey())
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'ano_inicio' => 'Já existe um ano lectivo para este período.',
                    ]);
                }

                $actual->restore();
            });
        });
    }

    /**
     * Obtém o próximo ano lectivo futuro ordenado pela data de início.
     */
    public function next(): ?AnoLectivo
    {
        return AnoLectivo::query()
            ->where('data_inicio', '>', now())
            ->orderBy('data_inicio')
            ->first();
    }

    /**
     * Obtém o ano lectivo mais recente pela data de início.
     */
    public function latest(): ?AnoLectivo
    {
        return AnoLectivo::query()
            ->orderByDesc('data_inicio')
            ->first();
    }

    /**
     * Procura um ano lectivo pelo seu identificador.
     *
     * @param  string  $id  Identificador do ano lectivo.
     */
    public function find(string $id): ?AnoLectivo
    {
        return AnoLectivo::query()->find($id);
    }

    /**
     * Obtém todos os anos lectivos ordenados pela data de início mais recente.
     */
    public function all(): Collection
    {
        return AnoLectivo::query()
            ->orderByDesc('data_inicio')
            ->get();
    }

    /**
     * Sincroniza o ano activo, encerra períodos terminados e prepara o próximo ano.
     */
    public function sincronizarEstado(): void
    {
        $this->withStateLock(function (): void {
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
        });
    }

    /**
     * Executa uma operação de estado protegida por um lock distribuído.
     *
     * @param  Closure(): mixed  $callback  Operação que deve ser executada sob lock.
     */
    private function withStateLock(Closure $callback): mixed
    {
        return Cache::lock('central:ano-lectivo:state', 300)->block(10, $callback);
    }

    /**
     * Calcula o ano de início do período lectivo correspondente a uma data.
     */
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

    /**
     * Cria ou actualiza o próximo ano quando o período actual está próximo do fim.
     */
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

    /**
     * Calcula as datas oficiais de início e fim de um período lectivo.
     *
     * @return array{inicio: Carbon, fim: Carbon}
     */
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
