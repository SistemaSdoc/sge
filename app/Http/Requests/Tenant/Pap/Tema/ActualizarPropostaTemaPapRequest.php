<?php

namespace App\Http\Requests\Tenant\Pap\Tema;

use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Turma;
use App\Rules\EstudoCasoPapUnico;
use App\Rules\TemaPapUnico;
use App\Services\Tenant\Pap\Tema\TemaPapUnicidadeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

/**
 * Valida a actualização de uma proposta de tema do grupo PAP.
 */
class ActualizarPropostaTemaPapRequest extends FormRequest
{
    /**
     * Mantém a autorização no controller, conforme o padrão actual do módulo.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Devolve as regras de actualização da proposta.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        $cursoTutelado = $this->cursoTutelado();
        $turma = $this->turma();
        $cursoClasseTurno = $this->cursoClasseTurno();
        $grupoPap = $this->grupoPap();

        return [
            'tema_grupo' => [
                'required',
                'string',
                'max:255',
                new TemaPapUnico(
                    (string) $cursoTutelado->getKey(),
                    (string) $turma->ano_lectivo_id,
                    (string) $cursoClasseTurno->getKey(),
                    (string) $grupoPap->getKey(),
                ),
            ],
            'professor_tutor_id' => ['required', 'exists:professores,id'],
            'problema' => ['required', 'string', 'max:1000'],
            'objectivo_geral' => ['required', 'string', 'max:1000'],
            'objectivos_especificos' => ['required', 'string', 'max:1000'],
            'estudo_caso' => [
                'required',
                'string',
                'max:1000',
                new EstudoCasoPapUnico(
                    (string) $cursoTutelado->getKey(),
                    (string) $turma->ano_lectivo_id,
                    (string) $cursoClasseTurno->getKey(),
                    $this->input('tema_grupo', $grupoPap->tema_grupo),
                    (string) $grupoPap->getKey(),
                ),
            ],
        ];
    }

    /**
     * Valida novamente a unicidade do tema e do estudo de caso.
     */
    public function after(TemaPapUnicidadeService $temaPapUnicidadeService): array
    {
        return [
            function (Validator $validator) use ($temaPapUnicidadeService): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $cursoTutelado = $this->cursoTutelado();
                $turma = $this->turma();
                $cursoClasseTurno = $this->cursoClasseTurno();
                $grupoPap = $this->grupoPap();

                try {
                    $temaPapUnicidadeService->validarUnicidade(
                        $this->validationData(),
                        (string) $cursoTutelado->getKey(),
                        (string) $turma->ano_lectivo_id,
                        (string) $cursoClasseTurno->getKey(),
                        (string) $grupoPap->getKey(),
                    );
                } catch (ValidationException $exception) {
                    foreach ($exception->errors() as $campo => $mensagens) {
                        foreach ($mensagens as $mensagem) {
                            $validator->errors()->add($campo, $mensagem);
                        }
                    }
                }
            },
        ];
    }

    /**
     * Devolve as mensagens específicas da actualização da proposta.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tema_grupo.required' => 'Indique o tema proposto pelo grupo.',
            'professor_tutor_id.required' => 'Seleccione o professor tutor do grupo.',
            'professor_tutor_id.exists' => 'O professor tutor seleccionado não existe.',
            'problema.required' => 'Descreva o problema que o trabalho pretende resolver.',
            'objectivo_geral.required' => 'Indique o objectivo geral do trabalho.',
            'objectivos_especificos.required' => 'Indique os objectivos específicos do trabalho.',
            'estudo_caso.required' => 'Descreva o estudo de caso do trabalho.',
        ];
    }

    /**
     * Devolve o curso tutelado associado à rota.
     */
    private function cursoTutelado(): CursoTutelado
    {
        /** @var CursoTutelado $cursoTutelado */
        $cursoTutelado = $this->route('cursoTutelado');

        return $cursoTutelado;
    }

    /**
     * Devolve a turma associada à rota.
     */
    private function turma(): Turma
    {
        /** @var Turma $turma */
        $turma = $this->route('turma');

        return $turma;
    }

    /**
     * Devolve o turno da classe associado à rota.
     */
    private function cursoClasseTurno(): CursoClasseTurno
    {
        /** @var CursoClasseTurno $cursoClasseTurno */
        $cursoClasseTurno = $this->route('cursoClasseTurno');

        return $cursoClasseTurno;
    }

    /**
     * Devolve o grupo associado à rota.
     */
    private function grupoPap(): GrupoPap
    {
        /** @var GrupoPap $grupoPap */
        $grupoPap = $this->route('grupoPap');

        return $grupoPap;
    }
}
