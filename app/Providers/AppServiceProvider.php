<?php

namespace App\Providers;

use App\Models\Central\AnoLectivo;
use App\Models\Tenant\CursoTuteladoProfessor;
use App\Models\Tenant\Documento;
use App\Models\Tenant\ItemPagavel;
use App\Models\Tenant\Pagamento;
use App\Models\Tenant\TurmaAluno;
use App\Models\Tenant\User;
use App\Observers\CursoTuteladoProfessorObserver;
use App\Observers\PagamentoObserver;
use App\Policies\Tenant\AcessManagementPolicy;
use App\Policies\Tenant\AnoLectivoPolicy;
use App\Policies\Tenant\ColegioPolicy;
use App\Policies\Tenant\ConfirmacaoMatriculaPolicy;
use App\Policies\Tenant\DocumentoPolicy;
use App\Policies\Tenant\GrelhaCurricularPolicy;
use App\Policies\Tenant\HorarioPolicy;
use App\Policies\Tenant\ItemPagavelPolicy;
use App\Policies\Tenant\PautaPolicy;
use App\Policies\Tenant\RolePolicy;
use App\Policies\Tenant\UserPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\ExceptionResponse;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

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
        // Event::listen(Registered::class, RegisteredListener::class);

        Inertia::handleExceptionsUsing(function (ExceptionResponse $response): ?ExceptionResponse {
            if (app()->environment(['local', 'testing'])) {
                return null;
            }

            if (! in_array($response->statusCode(), [403, 404, 500, 503], true)) {
                return null;
            }

            if ($response->response->headers->has('X-Inertia')) {
                return null;
            }

            return $response->render('errors/error-page', [
                'status' => $response->statusCode(),
            ])->withSharedData();
        });

        Gate::define('pauta.viewAny', [PautaPolicy::class, 'viewAny']);
        Gate::define('pauta.view', [PautaPolicy::class, 'view']);
        Gate::define('pauta.viewAnyCurso', [PautaPolicy::class, 'viewAnyCurso']);
        Gate::define('grelha-curricular.viewAny', [GrelhaCurricularPolicy::class, 'viewAny']);
        Gate::define('acessos.viewAny', [AcessManagementPolicy::class, 'viewAny']);
        Gate::define('acessos.create', [AcessManagementPolicy::class, 'create']);
        Gate::define('horarios.viewAny', [HorarioPolicy::class, 'viewAny']);

        Gate::policy(ItemPagavel::class, ItemPagavelPolicy::class);
        Gate::policy(AnoLectivo::class, AnoLectivoPolicy::class);

        Gate::policy(Documento::class, DocumentoPolicy::class);
        Gate::policy(TurmaAluno::class, ConfirmacaoMatriculaPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        Gate::define('colegios.viewAny', [ColegioPolicy::class, 'viewAny']);

        Gate::before(function ($user, $ability) {
            return $user->hasRole('SuperAdmin') ? true : null;
        });

        Gate::define('confirmacoes.matricula.viewAny', [ConfirmacaoMatriculaPolicy::class, 'viewAny']);
        Gate::define('confirmacoes.matricula.confirmar', [ConfirmacaoMatriculaPolicy::class, 'confirmar']);

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
            fn (): ?Password => app()->isProduction()
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
