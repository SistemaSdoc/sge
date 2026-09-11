<?php

namespace App\Providers;

use App\Models\Tenant\CursoTuteladoProfessor;
use App\Models\Tenant\Documento;
use App\Models\Tenant\ItemPagavel;
use App\Models\Tenant\Pagamento;
use App\Models\Tenant\TurmaAluno;
use App\Observers\CursoTuteladoProfessorObserver;
use App\Observers\PagamentoObserver;
use App\Policies\Tenant\AcessManagementPolicy;
use App\Policies\Tenant\ColegioPolicy;
use App\Policies\Tenant\ConfirmacaoMatriculaPolicy;
use App\Policies\Tenant\DocumentoPolicy;
use App\Policies\Tenant\GrelhaCurricularPolicy;
use App\Policies\Tenant\HorarioPolicy;
use App\Policies\Tenant\ItemPagavelPolicy;
use App\Policies\Tenant\PautaPolicy;
use App\Policies\Tenant\SolicitacaoDocumentoPolicy;
use App\Services\Rupe\RupeGeneratorInterface;
use App\Services\Rupe\RupeGeneratorManual;
use Carbon\CarbonImmutable;
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
        // Registrar binding para o gerador de RUPE
        $this->app->bind(
            RupeGeneratorInterface::class,
            RupeGeneratorManual::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Event::listen(Registered::class, RegisteredListener::class);

        // ===== GATES DE PAUTA =====
        Gate::define('pauta.viewAny', [PautaPolicy::class, 'viewAny']);
        Gate::define('pauta.view', [PautaPolicy::class, 'view']);
        Gate::define('pauta.viewAnyCurso', [PautaPolicy::class, 'viewAnyCurso']);

        // ===== GATES DE GRELHA CURRICULAR =====
        Gate::define('grelha-curricular.viewAny', [GrelhaCurricularPolicy::class, 'viewAny']);

        // ===== GATES DE ACESSOS =====
        Gate::define('acessos.viewAny', [AcessManagementPolicy::class, 'viewAny']);
        Gate::define('acessos.create', [AcessManagementPolicy::class, 'create']);

        // ===== GATES DE HORÁRIOS =====
        Gate::define('horarios.viewAny', [HorarioPolicy::class, 'viewAny']);

        Gate::policy(ItemPagavel::class, ItemPagavelPolicy::class);

        Gate::policy(Documento::class, DocumentoPolicy::class);
        Gate::policy(TurmaAluno::class, ConfirmacaoMatriculaPolicy::class);

        Gate::define('colegios.viewAny', [ColegioPolicy::class, 'viewAny']);

        // ===== GATES DE COLEGIOS =====
        Gate::define('colegios.viewAny', [ColegioPolicy::class, 'viewAny']);

        // ===== GATES DE CONFIRMAÇÃO DE MATRÍCULA =====
        Gate::define('confirmacao-matricula.viewAny', [ConfirmacaoMatriculaPolicy::class, 'viewAny']);
        Gate::define('confirmacao-matricula.view', [ConfirmacaoMatriculaPolicy::class, 'view']);
        Gate::define('confirmacao-matricula.create', [ConfirmacaoMatriculaPolicy::class, 'create']);

        // ===== GATES DE SOLICITAÇÃO DE DOCUMENTOS =====
        Gate::define('decidir', [SolicitacaoDocumentoPolicy::class, 'decidir']);
        Gate::define('marcarComoPago', [SolicitacaoDocumentoPolicy::class, 'marcarComoPago']);
        Gate::define('marcarComoPronto', [SolicitacaoDocumentoPolicy::class, 'marcarComoPronto']);
        Gate::define('marcarComoLevantado', [SolicitacaoDocumentoPolicy::class, 'marcarComoLevantado']);
        Gate::define('view', [SolicitacaoDocumentoPolicy::class, 'view']);
        Gate::define('viewAny', [SolicitacaoDocumentoPolicy::class, 'viewAny']);
        Gate::define('emitir', [SolicitacaoDocumentoPolicy::class, 'emitir']);

        // SuperAdmin tem acesso a tudo automaticamente
        Gate::before(function ($user, $ability) {
            return $user->hasRole('SuperAdmin') ? true : null;
        });

        Gate::define('confirmacao-matricula.viewAny', [ConfirmacaoMatriculaPolicy::class, 'viewAny']);
        Gate::define('confirmacao-matricula.view', [ConfirmacaoMatriculaPolicy::class, 'view']);
        Gate::define('confirmacao-matricula.create', [ConfirmacaoMatriculaPolicy::class, 'create']);

        // ===== OBSERVADORES =====
        // Registrar observadores de modelos
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
