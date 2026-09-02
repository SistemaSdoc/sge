<?php

namespace App\Http\Requests\Tenant\CursoTutelado;

use App\Enums\TenantStatus;
use App\Models\Central\Tenant;
use App\Services\Central\TenantService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Valida os dados para criar um curso tutelado.
 */
class StoreCursoTuteladoRequest extends FormRequest
{
    /**
     * Normaliza valores antigos do formulário para o contrato da API.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('tenant_tutor_id') === 'propria') {
            $this->merge(['tenant_tutor_id' => '']);
        }
    }

    /**
     * A autorização final é feita pela policy do controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtém as regras de criação e tutela externa.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->cursos?->id;

        $rules = [
            // 'curso_id' => ['nullable', 'uuid', 'exists:cursos,id'],
            'nome' => ['nullable', 'string', 'min:2', 'max:255', 'unique:cursos,nome,'.$id],
            'duracao_anos' => ['nullable', 'integer', 'min:1', 'max:10'],
            'nivel_ensino_id' => ['required', 'uuid', 'exists:niveis_ensino,id'],
            'classe_ids' => ['required', 'array', 'min:1'],
            'classe_ids.*' => ['uuid', 'exists:classes,id'],
            'tenant_tutor_id' => ['nullable', 'string'],
        ];

        // if (empty($this->curso_id)) {
        //     $rules['nome'] = ['required', 'string', 'min:2', 'max:255'];
        //     $rules['duracao_anos'] = ['required', 'integer', 'min:1', 'max:10'];
        // }

        return $rules;
    }

    /**
     * Acrescenta validações dependentes do tenant tutor escolhido.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $tenantTutorId = $this->input('tenant_tutor_id');

            if ($tenantTutorId === 'propria') {
                $tenantTutorId = '';
                $this->merge(['tenant_tutor_id' => '']);
            }

            $currentTenant = Tenant::query()->find((string) tenancy()->tenant->getTenantKey());
            $instituicao = $currentTenant
                ? app(TenantService::class)->getInstituicao($currentTenant)
                : null;

            if ($instituicao?->tipo === 'instituto' && $tenantTutorId) {
                $validator->errors()->add('tenant_tutor_id', 'Institutos só podem ter tutela própria.');

                return;
            }

            if (! $tenantTutorId) {
                return;
            }

            $tenant = Tenant::query()->find($tenantTutorId);
            $currentTenantId = (string) tenancy()->tenant->getTenantKey();

            if (! $tenant || ! in_array($tenant->status, [TenantStatus::ACTIVE, TenantStatus::TRIAL], true)) {
                $validator->errors()->add('tenant_tutor_id', 'A instituição tutora não está disponível.');
            }

            if ($tenantTutorId === $currentTenantId) {
                $validator->errors()->add('tenant_tutor_id', 'Para tutela própria, deixe a instituição tutora vazia.');

                return;
            }

            $instituicaoTutora = $tenant ? app(TenantService::class)->getInstituicao($tenant) : null;

            if ($instituicaoTutora?->tipo !== 'instituto') {
                $validator->errors()->add('tenant_tutor_id', 'A instituição tutora deve ser do tipo instituto.');
            }
        });
    }

    /**
     * Mensagens apresentadas ao utilizador durante a validação.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // 'curso_id.uuid' => 'O curso seleccionado é inválido.',
            'nome.unique' => 'Já existe um curso com este nome.',
            'nome.required' => 'O nome do curso é obrigatório.',
            'nome.min' => 'O nome do curso deve ter pelo menos 2 caracteres.',
            'duracao_anos.required' => 'A duração é obrigatória.',
            'duracao_anos.min' => 'A duração deve ser pelo menos 1 ano.',
            'nivel_ensino_id.required' => 'Seleccione o nível de ensino.',
            'nivel_ensino_id.exists' => 'O nível de ensino seleccionado não existe.',
            'classe_ids.required' => 'Seleccione pelo menos uma classe.',
            'classe_ids.min' => 'Seleccione pelo menos uma classe.',
            'classe_ids.*.uuid' => 'Uma ou mais classes seleccionadas são inválidas.',
            'classe_ids.*.exists' => 'Uma ou mais classes seleccionadas não existem.',
        ];
    }
}
