<?php

declare(strict_types=1);

namespace App\Services\Central;

use App\Enums\TenantStatus;
use App\Models\Central\Tenant;
use App\Models\Tenant\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardService
{
    private const ONLINE_MINUTOS = 5;

    private const DIAS_SEMANA = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];

    /**
     * Dados do dashboard da central.
     *
     * @return array<string, mixed>
     */
    public function getData(int $dias = 7): array
    {
        $tenants = Tenant::query()->get(['id', 'status', 'instituicao_nome', 'instituicao_tipo', 'created_at']);
        $actividade = $this->actividade();

        return [
            'kpis' => $this->kpis($tenants),
            'crescimento' => $this->crescimento($tenants, $dias),
            'utilizadores' => $this->utilizadores(),
            'logins' => $actividade['logins'],
            'online' => $actividade['online'],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function kpis(Collection $tenants): array
    {
        $porStatus = fn (array $status): int => $tenants
            ->filter(fn (Tenant $t): bool => in_array($t->status, $status, true))
            ->count();

        return [
            'total' => $tenants->count(),
            'novas_semana' => $tenants
                ->filter(fn (Tenant $t): bool => $t->created_at !== null && $t->created_at->gte(now()->subDays(7)))
                ->count(),
            'activas' => $porStatus([TenantStatus::ACTIVE, TenantStatus::TRIAL]),
            'pendentes' => $porStatus([TenantStatus::PENDING]),
            'suspensas' => $porStatus([TenantStatus::SUSPENDED]),
            'colegios' => $tenants->where('instituicao_tipo', 'colegio')->count(),
            'institutos' => $tenants->where('instituicao_tipo', 'instituto')->count(),
        ];
    }

    /**
     * Instituições acumuladas por tipo, dia a dia.
     *
     * @return array<int, array{data: string, colegios: int, institutos: int}>
     */
    private function crescimento(Collection $tenants, int $dias): array
    {
        $serie = [];

        for ($i = $dias - 1; $i >= 0; $i--) {
            $dia = now()->subDays($i)->endOfDay();

            $ate = $tenants->filter(
                fn (Tenant $t): bool => $t->created_at !== null && $t->created_at->lte($dia)
            );

            $serie[] = [
                'data' => $dia->format('d/m'),
                'colegios' => $ate->where('instituicao_tipo', 'colegio')->count(),
                'institutos' => $ate->where('instituicao_tipo', 'instituto')->count(),
            ];
        }

        return $serie;
    }

    /**
 * Utilizadores de todos os tenants (sem a conta de suporte central).
 * Em cache 5 minutos, porque percorre a base de cada tenant.
 *
 * @return array{total: int, novos_semana: int, por_instituicao: array<int, array<string, mixed>>}
 */
private function utilizadores(): array
{
    return Cache::remember('central.dashboard.utilizadores', now()->addMinutes(5), function (): array {
        $lista = Tenant::query()
            ->whereIn('status', [TenantStatus::ACTIVE, TenantStatus::TRIAL, TenantStatus::SUSPENDED])
            ->get()
            ->map(function (Tenant $tenant): ?array {
                try {
                    $dados = $tenant->run(function (): array {
                        $base = User::query()->where('email', '!=', User::CENTRAL_SUPPORT_EMAIL);

                        $porPerfil = fn (string $perfil): int => (clone $base)
                            ->whereHas('roles', fn ($q) => $q->where('name', $perfil))
                            ->count();

                        return [
                            'total' => (clone $base)->count(),
                            'novos' => (clone $base)->where('created_at', '>=', now()->subDays(7))->count(),
                            'professores' => $porPerfil('Professor'),
                            'alunos' => $porPerfil('Aluno'),
                        ];
                    });
                } catch (Throwable $exception) {
                    Log::warning('Dashboard: não foi possível contar os utilizadores do tenant.', [
                        'tenant_id' => $tenant->getTenantKey(),
                        'exception' => $exception,
                    ]);

                    return null;
                }

                return [
                    'id' => (string) $tenant->getTenantKey(),
                    'nome' => $tenant->instituicao_nome ?? (string) $tenant->getTenantKey(),
                    'tipo' => $tenant->instituicao_tipo,
                    'total' => $dados['total'],
                    'novos' => $dados['novos'],
                    'professores' => $dados['professores'],
                    'alunos' => $dados['alunos'],
                ];
            })
            ->filter()
            ->sortByDesc('total')
            ->values();

        return [
            'total' => (int) $lista->sum('total'),
            'novos_semana' => (int) $lista->sum('novos'),
            'por_instituicao' => $lista->all(),
        ];
    });
}
    /**
     * Logins por dia e utilizadores online, somando todos os tenants.
     * Em cache 30 segundos.
     *
     * @return array{logins: array<string, mixed>, online: array<string, mixed>}
     */
    private function actividade(): array
    {
        return Cache::remember('central.dashboard.actividade', now()->addSeconds(30), function (): array {
            $inicio = now()->subDays(6)->startOfDay();
            $porDia = [];

            for ($i = 6; $i >= 0; $i--) {
                $porDia[now()->subDays($i)->toDateString()] = 0;
            }

            $online = [];

            Tenant::query()
                ->whereIn('status', [TenantStatus::ACTIVE, TenantStatus::TRIAL])
                ->get()
                ->each(function (Tenant $tenant) use (&$porDia, &$online, $inicio): void {
                    try {
                        $dados = $tenant->run(function () use ($inicio): array {
                            $logins = User::withoutCentralSupport()
                                ->where('last_login_at', '>=', $inicio)
                                ->pluck('last_login_at')
                                ->countBy(fn ($data) => Carbon::parse($data)->toDateString())
                                ->all();

                            $ligados = User::withoutCentralSupport()
                                ->whereIn('id', DB::table('sessions')
                                    ->whereNotNull('user_id')
                                    ->where('last_activity', '>=', now()->subMinutes(self::ONLINE_MINUTOS)->timestamp)
                                    ->select('user_id'))
                                ->count();

                            return ['logins' => $logins, 'online' => $ligados];
                        });
                    } catch (Throwable $exception) {
                        Log::warning('Dashboard: não foi possível ler a actividade do tenant.', [
                            'tenant_id' => $tenant->getTenantKey(),
                            'exception' => $exception,
                        ]);

                        return;
                    }

                    foreach ($dados['logins'] as $data => $total) {
                        if (isset($porDia[$data])) {
                            $porDia[$data] += $total;
                        }
                    }

                    if ($dados['online'] > 0) {
                        $online[] = [
                            'nome' => $tenant->instituicao_nome ?? (string) $tenant->getTenantKey(),
                            'total' => $dados['online'],
                        ];
                    }
                });

            usort($online, fn (array $a, array $b): int => $b['total'] <=> $a['total']);

            $valores = array_values($porDia);
            $hoje = $valores[6];
            $ontem = $valores[5];

            $dias = [];
            foreach ($porDia as $data => $total) {
                $dias[] = [
                    'dia' => self::DIAS_SEMANA[Carbon::parse($data)->dayOfWeekIso - 1],
                    'logins' => $total,
                ];
            }

            return [
                'logins' => [
                    'por_dia' => $dias,
                    'hoje' => $hoje,
                    'variacao' => $ontem > 0 ? (int) round((($hoje - $ontem) / $ontem) * 100) : null,
                    'falhados' => null,
                ],
                'online' => [
                    'total' => array_sum(array_column($online, 'total')),
                    'por_instituicao' => $online,
                ],
            ];
        });
    }
}