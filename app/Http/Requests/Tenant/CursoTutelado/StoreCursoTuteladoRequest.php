<?php

namespace App\Http\Requests\Tenant\CursoTutelado;

use App\Enums\TenantStatus;
use App\Models\Central\Tenant;
use App\Services\Central\TenantService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCursoTuteladoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('tenant_tutor_id') === 'propria') {
            $this->merge(['tenant_tutor_id' => '']);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hasTutor = filled($this->input('tenant_tutor_id'))
            && $this->input('tenant_tutor_id') !== 'propria';

        $rules = [
            'curso_id' => [
                'required',
                'uuid',
                Rule::exists(config('tenancy.database.central_connection') . '.cursos', 'id')
                    ->where('status', 1),
            ],
            'tenant_tutor_id' => ['nullable', 'string'],
        ];

        if ($hasTutor) {
            $rules['nivel_ensino_id'] = ['required', 'uuid'];
            $rules['classes']         = ['required', 'array', 'min:1'];
            $rules['classes.*']       = ['uuid'];
        } else {
            $rules['nivel_ensino_id'] = ['required', 'uuid', 'exists:niveis_ensino,id'];
            $rules['classes']         = ['required', 'array', 'min:1'];
            $rules['classes.*']       = ['uuid', 'exists:classes,id'];
        }

        return $rules;
    }

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

            if (!$tenantTutorId) {
                return;
            }

            $tenant = Tenant::query()->find($tenantTutorId);
            $currentTenantId = (string) tenancy()->tenant->getTenantKey();

            if (!$tenant || !in_array($tenant->status, [TenantStatus::ACTIVE, TenantStatus::TRIAL], true)) {
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

    public function messages(): array
    {
        return [
            'curso_id.required'      => 'Seleccione um curso do catálogo central.',
            'curso_id.exists'        => 'O curso seleccionado não está disponível.',
            'nivel_ensino_id.required' => 'Seleccione o nível de ensino.',
            'nivel_ensino_id.exists' => 'O nível de ensino seleccionado não existe.',
            'classes.required'       => 'Seleccione pelo menos uma classe.',
            'classes.min'            => 'Seleccione pelo menos uma classe.',
            'classes.*.uuid'         => 'Uma ou mais classes seleccionadas são inválidas.',
            'classes.*.exists'       => 'Uma ou mais classes seleccionadas não existem.',
        ];
    }
}