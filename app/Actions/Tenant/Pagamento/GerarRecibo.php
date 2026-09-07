<?php

namespace App\Actions\Tenant\Pagamento;

use App\Models\Tenant\Pagamento;
use App\Services\Tenant\Recibos\ReciboPdfService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GerarRecibo
{
    public function __construct(
        private readonly ReciboPdfService $reciboPdfService,
    ) {}

    public function handle(Pagamento $pagamento, bool $forcar = false): string
    {
        if (! $forcar && $this->reciboPdfService->existe($pagamento->recibo_path)) {
            return (string) $pagamento->recibo_path;
        }

        return Cache::store('database')
            ->lock("pagamento:{$pagamento->getKey()}:recibo", 120)
            ->block(30, function () use ($pagamento, $forcar): string {
                $pagamento->refresh();

                if (! $forcar && $this->reciboPdfService->existe($pagamento->recibo_path)) {
                    return (string) $pagamento->recibo_path;
                }

                $numeroRecibo = $this->atribuirNumero($pagamento);
                $caminho = $this->reciboPdfService->gerar($pagamento, $numeroRecibo);

                $pagamento->updateQuietly(['recibo_path' => $caminho]);

                return $caminho;
            });
    }

    private function atribuirNumero(Pagamento $pagamento): string
    {
        $numeroRecibo = DB::connection('tenant')->transaction(function () use ($pagamento): string {
            $registo = Pagamento::on('tenant')
                ->lockForUpdate()
                ->findOrFail($pagamento->getKey());

            if ($registo->numero_recibo) {
                return $registo->numero_recibo;
            }

            $ano = $registo->data_pagamento->year;
            $ultimo = Pagamento::on('tenant')
                ->where('numero_recibo', 'like', "REC-{$ano}-%")
                ->where('instituicao_id', $registo->instituicao_id)
                ->lockForUpdate()
                ->orderByDesc('numero_recibo')
                ->first();

            $proximoNumero = $ultimo
                ? ((int) substr($ultimo->numero_recibo, -6)) + 1
                : 1;

            $numeroRecibo = \sprintf('REC-%d-%06d', $ano, $proximoNumero);
            $registo->updateQuietly(['numero_recibo' => $numeroRecibo]);

            return $numeroRecibo;
        });

        $pagamento->setAttribute('numero_recibo', $numeroRecibo);

        return $numeroRecibo;
    }
}
