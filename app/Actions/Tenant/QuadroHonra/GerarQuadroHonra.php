<?php

namespace App\Actions\Tenant\QuadroHonra;

use App\Enums\TipoQuadroHonra;
use App\Models\Tenant\QuadroHonra;
use App\Models\Tenant\QuadroHonraItem;
use App\Models\Tenant\User;
use App\Notifications\Aluno\AlunoQuadroHonraNotification;
use App\Services\Tenant\QuadroHonra\CalcularCandidatosQuadroHonra;
use App\Services\Tenant\QuadroHonra\ResolverCriteriosQuadroHonra;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Gera ou actualiza o quadro e substitui os seus itens numa única transacção. */
class GerarQuadroHonra
{
    public function __construct(
        private readonly CalcularCandidatosQuadroHonra $calcularCandidatos,
        private readonly ResolverCriteriosQuadroHonra $resolverCriterios,
    ) {}

    /**
     * @param  array{tipo: string, ano_lectivo_id: string, curso_tutelado_id?: ?string, media_minima?: numeric-string|float|int, limite_alunos?: numeric-string|int}  $dados
     */
    public function handle(array $dados, User $utilizador): QuadroHonra
    {
        $tipo = TipoQuadroHonra::from($dados['tipo']);
        $cursoTuteladoId = $tipo === TipoQuadroHonra::CURSO
            ? $dados['curso_tutelado_id']
            : null;
        // O âmbito estável faz uma nova geração substituir o quadro do mesmo ano e curso.
        $escopo = $tipo === TipoQuadroHonra::GERAL
            ? TipoQuadroHonra::GERAL->value
            : 'curso:'.$cursoTuteladoId;
        $criterios = $this->resolverCriterios->resolver(
            isset($dados['media_minima']) ? (float) $dados['media_minima'] : null,
            isset($dados['limite_alunos']) ? (int) $dados['limite_alunos'] : null,
        );

        if ($utilizador->instituicao_id === null) {
            abort(403, 'O utilizador não está associado a uma instituição.');
        }

        // O quadro e a lista completa de elegíveis são gravados atomicamente.
        $quadro = DB::transaction(function () use (
            $dados,
            $utilizador,
            $tipo,
            $cursoTuteladoId,
            $escopo,
            $criterios,
        ): QuadroHonra {
            $candidatos = $this->calcularCandidatos->calcular(
                $dados['ano_lectivo_id'],
                $utilizador->instituicao_id,
                $cursoTuteladoId,
                $criterios['media_minima'],
                $criterios['limite_alunos'],
            );

            if ($candidatos->isEmpty()) {
                throw ValidationException::withMessages([
                    'geracao' => 'Não é possível gerar o Quadro de Honra porque não há alunos elegíveis.',
                ]);
            }

            $quadro = QuadroHonra::query()->firstOrNew([
                'ano_lectivo_id' => $dados['ano_lectivo_id'],
                'escopo' => $escopo,
            ]);
            $quadro->fill([
                'tipo' => $tipo,
                'curso_tutelado_id' => $cursoTuteladoId,
                'media_minima' => $criterios['media_minima'],
                'limite_alunos' => $criterios['limite_alunos'],
                'gerado_por' => $utilizador->id,
                'gerado_em' => now(),
            ]);
            $quadro->save();
            $quadro->itens()->delete();

            $quadro->itens()->createMany(
                $candidatos
                    ->values()
                    ->map(fn (array $candidato, int $index): array => [
                        ...$candidato,
                        'posicao' => $index + 1,
                    ])
                    ->all(),
            );

            return $quadro->load('itens');
        });

        $this->notificarAlunos($quadro);

        return $quadro;
    }

    private function notificarAlunos(QuadroHonra $quadro): void
    {
        $quadro->loadMissing(['anoLectivo:id,nome', 'itens.aluno.user']);

        $alunosComUtilizador = $quadro->itens
            ->map(fn (QuadroHonraItem $item): array => [
                'item' => $item,
                'user' => $item->aluno?->user,
            ])
            ->filter(fn (array $destinatario): bool => $destinatario['user'] instanceof User)
            ->unique(fn (array $destinatario): string => (string) $destinatario['user']->getKey());

        if ($alunosComUtilizador->isEmpty()) {
            return;
        }

        $primeiroUtilizador = $alunosComUtilizador->first()['user'];
        $utilizadoresJaNotificados = DatabaseNotification::query()
            // O tipo antigo evita repetir felicitações já gravadas antes da renomeação.
            ->whereIn('type', [
                AlunoQuadroHonraNotification::class,
                'App\\Notifications\\Aluno\\AlunoNoQuadroHonraNotification',
            ])
            ->where('notifiable_type', $primeiroUtilizador->getMorphClass())
            ->where('data->quadro_honra_id', $quadro->getKey())
            ->whereIn(
                'notifiable_id',
                $alunosComUtilizador
                    ->map(fn (array $destinatario): string => (string) $destinatario['user']->getKey())
                    ->all(),
            )
            ->pluck('notifiable_id')
            ->map(fn ($id): string => (string) $id)
            ->all();

        foreach ($alunosComUtilizador as $destinatario) {
            /** @var User $user */
            $user = $destinatario['user'];

            if (in_array((string) $user->getKey(), $utilizadoresJaNotificados, true)) {
                continue;
            }

            $item = $destinatario['item'];
            $user->notify(new AlunoQuadroHonraNotification(
                (string) $quadro->getKey(),
                $quadro->anoLectivo?->nome ?? 'ano lectivo',
                $quadro->tipo->value,
                $item->curso,
                $item->posicao,
                (float) $item->media,
            ));
        }
    }
}
