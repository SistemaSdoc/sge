<?php

namespace App\Actions\Tenant\CursoTutelado;

use App\Models\Tenant\CursoTutelado;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Gere o armazenamento dos documentos de um curso tutelado.
 */
class UploadCursoTuteladoDocumentos
{
    /**
     * Guarda os novos documentos e remove os caminhos substituídos.
     *
     * @param  array{criterios_pap?: UploadedFile|null, manual_pt?: UploadedFile|null}  $validated
     */
    public function handle(CursoTutelado $cursoTutelado, array $validated): void
    {
        $novosCaminhos = [];

        try {
            foreach (['criterios_pap', 'manual_pt'] as $campo) {
                if (isset($validated[$campo])) {
                    $novosCaminhos[$campo] = $validated[$campo]->store(
                        "cursos-tutelados/{$cursoTutelado->getKey()}/{$campo}",
                        'public'
                    );
                }
            }

            $caminhosAntigos = [
                'criterios_pap' => $cursoTutelado->criterios_pap_path,
                'manual_pt' => $cursoTutelado->manual_pt_path,
            ];

            $cursoTutelado->forceFill([
                'criterios_pap_path' => $novosCaminhos['criterios_pap'] ?? $cursoTutelado->criterios_pap_path,
                'manual_pt_path' => $novosCaminhos['manual_pt'] ?? $cursoTutelado->manual_pt_path,
            ])->save();

            foreach ($caminhosAntigos as $campo => $caminhoAntigo) {
                if (isset($novosCaminhos[$campo]) && $caminhoAntigo) {
                    Storage::disk('public')->delete($caminhoAntigo);
                }
            }
        } catch (\Throwable $exception) {
            foreach ($novosCaminhos as $caminho) {
                Storage::disk('public')->delete($caminho);
            }

            throw $exception;
        }
    }
}
