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
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

/**
 * Valida a definição de uma proposta de tema para o grupo PAP.
 */
class DefinirTemaPapRequest extends FormRequest
{
    private ?Collection $sugestoesDisponiveis = null;

    /**
     * Mantém a autorização no controller, conforme o padrão actual do módulo.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Devolve as regras da proposta, incluindo as sugestões ainda disponíveis.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(TemaPapUnicidadeService $temaPapUnicidadeService): array
    {
        $cursoTutelado = $this->cursoTutelado();
        $turma = $this->turma();
        $cursoClasseTurno = $this->cursoClasseTurno();
        $grupoPap = $this->grupoPap();

        $idsSugestoes = $this->sugestoesDisponiveis($temaPapUnicidadeService)
            ->pluck('id')
            ->map(fn ($id): string => (string) $id)
            ->all();

        return [
            'origem_tema' => ['required', Rule::in(['sugerido', 'autoral'])],
            'tema_sugerido_id' => [
                'nullable',
                'required_if:origem_tema,sugerido',
                'string',
                Rule::in($idsSugestoes),
            ],
            'tema_grupo' => [
                Rule::requiredIf($this->input('origem_tema') === 'autoral'),
                'nullable',
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
                    $this->input('tema_grupo'),
                    (string) $grupoPap->getKey(),
                ),
            ],
        ];
    }

    /**
     * Valida a unicidade do título resolvido quando o grupo escolhe uma sugestão.
     */
    public function after(TemaPapUnicidadeService $temaPapUnicidadeService): array
    {
        return [
            function (Validator $validator) use ($temaPapUnicidadeService): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $dados = $this->validationData();
                $campoTema = 'tema_grupo';

                if ($this->input('origem_tema') === 'sugerido') {
                    $sugestao = $this->sugestoesDisponiveis($temaPapUnicidadeService)
                        ->first(fn ($item): bool => (string) data_get($item, 'id') === (string) $this->input('tema_sugerido_id'));

                    if (! $sugestao) {
                        return;
                    }

                    $dados['tema_grupo'] = data_get($sugestao, 'titulo');
                    $campoTema = 'tema_sugerido_id';
                }

                $cursoTutelado = $this->cursoTutelado();
                $turma = $this->turma();
                $cursoClasseTurno = $this->cursoClasseTurno();
                $grupoPap = $this->grupoPap();

                try {
                    $temaPapUnicidadeService->validarUnicidade(
                        $dados,
                        (string) $cursoTutelado->getKey(),
                        (string) $turma->ano_lectivo_id,
                        (string) $cursoClasseTurno->getKey(),
                        (string) $grupoPap->getKey(),
                        $campoTema,
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
     * Devolve as mensagens específicas da definição do tema.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'origem_tema.required' => 'Seleccione como pretende definir o tema.',
            'origem_tema.in' => 'Seleccione uma opção válida para definir o tema.',
            'tema_sugerido_id.string' => 'A sugestão de tema seleccionada não é válida.',
            'tema_grupo.required' => 'Indique o tema proposto pelo grupo.',
            'tema_grupo.string' => 'O tema do grupo deve ser um texto.',
            'tema_grupo.max' => 'O tema do grupo não pode exceder 255 caracteres.',
            'tema_sugerido_id.required_if' => 'Seleccione uma sugestão de tema.',
            'tema_sugerido_id.in' => 'A sugestão seleccionada já não está disponível. Escolha outra.',
            'professor_tutor_id.required' => 'Seleccione o professor tutor do grupo.',
            'professor_tutor_id.exists' => 'O professor tutor seleccionado não existe.',
            'problema.required' => 'Descreva o problema que o trabalho pretende resolver.',
            'problema.string' => 'O problema deve ser um texto.',
            'problema.max' => 'O problema não pode exceder 1000 caracteres.',
            'objectivo_geral.required' => 'Indique o objectivo geral do trabalho.',
            'objectivo_geral.string' => 'O objectivo geral deve ser um texto.',
            'objectivo_geral.max' => 'O objectivo geral não pode exceder 1000 caracteres.',
            'objectivos_especificos.required' => 'Indique os objectivos específicos do trabalho.',
            'objectivos_especificos.string' => 'Os objectivos específicos devem ser um texto.',
            'objectivos_especificos.max' => 'Os objectivos específicos não podem exceder 1000 caracteres.',
            'estudo_caso.required' => 'Descreva o estudo de caso do trabalho.',
            'estudo_caso.string' => 'O estudo de caso deve ser um texto.',
            'estudo_caso.max' => 'O estudo de caso não pode exceder 1000 caracteres.',
        ];
    }

    /**
     * Devolve as sugestões activas e ainda não utilizadas no turno.
     *
     * @return Collection<int, mixed>
     */
    private function sugestoesDisponiveis(TemaPapUnicidadeService $temaPapUnicidadeService): Collection
    {
        if ($this->sugestoesDisponiveis !== null) {
            return $this->sugestoesDisponiveis;
        }

        $cursoTutelado = $this->cursoTutelado();
        $turma = $this->turma();
        $cursoClasseTurno = $this->cursoClasseTurno();
        $grupoPap = $this->grupoPap();

        $titulosUsados = $temaPapUnicidadeService->titulosUsadosNoTurno(
            (string) $cursoTutelado->getKey(),
            (string) $turma->ano_lectivo_id,
            (string) $cursoClasseTurno->getKey(),
            (string) $grupoPap->getKey(),
        );

        $this->sugestoesDisponiveis = collect($cursoTutelado->resolverSugestoesTemas())
            ->filter(fn ($sugestao): bool => (bool) data_get($sugestao, 'ativo', true))
            ->reject(fn ($sugestao): bool => $titulosUsados->contains(
                $temaPapUnicidadeService->normalizarTitulo((string) data_get($sugestao, 'titulo'))
            ))
            ->values();

        return $this->sugestoesDisponiveis;
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
