<?php

namespace App\Providers;

use App\Listeners\RegisteredListener;
use App\Models\CursoTuteladoProfessor;
use App\Observers\CursoTuteladoProfessorObserver;
use App\Policies\AcessManagementPolicy;
use App\Policies\ColegioPolicy;
use App\Policies\GrelhaCurricularPolicy;
use App\Policies\HorarioPolicy;
use App\Policies\ConfirmacaoMatriculaPolicy;
use App\Policies\ItemPagavelPolicy;
use App\Policies\PagamentoPolicy;
use App\Policies\PautaPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registar listener para atribuir role padrão a novos utilizadores
        Event::listen(Registered::class, RegisteredListener::class);

        Gate::define('pauta.viewAny', [PautaPolicy::class, 'viewAny']);
        Gate::define('pauta.view', [PautaPolicy::class, 'view']);
        Gate::define('pauta.viewAnyCurso', [PautaPolicy::class, 'viewAnyCurso']);
        Gate::define('grelha-curricular.viewAny', [GrelhaCurricularPolicy::class, 'viewAny']);
        Gate::define('acessos.viewAny', [AcessManagementPolicy::class, 'viewAny']);
        Gate::define('acessos.create', [AcessManagementPolicy::class, 'create']);
        Gate::define('horarios.viewAny', [HorarioPolicy::class, 'viewAny']);

        Gate::define('colegios.viewAny', [ColegioPolicy::class, 'viewAny']);
        // Gate::define('pagamentos.view', [PagamentoPolicy::class, 'viewAny']);
        // Gate::define('pagamentos.gerir', [PagamentoPolicy::class, 'create']);

        // $this->configureDefaults();

        // SuperAdmin tem acesso a tudo automaticamente
        Gate::before(function ($user, $ability) {
            return $user->hasRole('SuperAdmin') ? true : null;
        });

        Gate::define('confirmacao-matricula.viewAny', [ConfirmacaoMatriculaPolicy::class, 'viewAny']);
        Gate::define('confirmacao-matricula.view', [ConfirmacaoMatriculaPolicy::class, 'view']);
        Gate::define('confirmacao-matricula.create', [ConfirmacaoMatriculaPolicy::class, 'create']);


        // Registrar observadores de modelos
        CursoTuteladoProfessor::observe(CursoTuteladoProfessorObserver::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn(): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
