<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Models\Tenant\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class DebugMenus extends Command
{
    protected $signature   = 'debug:menus {--email= : Filtrar por email de utilizador}';
    protected $description = 'Debug de roles, permissões e gates dos menus';

    public function handle(): void
    {
        $tenant = Tenant::first();

        if (! $tenant) {
            $this->error('Nenhum tenant encontrado.');
            return;
        }

        tenancy()->initialize($tenant);

        $this->newLine();
        $this->info("═══════════════════════════════════════════════");
        $this->info("  TENANT: {$tenant->id} ({$tenant->tenancy_db_name})");
        $this->info("═══════════════════════════════════════════════");

        // ── Roles ────────────────────────────────────────────
        $this->newLine();
        $this->info('▸ ROLES (guard_name)');
        foreach (Role::orderBy('name')->get() as $r) {
            $this->line("  [{$r->id}] {$r->name}  →  guard: {$r->guard_name}");
        }

        // ── Professores ──────────────────────────────────────
        $this->newLine();
        $this->info('▸ PROFESSORES (amostra 5)');
        $profs = User::whereHas('professor')->limit(5)->get();
        if ($profs->isEmpty()) {
            $this->warn('  Nenhum utilizador com relação professor.');
        }
        foreach ($profs as $p) {
            $this->line(sprintf(
                '  %-35s | roles: [%s] | hasRole(Professor): %s',
                $p->email,
                $p->getRoleNames()->implode(', ') ?: '—',
                $p->hasRole('Professor') ? '✅ SIM' : '❌ NÃO'
            ));
        }

        // ── Diretores ────────────────────────────────────────
        $this->newLine();
        $this->info('▸ DIRETORES');
        $dirs = User::role('Director')->get();
        foreach ($dirs as $d) {
            $this->line(sprintf(
                '  %-35s | roles: [%s] | hasRole(Director): %s',
                $d->email,
                $d->getRoleNames()->implode(', ') ?: '—',
                $d->hasRole('Director') ? '✅ SIM' : '❌ NÃO'
            ));
        }

        // ── Coordenadores ────────────────────────────────────
        $this->newLine();
        $this->info('▸ COORDENADORES');
        $coords = User::role('Coordenador')->get();
        foreach ($coords as $c) {
            $this->line(sprintf(
                '  %-35s | roles: [%s] | hasRole(Professor): %s | prof.relation: %s',
                $c->email,
                $c->getRoleNames()->implode(', ') ?: '—',
                $c->hasRole('Professor') ? '✅' : '❌',
                $c->professor()->exists() ? '✅' : '❌'
            ));
        }

        // ── Tabela model_has_roles ───────────────────────────
        $this->newLine();
        $this->info('▸ TABELA model_has_roles (roles relevantes)');
        $rows = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->join('users', 'model_has_roles.model_id', '=', 'users.id')
            ->select('users.email', 'roles.name as role', 'roles.guard_name')
            ->whereIn('roles.name', ['Professor', 'Director', 'Coordenador'])
            ->orderBy('roles.name')
            ->limit(30)
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('  Nenhuma atribuição encontrada!');
        }
        foreach ($rows as $r) {
            $this->line(sprintf(
                '  %-35s | %-15s | %s',
                $r->email,
                $r->role,
                $r->guard_name
            ));
        }

        // ── Gates ────────────────────────────────────────────
        $this->newLine();
        $this->info('▸ GATES');

        $prof  = $profs->first();
        $dir   = $dirs->first();
        $coord = $coords->first();

        $abilities = [
            'submissao-prova.viewAny',
            'prazo-prova.viewAny',
            'horarios.viewAny',
        ];

        foreach ($abilities as $ability) {
            $this->line("  ▸ {$ability}");
            if ($prof)  $this->line('      Professor  : ' . (Gate::forUser($prof)->allows($ability) ? '✅' : '❌'));
            if ($dir)   $this->line('      Director   : ' . (Gate::forUser($dir)->allows($ability) ? '✅' : '❌'));
            if ($coord) $this->line('      Coordenador: ' . (Gate::forUser($coord)->allows($ability) ? '✅' : '❌'));
        }

        // ── Utilizador específico ────────────────────────────
        if ($email = $this->option('email')) {
            $this->newLine();
            $this->info("▸ UTILIZADOR ESPECÍFICO: {$email}");

            $u = User::where('email', $email)->first();

            if (! $u) {
                $this->error('  Não encontrado.');
                tenancy()->end();
                return;
            }

            $this->line('  Email: ' . $u->email);
            $this->line('  Roles: ' . ($u->getRoleNames()->implode(', ') ?: '—'));
            $this->line('  isProfessor: ' . ($u->professor()->exists() ? '✅' : '❌'));
            $this->line('  isAluno:     ' . ($u->aluno()->exists() ? '✅' : '❌'));

            $this->newLine();
            $this->info('  Gates (avaliadas como este utilizador):');
            foreach ($abilities as $ability) {
                $this->line("    {$ability}: " . (Gate::forUser($u)->allows($ability) ? '✅' : '❌'));
            }

            // ── Menu real ────────────────────────────────────
            $this->newLine();
            $this->info("  ▸ MENU REAL para {$email}");

            auth('tenant')->setUser($u);

            try {
                $menu = app(\App\Services\Tenant\Menu\SidebarMenuService::class)->build();

                foreach ($menu as $group) {
                    $this->line("    ▸ {$group['title']}");
                    foreach ($group['items'] as $item) {
                        $this->line("        - {$item['title']} (key: {$item['key']})");
                    }
                }

                // Condições específicas do item
                $this->newLine();
                $this->info('    ▸ Condições do item "submeter-provas":');
                $this->line('        hasRole(Professor): ' . ($u->hasRole('Professor') ? '✅' : '❌'));
                $this->line('        Gate submissao-prova.viewAny: ' . (Gate::forUser($u)->allows('submissao-prova.viewAny') ? '✅' : '❌'));
            } catch (\Throwable $e) {
                $this->error('    Erro ao construir menu: ' . $e->getMessage());
            }
        }

        tenancy()->end();
    }
}