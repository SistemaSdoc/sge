<?php

namespace App\Actions\Central\CalendarioAnual;

use App\Models\Central\CalendarioAnual;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadCalendarioAnual
{
    public function handle(CalendarioAnual $calendario, UploadedFile $ficheiro): void
    {
        $novoCaminho = null;

        try {
            $novoCaminho = $ficheiro->store(
                "calendarios-anuais/{$calendario->getKey()}",
                config('filesystems.default')
            );

            if ($novoCaminho === false) {
                throw new \RuntimeException('Não foi possível guardar o calendário anual.');
            }

            $caminhoAntigo = $calendario->ficheiro_path;

            $calendario->forceFill([
                'ficheiro_path' => $novoCaminho,
                'ficheiro_nome' => $ficheiro->getClientOriginalName(),
            ])->save();

            $connection = $calendario->getConnection();

            $inTransaction = $connection->transactionLevel() > 0;

            if ($caminhoAntigo) {
                $disco = config('filesystems.default');

                if ($inTransaction) {
                    $connection->afterCommit(
                        fn (): bool => Storage::disk($disco)->delete($caminhoAntigo)
                    );
                } else {
                    Storage::disk($disco)->delete($caminhoAntigo);
                }
            }

            if ($inTransaction) {
                $connection->afterRollBack(
                    fn (): bool => Storage::disk(config('filesystems.default'))->delete($novoCaminho)
                );
            }
        } catch (\Throwable $exception) {
            if ($novoCaminho) {
                Storage::disk(config('filesystems.default'))->delete($novoCaminho);
            }

            throw $exception;
        }
    }
}
