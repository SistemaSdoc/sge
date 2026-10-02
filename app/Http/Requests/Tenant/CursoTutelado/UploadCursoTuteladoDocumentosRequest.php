<?php

namespace App\Http\Requests\Tenant\CursoTutelado;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Valida os documentos PDF de um curso tutelado.
 */
class UploadCursoTuteladoDocumentosRequest extends FormRequest
{
    /**
     * A autorização final é feita pela policy do controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Exige os documentos que ainda não existem e valida os seus formatos.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $cursoTutelado = $this->route('cursoTutelado');

        return [
            'criterios_pap' => [
                $cursoTutelado?->criterios_pap_path ? 'nullable' : 'required',
                'file',
                'mimes:pdf',
                'max:10240',
            ],
            'manual_pt' => [
                $cursoTutelado?->manual_pt_path ? 'nullable' : 'required',
                'file',
                'mimes:pdf',
                'max:10240',
            ],
            'estrutura_trabalho_pap' => [
                $cursoTutelado?->estrutura_trabalho_pap_path ? 'nullable' : 'required',
                'file',
                'mimes:pdf',
                'max:10240',
            ],
        ];
    }

    /**
     * Mensagens apresentadas ao utilizador durante a validação.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'criterios_pap.required' => 'Seleccione o ficheiro PDF dos critérios do PAP.',
            'criterios_pap.file' => 'Os critérios do PAP devem ser enviados como ficheiro.',
            'criterios_pap.uploaded' => 'O servidor não conseguiu receber os critérios do PAP. Tente novamente.',
            'criterios_pap.mimes' => 'Os critérios do PAP devem estar no formato PDF.',
            'criterios_pap.max' => 'O ficheiro dos critérios do PAP não pode ultrapassar 10 MB.',
            'manual_pt.required' => 'Seleccione o manual de Português em PDF.',
            'manual_pt.file' => 'O manual de Português deve ser enviado como ficheiro.',
            'manual_pt.uploaded' => 'O servidor não conseguiu receber o manual de Português. Tente novamente.',
            'manual_pt.mimes' => 'O manual de Português deve estar no formato PDF.',
            'manual_pt.max' => 'O manual de Português não pode ultrapassar 10 MB.',
            'estrutura_trabalho_pap.required' => 'Seleccione a estrutura do trabalho PAP em PDF.',
            'estrutura_trabalho_pap.file' => 'A estrutura do trabalho PAP deve ser enviada como ficheiro.',
            'estrutura_trabalho_pap.uploaded' => 'O servidor não conseguiu receber a estrutura do trabalho PAP. Tente novamente.',
            'estrutura_trabalho_pap.mimes' => 'A estrutura do trabalho PAP deve estar no formato PDF.',
            'estrutura_trabalho_pap.max' => 'O ficheiro da estrutura do trabalho PAP não pode ultrapassar 10 MB.',
        ];
    }

    /**
     * Regista apenas metadados quando PHP rejeita um upload antes da validação.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (): void {
            foreach (['criterios_pap', 'manual_pt', 'estrutura_trabalho_pap'] as $campo) {
                $ficheiro = $this->file($campo);

                if ($ficheiro && $ficheiro->getError() !== UPLOAD_ERR_OK) {
                    Log::warning('Falha no upload de documento PAP', [
                        'campo' => $campo,
                        'erro' => $ficheiro->getError(),
                        'mensagem' => $ficheiro->getErrorMessage(),
                        'tamanho' => $ficheiro->getSize(),
                        'nome' => $ficheiro->getClientOriginalName(),
                    ]);
                }
            }
        });
    }
}
