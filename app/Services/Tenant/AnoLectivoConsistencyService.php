<?php

namespace App\Services\Tenant;

use App\Models\Tenant\AnoLectivo;
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
        $anoInicio = $hoje->month >= config('ano-lectivo.inicio_mes') ? $hoje->year : $hoje->year - 1;

        AnoLectivo::create([
            'data_inicio' => Carbon::create(
                $anoInicio,
                config('ano-lectivo.inicio_mes'),
                config('ano-lectivo.inicio_dia'),
                (int) config('ano-lectivo.inicio_hora'),      // ← ADICIONA
                (int) config('ano-lectivo.inicio_minuto'),    // ← ADICIONA
                0
            ),
            'data_fim' => Carbon::create(
                $anoInicio + 1,
                config('ano-lectivo.fim_mes'),
                config('ano-lectivo.fim_dia'),
                (int) config('ano-lectivo.fim_hora'),
                (int) config('ano-lectivo.fim_minuto'),
                59
            ),
            'activo' => true,
            'estado' => 'em_curso',
        ]);
    }

    private function garantirProximoAno(): void
    {
        $anoAtual = AnoLectivo::query()->where('activo', true)->first();
        if (! $anoAtual) {
            Log::info('Nenhum ano activo encontrado');

            return;
        }

        Log::info("Ano actual: {$anoAtual->nome}, termina em: {$anoAtual->data_fim->format('H:i:s')}");

        $minutosFaltando = now()->diffInMinutes($anoAtual->data_fim, false);
        Log::info("Minutos faltando: {$minutosFaltando}");
        Log::info('Antecedência configurada: '.config('ano-lectivo.antecedencia_criacao_minutos'));

        if ($minutosFaltando <= config('ano-lectivo.antecedencia_criacao_minutos')) {
            Log::info('DENTRO DO PRAZO - Procurando próximo ano...');

            $proximoAno = AnoLectivo::query()
                ->where('data_inicio', '=', $anoAtual->data_fim)
                ->where('estado', 'planeado')
                ->first();

            Log::info('Próximo encontrado: '.($proximoAno ? $proximoAno->nome : 'NENHUM'));

            if ($proximoAno && $proximoAno->estado === 'planeado') {
                Log::info("Atualizando {$proximoAno->nome} para matriculas_abertas");
                $proximoAno->update([
                    'estado' => config('ano-lectivo.status_inicial_proximo_ano'),
                ]);
            } elseif (! $proximoAno) {
                Log::info('Criando novo ano...');
                // ... resto do create
            }
        } else {
            Log::info("FORA DO PRAZO - Faltam {$minutosFaltando} minutos");
        }
    }
}
