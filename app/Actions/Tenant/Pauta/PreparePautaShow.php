<?php

namespace App\Actions\Tenant\Pauta;

use App\Models\Tenant\User;
use App\Services\Tenant\Pauta\Show\LocalPautaShowProvider;
use App\Services\Tenant\Pauta\Show\RemotePautaShowProvider;
use Illuminate\Http\Request;

/**
 * Obtém os dados da pauta de uma turma.
 */
class PreparePautaShow
{
    /**
     * Injeta os providers locais e remotos da pauta.
     */
    public function __construct(
        private readonly LocalPautaShowProvider $localProvider,
        private readonly RemotePautaShowProvider $remoteProvider,
    ) {}

    /**
     * Autoriza o acesso à turma e prepara os dados da pauta.
     *
     * @return array<string, mixed>
     */
    public function handle(User $user, string $turmaId, Request $request): array
    {
        $dadosLocais = $this->localProvider->obter($user, $turmaId, $request);

        if ($dadosLocais !== null) {
            return $dadosLocais;
        }

        $dadosRemotos = $this->remoteProvider->obter($user, $turmaId, $request);

        abort_unless($dadosRemotos !== null, 404);

        return $dadosRemotos;
    }
}
