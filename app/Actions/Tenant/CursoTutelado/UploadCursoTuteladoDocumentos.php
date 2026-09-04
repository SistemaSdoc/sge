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
     * @param  array{criterios_pap?: UploadedFile|null, manual_pt?: UploadedFile|null, estrutura_trabalho_pap?: UploadedFile|null}  $validated
     */
    public function handle(CursoTutelado $cursoTutelado, array $validated): void
    {
        $novosCaminhos = [];

        try {
            $documentos = [
                'criterios_pap' => 'criterios-pap',
                'manual_pt' => 'manual-pt',
                'estrutura_trabalho_pap' => 'estrutura-trabalho-pap',
            ];

            foreach ($documentos as $campo => $diretorio) {
                if (isset($validated[$campo])) {
                    $caminho = $validated[$campo]->store(
                        "cursos-tutelados/{$cursoTutelado->getKey()}/{$diretorio}",
                        'public'
                    );

                    if ($caminho === false) {
                        throw new \RuntimeException("Não foi possível guardar o documento [{$campo}].");
                    }

                    $novosCaminhos[$campo] = $caminho;
                }
            }

            $caminhosAntigos = [
                'criterios_pap' => $cursoTutelado->criterios_pap_path,
                'manual_pt' => $cursoTutelado->manual_pt_path,
                'estrutura_trabalho_pap' => $cursoTutelado->estrutura_trabalho_pap_path,
            ];

            $cursoTutelado->forceFill([
                'criterios_pap_path' => $novosCaminhos['criterios_pap'] ?? $cursoTutelado->criterios_pap_path,
                'manual_pt_path' => $novosCaminhos['manual_pt'] ?? $cursoTutelado->manual_pt_path,
                'estrutura_trabalho_pap_path' => $novosCaminhos['estrutura_trabalho_pap'] ?? $cursoTutelado->estrutura_trabalho_pap_path,
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
