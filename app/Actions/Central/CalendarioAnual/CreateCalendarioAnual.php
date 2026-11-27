<?php

namespace App\Actions\Central\CalendarioAnual;

use App\Models\Central\CalendarioAnual;

class CreateCalendarioAnual
{
    public function __construct(private readonly UploadCalendarioAnual $uploadCalendarioAnual) {}

    /**
     * Cria um calendario anual e guarda o seu ficheiro.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(array $validated): CalendarioAnual
    {
        $connection = (new CalendarioAnual)->getConnection();

        return $connection->transaction(function () use ($validated): CalendarioAnual {
            $calendario = CalendarioAnual::create([
                'ano' => $validated['ano'],
                'ficheiro_path' => '',
                'ficheiro_nome' => '',
                'ativo' => $validated['ativo'] ?? true,
            ]);

            $this->uploadCalendarioAnual->handle(
                $calendario,
                $validated['ficheiro']
            );

            return $calendario->refresh();
        });
    }
}
