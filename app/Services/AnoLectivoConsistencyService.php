<?php

namespace App\Services;

use App\Models\AnoLectivo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AnoLectivoConsistencyService
{
    public function sincronizar(): void
    {
        Log::info('Sincronização de Anos Lectivos iniciada');

        // Passo 0: Se tabela está vazia, cria o ano correspondente a hoje
        if (AnoLectivo::withoutTrashed()->count() === 0) {
            $this->criarAnoInicial();
        }

        // Passo 1: Identificar qual ano está vigente agora
        $anoVigente = AnoLectivo::query()
            ->where('data_inicio', '<=', now())
            ->where('data_fim', '>=', now())
            ->orderByDesc('data_inicio')
            ->first();

        if ($anoVigente) {
            // Desativar todos os outros
            $desativados = AnoLectivo::query()
                ->where('id', '!=', $anoVigente->id)
                ->where('activo', true)
                ->update(['activo' => false, 'estado' => 'encerrado']);

            $anoVigente->update(['activo' => true, 'estado' => 'em_curso']);

            Log::info('Ano Lectivo ativo definido', [
                'nome' => $anoVigente->nome,
                'periodo' => $anoVigente->data_inicio->format('d/m/Y H:i:s').' até '.$anoVigente->data_fim->format('d/m/Y H:i:s'),
                'outros_desativados' => $desativados,
            ]);
        } else {
            // Nenhum ano vigente — encerrar todos que já passaram
            AnoLectivo::query()
                ->where('data_fim', '<', now())
                ->where('estado', '!=', 'encerrado')
                ->update(['activo' => false, 'estado' => 'encerrado']);

            // Verificar se é período de vácuo intencional
            $proximoAno = AnoLectivo::query()
                ->where('data_inicio', '>', now())
                ->orderBy('data_inicio')
                ->first();

            if ($proximoAno) {
                // Vácuo intencional — próximo ano já existe, aguardando data de início
                Log::info('Período de transição entre anos lectivos', [
                    'ano_anterior' => 'Encerrado',
                    'ano_proximo' => $proximoAno->nome,
                    'estado' => $proximoAno->estado,
                    'comeca_em' => $proximoAno->data_inicio->format('d/m/Y H:i:s'),
                    'nota' => 'Confirmações de matrícula e inscrições em progresso',
                ]);
            } else {
                // Verdadeiro problema — nenhum ano vigente, nenhum próximo criado
                Log::error('Vácuo não controlado detectado', [
                    'data_atual' => now()->format('d/m/Y H:i:s'),
                    'problema' => 'Nenhum ano vigente e nenhum próximo criado',
                    'acao' => 'Administrador deve criar o próximo ano lectivo',
                ]);
            }
        }

        // Passo 2: Garantir que o próximo ano está pronto
        $this->garantirProximoAno();

        Log::info('Sincronização de Anos Lectivos concluída');
    }

    private function criarAnoInicial(): void
    {
        $hoje = now();

        // Se estamos em julho (vácuo), criar o ano QUE ESTÁ TERMINANDO
        if ($hoje->month == 7) {
            $anoInicio = $hoje->year - 1;
        } else {
            $anoInicio = $hoje->month >= config('ano-lectivo.inicio_mes') ? $hoje->year : $hoje->year - 1;
        }

        $dataInicio = Carbon::create(
            $anoInicio,
            config('ano-lectivo.inicio_mes'),
            config('ano-lectivo.inicio_dia'),
            0, 0, 0
        );

        $dataFim = Carbon::create(
            $anoInicio + 1,
            config('ano-lectivo.fim_mes'),
            config('ano-lectivo.fim_dia'),
            (int) config('ano-lectivo.fim_hora'),
            (int) config('ano-lectivo.fim_minuto'),
            59
        );

        $novoAno = AnoLectivo::create([
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'activo' => true,
            'estado' => 'em_curso',
        ]);

        Log::info('Ano lectivo inicial criado', [
            'nome' => $novoAno->nome,
            'periodo' => $dataInicio->format('d/m/Y H:i:s').' até '.$dataFim->format('d/m/Y H:i:s'),
            'id' => $novoAno->id,
        ]);
    }

    private function garantirProximoAno(): void
    {
        $anoAtual = AnoLectivo::query()
            ->where('activo', true)
            ->first();

        if (! $anoAtual) {
            Log::debug('Nenhum ano lectivo activo para verificar próximo ano');

            return;
        }

        $minutosFaltando = now()->diffInMinutes($anoAtual->data_fim, false);
        $antecedencia = config('ano-lectivo.antecedencia_criacao_minutos');

        // Se faltam menos do que o configurado, criar o próximo ano
        if ($minutosFaltando <= $antecedencia) {
            $anoProximoInicio = $anoAtual->data_fim->year;

            $dataInicioProximo = Carbon::create(
                $anoProximoInicio,
                config('ano-lectivo.inicio_mes'),
                config('ano-lectivo.inicio_dia'),
                0, 0, 0
            );

            $proximoJaExiste = AnoLectivo::query()
                ->whereYear('data_inicio', $anoProximoInicio)
                ->whereMonth('data_inicio', config('ano-lectivo.inicio_mes'))
                ->whereDay('data_inicio', config('ano-lectivo.inicio_dia'))
                ->exists();

            if ($proximoJaExiste) {
                Log::debug('Próximo ano lectivo já existe', [
                    'ano_atual' => $anoAtual->nome,
                    'proximo_ano' => $anoProximoInicio.'/'.($anoProximoInicio + 1),
                ]);

                return;
            }

            $dataFimProximo = Carbon::create(
                $anoProximoInicio + 1,
                config('ano-lectivo.fim_mes'),
                config('ano-lectivo.fim_dia'),
                (int) config('ano-lectivo.fim_hora'),
                (int) config('ano-lectivo.fim_minuto'),
                59
            );

            $proximoAno = AnoLectivo::create([
                'data_inicio' => $dataInicioProximo,
                'data_fim' => $dataFimProximo,
                'activo' => false,
                'estado' => config('ano-lectivo.status_inicial_proximo_ano'),
            ]);

            Log::info('Próximo ano lectivo criado antecipadamente', [
                'nome' => $proximoAno->nome,
                'periodo' => $dataInicioProximo->format('d/m/Y H:i:s').' até '.$dataFimProximo->format('d/m/Y H:i:s'),
                'estado' => $proximoAno->estado,
                'antecedencia' => $antecedencia.' minutos antes do término',
            ]);
        }
    }
}
