<?php

namespace App\Actions\Central\CalendarioAnual;

use App\Models\Central\CalendarioAnual;
use Illuminate\Http\UploadedFile;

class UpdateCalendarioAnual
{
    public function __construct(private readonly UploadCalendarioAnual $uploadCalendarioAnual) {}

    /**
     * Actualiza o ficheiro e o estado de um calendario anual.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(CalendarioAnual $calendario, array $validated): void
    {
        $calendario->getConnection()->transaction(function () use ($calendario,
            $validated
        ): void {
            if (($validated['ficheiro'] ?? null) instanceof UploadedFile) {
                $this->uploadCalendarioAnual->handle(
                    $calendario,
                    $validated['ficheiro']
                );
            }

            $alteracoes = array_filter([
                'ano' => $validated['ano'] ?? null,
                'ativo' => $validated['ativo'] ?? null,
            ], static fn (mixed $valor): bool => $valor !== null);

            if ($alteracoes !== []) {
                $calendario->update($alteracoes);
            }
        });
    }
}
