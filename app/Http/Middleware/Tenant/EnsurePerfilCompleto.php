<?php

namespace App\Http\Middleware\Tenant;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePerfilCompleto
{
    /**
     * Rotas que o utilizador pode aceder mesmo com perfil incompleto.
     *
     * Os nomes finais das rotas do settings são prefixados com
     * `tenant.dashboard.` porque são carregados dentro do grupo
     * com `->name('tenant.dashboard.')` em routes/tenant.php.
     */
    protected array $rotasIsentas = [
        // Página alternativa de completar perfil (se existir)
        'tenant.perfil.*',

        // Settings — onde o aluno completa os dados
        'tenant.dashboard.settings',
        'tenant.dashboard.profile.*',
        'tenant.dashboard.security.*',
        'tenant.dashboard.user-password.*',
        'tenant.dashboard.appearance.*',

        // Auth / sistema
        'logout',
        'password.*',
        'verification.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Sem login → segue (o middleware 'auth' trata disso)
        if (! $user) {
            return $next($request);
        }

        // Só alunos precisam de completar perfil
        if (! $user->hasRole('Aluno')) {
            return $next($request);
        }

        // Vai buscar o candidato associado
        $candidato = $this->resolverCandidato($user);

        // Sem candidato → deixa passar (não é problema deste middleware)
        if (! $candidato) {
            return $next($request);
        }

        // Já completou → segue normalmente
        if ($candidato->perfil_completo) {
            return $next($request);
        }

        // Está numa rota isenta? Deixa passar
        if ($this->isIsenta($request)) {
            return $next($request);
        }

        // ─── Redireciona para completar perfil ───
        return $this->redirecionar($request);
    }

    /**
     * Resolve o candidato a partir do utilizador autenticado.
     *
     * Caminho 1 (preferido): aluno → inscrição → candidato
     * Caminho 2 (fallback):  candidato.user_id = user.id
     */
    protected function resolverCandidato($user): ?object
    {
        $candidato = $user->aluno?->inscricao?->candidato;

        if ($candidato) {
            return $candidato;
        }

        return $user->candidato ?? null;
    }

    /**
     * Verifica se a rota atual está isenta.
     */
    protected function isIsenta(Request $request): bool
    {
        foreach ($this->rotasIsentas as $pattern) {
            if ($request->routeIs($pattern)) {
                return true;
            }
        }

        // Assets / storage / API — nunca redirecionar
        if ($request->is('api/*', 'storage/*', 'build/*')) {
            return true;
        }

        return false;
    }

    /**
     * Redireciona o utilizador para a página de completar perfil.
     *
     * - Inertia (X-Inertia): redirect 302 normal — o Inertia segue.
     * - Stelvio tens que ajustar o $url para a tua pagina nova 
     */

    protected function redirecionar(Request $request): Response
    {
        $url = route('tenant.dashboard.profile.edit');

        if ($request->header('X-Inertia')) {
            return redirect($url)
                ->with('warning', 'Complete os seus dados para continuar.');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message'  => 'Complete o seu perfil para continuar.',
                'redirect' => $url,
            ], 409);
        }

        return redirect($url)
            ->with('warning', 'Complete os seus dados para continuar.');
    }
}