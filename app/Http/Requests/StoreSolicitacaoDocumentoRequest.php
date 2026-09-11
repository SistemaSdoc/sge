<?php

namespace App\Http\Requests;

use App\Services\ElegibilidadeDocumentoService;
use Illuminate\Foundation\Http\FormRequest;

class StoreSolicitacaoDocumentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_documento' => ['required', 'string', 'in:'.implode(',', $this->tiposPermitidos())],
            'motivo' => ['required', 'string', 'min:3', 'max:500'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
            'instituicao_emissora_id' => ['nullable', 'string', 'exists:instituicoes,id'],
            'curso_id' => ['nullable', 'string', 'exists:cursos,id'],
            'turma_id' => ['nullable', 'string', 'exists:turmas,id'],
            'classe_id' => ['nullable', 'string', 'exists:classes,id'],
            'ano_lectivo_id' => ['nullable', 'string', 'exists:ano_lectivos,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $aluno = $this->user()?->aluno;

            if (! $aluno) {
                $validator->errors()->add('aluno', 'Não foi possível identificar o aluno autenticado.');

                return;
            }

            $tipo = $this->input('tipo_documento');
            $tiposPermitidos = $this->tiposPermitidos();

            if (! in_array($tipo, $tiposPermitidos, true)) {
                $validator->errors()->add('tipo_documento', 'Tipo de documento inválido para este aluno.');

                return;
            }

            if ($tipo === 'declaracao_com_notas') {
                $classeId = $this->input('classe_id');

                if (blank($classeId)) {
                    $validator->errors()->add('classe_id', 'Selecciona a classe para a declaração com notas.');

                    return;
                }

                $elegibilidade = new ElegibilidadeDocumentoService;
                $classesDisponiveis = $elegibilidade->classesDisponiveisParaDeclaracao($aluno);
                $idsPermitidos = collect($classesDisponiveis)->pluck('id')->all();

                if (! in_array($classeId, $idsPermitidos, true)) {
                    $validator->errors()->add('classe_id', 'A classe seleccionada não está disponível para este tipo de declaração.');
                }
            }

            if ($tipo === 'certificado') {
                $elegibilidade = new ElegibilidadeDocumentoService;

                if (! $elegibilidade->podeSolicitarCertificado($aluno)) {
                    $validator->errors()->add('tipo_documento', 'Este aluno não é elegível para solicitar certificado.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'tipo_documento.required' => 'Selecciona o tipo de documento.',
            'tipo_documento.in' => 'Tipo de documento inválido.',
            'motivo.required' => 'Indica o motivo da solicitação.',
            'motivo.min' => 'O motivo deve ter pelo menos 3 caracteres.',
            'motivo.max' => 'O motivo não pode exceder 500 caracteres.',
            'observacoes.max' => 'As observações não podem exceder 1000 caracteres.',
            'instituicao_emissora_id.exists' => 'A instituição emissora selecionada não existe.',
            'classe_id.exists' => 'A classe selecionada não existe.',
        ];
    }

    protected function tiposPermitidos(): array
    {
        $tipos = collect(config('documentos.types_base', []))
            ->pluck('value')
            ->filter()
            ->values()
            ->all();

        $certificado = config('documentos.certificado.value');

        if ($certificado) {
            $tipos[] = $certificado;
        }

        return $tipos;
    }
}
