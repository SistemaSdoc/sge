<?php

namespace App\Actions\Central\CalendarioAnual;

use App\Models\Central\CalendarioAnual as CalendarioAnualModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadCalendarioAnual
{
    public function handle(CalendarioAnualModel $calendario, UploadedFile $ficheiro): void
    {
        $novoCaminho = null;

        try {
            $novoCaminho = $ficheiro->store(
                "calendarios-anuais/{$calendario->getKey()}",
                'public'
            );

            if ($novoCaminho === false) {
                throw new \RuntimeException('Não foi possível guardar o calendário anual.');
            }

            $caminhoAntigo = $calendario->ficheiro_path;

            $calendario->forceFill([
                'ficheiro_path' => $novoCaminho,
                'ficheiro_nome' => $ficheiro->getClientOriginalName(),
            ])->save();

            if ($caminhoAntigo) {
                Storage::disk('public')->delete($caminhoAntigo);
            }
        } catch (\Throwable $exception) {
            if ($novoCaminho) {
                Storage::disk('public')->delete($novoCaminho);
            }

            throw $exception;
        }
    }
}
