<?php

namespace App\Providers;

use App\Listeners\UpdateLastLoginAt;
use App\Models\Tenant\CursoTuteladoProfessor;
use App\Models\Tenant\ItemPagavel;
use App\Models\Tenant\Pagamento;
use App\Observers\CursoTuteladoProfessorObserver;
use App\Observers\PagamentoObserver;
use App\Policies\Tenant\AcessManagementPolicy;
use App\Policies\Tenant\ColegioPolicy;
use App\Policies\Tenant\ConfirmacaoMatriculaPolicy;
use App\Policies\Tenant\GrelhaCurricularPolicy;
use App\Policies\Tenant\HorarioPolicy;
use App\Policies\Tenant\ItemPagavelPolicy;
use App\Policies\Tenant\PagamentoPolicy;
use App\Policies\Tenant\PautaPolicy;
use App\Models\Tenant\Documento;
use App\Policies\Tenant\DocumentoPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
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
        Event::listen(Registered::class, RegisteredListener::class);

        Gate::define('pauta.viewAny', [PautaPolicy::class, 'viewAny']);
        Gate::define('pauta.view', [PautaPolicy::class, 'view']);
        Gate::define('pauta.viewAnyCurso', [PautaPolicy::class, 'viewAnyCurso']);
        Gate::define('grelha-curricular.viewAny', [GrelhaCurricularPolicy::class, 'viewAny']);
        Gate::define('acessos.viewAny', [AcessManagementPolicy::class, 'viewAny']);
        Gate::define('acessos.create', [AcessManagementPolicy::class, 'create']);
        Gate::define('horarios.viewAny', [HorarioPolicy::class, 'viewAny']);

        Gate::policy(ItemPagavel::class, ItemPagavelPolicy::class);

        Gate::policy(Documento::class, DocumentoPolicy::class);

        Gate::define('colegios.viewAny', [ColegioPolicy::class, 'viewAny']);

        Gate::before(function ($user, $ability) {
            return $user->hasRole('SuperAdmin') ? true : null;
        });

        Gate::define('confirmacao-matricula.viewAny', [ConfirmacaoMatriculaPolicy::class, 'viewAny']);
        Gate::define('confirmacao-matricula.view', [ConfirmacaoMatriculaPolicy::class, 'view']);
        Gate::define('confirmacao-matricula.create', [ConfirmacaoMatriculaPolicy::class, 'create']);

        CursoTuteladoProfessor::observe(CursoTuteladoProfessorObserver::class);
        Pagamento::observe(PagamentoObserver::class);
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
