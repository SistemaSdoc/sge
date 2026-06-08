<?php

namespace App\Providers;

use App\Models\Permission;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
        // Gate::policy(Classe::class, ClassePolicy::class);

        // $this->configureDefaults();
        // Superadmin bypassa tudo
        Gate::before(function ($user, $ability) {
            if ($user->roles->pluck('nome')->contains('superadmin')) {
                return true;
            }
        });

        // Registar permissões dinamicamente da DB
        try {
            Permission::all()->each(function ($permission) {
                Gate::define($permission->slug, function ($user) use ($permission) {
                    return $user->roles()
                        ->whereHas('permissions', function ($q) use ($permission) {
                            $q->where('permissions.id', $permission->id);
                        })
                        ->exists();
                });
            });
        } catch (\Exception $e) {
            // Evita erro se a tabela ainda não existir (ex: antes de migrar)
        }
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

        Password::defaults(fn (): ?Password => app()->isProduction()
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
