<?php

namespace App\Actions\Tenant\Pauta;

use App\Models\Tenant\User;
use App\Services\Tenant\Pauta\Index\LocalPautaIndexProvider;
use App\Services\Tenant\Pauta\Index\PautaIndexContextResolver;
use App\Services\Tenant\Pauta\Index\PautaIndexPresenter;
use App\Services\Tenant\Pauta\Index\RemotePautaIndexProvider;
use Illuminate\Http\Request;

/**
 * Orquestra a preparação dos dados da listagem de pautas.
 */
final class PreparePautaIndex
{
    /**
     * Injeta os componentes que resolvem o acesso, consultam dados e compõem a resposta.
     */
    public function __construct(
        private readonly PautaIndexContextResolver $contextResolver,
        private readonly LocalPautaIndexProvider $localProvider,
        private readonly RemotePautaIndexProvider $remoteProvider,
        private readonly PautaIndexPresenter $presenter,
    ) {}

    /**
     * Prepara a resposta da listagem sem expor a lógica dos providers ao controller.
     *
     * @return array<string, mixed>
     */
    public function handle(User $user, Request $request): array
    {
        $contexto = $this->contextResolver->resolver($user, $request);

        $dadosLocais = $this->localProvider->obter($user, $contexto);

        $dadosRemotos = $this->remoteProvider->obter($user, $contexto);

        return $this->presenter->apresentar($request, $contexto, $dadosLocais, $dadosRemotos);
    }
}
