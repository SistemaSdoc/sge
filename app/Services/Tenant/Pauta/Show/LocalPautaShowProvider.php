<?php

namespace App\Services\Tenant\Pauta\Show;

use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Obtém e autoriza a pauta de uma turma do tenant actual.
 */
final class LocalPautaShowProvider
{
    /**
     * Injeta o presenter comum às pautas locais e remotas.
     */
    public function __construct(private readonly PautaShowPresenter $presenter) {}

    /**
     * Procura a turma local e devolve a pauta se a turma existir.
     *
     * @return array<string, mixed>|null
     */
    public function obter(User $user, string $turmaId, Request $request): ?array
    {
        $turma = Turma::query()
            ->with($this->presenter->relacoesNecessarias())
            ->find($turmaId);

        if ($turma === null) {
            return null;
        }

        // A autorização local é feita antes de preparar ou devolver dados da pauta.
        Gate::forUser($user)->authorize('pauta.view', $turma);

        return $this->presenter->apresentar($turma, $request);
    }
}
