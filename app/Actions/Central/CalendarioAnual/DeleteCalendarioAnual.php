<?php

namespace App\Actions\Central\CalendarioAnual;

use App\Models\Central\CalendarioAnual;
use Illuminate\Support\Facades\Storage;

class DeleteCalendarioAnual
{
    /**
     * Elimina um calendario anual e o seu ficheiro armazenado.
     */
    public function handle(CalendarioAnual $calendario): void
    {
        $caminho = $calendario->ficheiro_path;
        $disco = config('filesystems.default');
        $connection = $calendario->getConnection();

        $connection->transaction(function () use (
            $calendario,
            $caminho,
            $disco,
            $connection
        ): void {
            $calendario->delete();

            if ($caminho) {
                $connection->afterCommit(fn (): bool => Storage::disk($disco)->delete($caminho));
            }
        });
    }
}
