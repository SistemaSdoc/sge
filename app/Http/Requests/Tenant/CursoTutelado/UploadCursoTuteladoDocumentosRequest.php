<?php

namespace App\Http\Requests\Tenant\CursoTutelado;

use Illuminate\Foundation\Http\FormRequest;

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
            'criterios_pap.mimes' => 'Os critérios do PAP devem estar no formato PDF.',
            'criterios_pap.max' => 'O ficheiro dos critérios do PAP não pode ultrapassar 10 MB.',
            'manual_pt.required' => 'Seleccione o manual de Português em PDF.',
            'manual_pt.file' => 'O manual de Português deve ser enviado como ficheiro.',
            'manual_pt.mimes' => 'O manual de Português deve estar no formato PDF.',
            'manual_pt.max' => 'O manual de Português não pode ultrapassar 10 MB.',
        ];
    }
}
