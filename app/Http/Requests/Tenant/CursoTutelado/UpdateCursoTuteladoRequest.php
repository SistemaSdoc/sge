<?php

namespace App\Http\Requests\Tenant\CursoTutelado;

use App\Models\Central\Tenant;
use App\Services\Central\TenantService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Valida a actualização de um curso tutelado.
 */
class UpdateCursoTuteladoRequest extends FormRequest
{
    /**
     * A autorização final é feita pela policy do controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $tenantTutorId = $this->input('tenant_tutor_id');

        $this->merge([
            'tenant_tutor_id' => $tenantTutorId ?: null,
        ]);
    }

    /**
     * Obtém as regras da associação local e da tutela.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $hasTutor = filled($this->input('tenant_tutor_id'));

        return [
            'tenant_tutor_id' => ['nullable', 'string'],
            'nivel_ensino_id' => ['required', 'uuid', ...($hasTutor ? [] : ['exists:niveis_ensino,id'])],
            'classes' => ['required', 'array', 'min:1'],
            'classes.*' => ['string', ...($hasTutor ? [] : ['exists:classes,id'])],
        ];
    }

    /**
     * Confirma que o instituto escolhido oferece o curso central associado.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $tenantTutorId = $this->input('tenant_tutor_id');

            if (!$tenantTutorId) {
                return;
            }

            $cursoId = $this->route('cursoTutelado')?->instituicaoCurso?->curso_id;
            $currentTenantId = (string) tenancy()->tenant->getTenantKey();

            if ((string) $tenantTutorId === $currentTenantId) {
                $validator->errors()->add(
                    'tenant_tutor_id',
                    'A instituição tutora deve ser diferente da instituição actual.'
                );

                return;
            }

            if (!$cursoId || !app(TenantService::class)->tutorOffersCourse((string) $tenantTutorId, (string) $cursoId)) {
                $validator->errors()->add(
                    'tenant_tutor_id',
                    'A instituição seleccionada não lecciona este curso.'
                );

                return;
            }

            $tenant = Tenant::query()->find($tenantTutorId);

            if (!$tenant) {
                $validator->errors()->add(
                    'tenant_tutor_id',
                    'A instituição tutora seleccionada não está disponível.'
                );
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
            'tenant_tutor_id.string' => 'A instituição tutora seleccionada é inválida.',
            'nivel_ensino_id.required' => 'Seleccione o nível de ensino.',
            'nivel_ensino_id.exists' => 'O nível de ensino seleccionado não existe.',
            'classes.required' => 'Seleccione pelo menos uma classe.',
            'classes.array' => 'A lista de classes seleccionada é inválida.',
            'classes.min' => 'Seleccione pelo menos uma classe.',
            'classes.*.string' => 'Uma das classes seleccionadas é inválida.',
            'classes.*.exists' => 'Uma das classes seleccionadas já não existe.',
        ];
    }
}
