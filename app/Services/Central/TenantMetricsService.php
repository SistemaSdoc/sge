<?php

namespace App\Services\Central;

use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;

class TenantMetricsService
{
    /**
     * Obtém métricas completas do tenant
     */
    public function getMetrics(): array
    {
        return [
            'users' => $this->getUserMetrics(),
            'logins_semana' => $this->getLoginsPerDay(),
            'database' => $this->getDatabaseMetrics(),
        ];
    }

    /**
     * Métricas de utilizadores
     */
    private function getUserMetrics(): array
    {
        $total = User::count();

        // Activos: login nos últimos 30 dias
        $ativos = User::where('last_login_at', '>=', now()->subDays(30))->count();

        // Inactivos: sem login há mais de 30 dias OU nunca fez login
        $inactivos = User::where(function ($query) {
            $query->where('last_login_at', '<', now()->subDays(30))->orWhereNull('last_login_at');
        })->count();

        // Suspensos: apenas users com email_verified_at = null
        $suspensos = 0;

        // Tipos de users — por role
        $diretores = User::whereHas('roles', fn ($q) => $q->where('name', 'Director'))->count();
        $subdiretores = User::whereHas('roles', fn ($q) => $q->where('name', 'Subdiretor'))->count();
        $professores = User::whereHas('roles', fn ($q) => $q->where('name', 'Professor'))->count();
        $alunos = User::whereHas('roles', fn ($q) => $q->where('name', 'Aluno'))->count();

        return [
            'total' => $total,
            'ativos' => $ativos,
            'inactivos' => $inactivos,
            'suspensos' => $suspensos,
            'diretores' => $diretores,
            'subdiretores' => $subdiretores,
            'professores' => $professores,
            'alunos' => $alunos,
        ];
    }

    /**
     * Logins nos últimos 7 dias
     */
    private function getLoginsPerDay(): array
    {
        $dias = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
        $resultado = [];

        for ($i = 6; $i >= 0; $i--) {
            $data = now()->subDays($i);
            $inicio = $data->clone()->startOfDay();
            $fim = $data->clone()->endOfDay();

            $count = User::whereBetween('last_login_at', [$inicio, $fim])->count();

            $resultado[] = [
                'dia' => $dias[$data->dayOfWeek === 0 ? 6 : $data->dayOfWeek - 1],
                'logins' => $count,
            ];
        }

        return $resultado;
    }

    /**
     * Métricas de base de dados
     */
    /**
     * Métricas de base de dados
     */
    private function getDatabaseMetrics(): array
    {
        $database = DB::connection()->getDatabaseName();

        // Tamanho total
        $tamanho = DB::table('information_schema.tables')
            ->selectRaw('ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as tamanho')
            ->where('table_schema', $database)
            ->first();

        $totalMB = $tamanho?->tamanho ?? 0;

        // Top 5 tabelas mais pesadas
        $tabelasGrandes = DB::table('information_schema.tables')
            ->selectRaw('table_name as nome, 
                 ROUND((data_length + index_length) / 1024 / 1024, 2) as mb,
                 table_rows as registos'
            )
            ->where('table_schema', $database)
            ->orderByRaw('(data_length + index_length) DESC')
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'nome' => ucfirst($t->nome),
                'registos' => (int) $t->registos,
                'mb' => (float) $t->mb,
            ])->toArray();

        // Top 5 tabelas com mais registos
        $tabelasRegistos = DB::table('information_schema.tables')
            ->selectRaw('table_name as nome, table_rows as registos')
            ->where('table_schema', $database)
            ->orderByRaw('table_rows DESC')
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'nome' => ucfirst($t->nome),
                'registos' => (int) $t->registos,
            ])->toArray();

        return [
            'total_mb' => $totalMB,
            'tabelasPorTamanho' => $tabelasGrandes,
            'tabelasPorRegistos' => $tabelasRegistos,
        ];
    }

    /**
     * Todas as tabelas ordenadas por tamanho (MB)
     */
    public function getAllTablesBySize(): array
    {
        $database = DB::connection()->getDatabaseName();

        return DB::table('information_schema.tables')
            ->selectRaw('table_name as nome, 
             ROUND((data_length + index_length) / 1024 / 1024, 2) as mb,
             table_rows as registos'
            )
            ->where('table_schema', $database)
            ->orderByRaw('(data_length + index_length) DESC')
            ->get()
            ->map(function ($t) {
                return [
                    'nome' => ucfirst($t->nome),
                    'registos' => (int) $t->registos,
                    'mb' => (float) $t->mb,
                ];
            })->toArray();
    }

    /**
     * Todas as tabelas ordenadas por número de registos
     */
    public function getAllTablesByRecords(): array
    {
        $database = DB::connection()->getDatabaseName();

        return DB::table('information_schema.tables')
            ->selectRaw('table_name as nome, 
             table_rows as registos'
            )
            ->where('table_schema', $database)
            ->orderByRaw('table_rows DESC')
            ->get()
            ->map(function ($t) {
                return [
                    'nome' => ucfirst($t->nome),
                    'registos' => (int) $t->registos,
                ];
            })->toArray();
    }

    /**
     * Sessões activas (online agora)
     */
    public function getOnlineNow(int $minutosAtivos = 15): int
    {
        return DB::table('sessions')
            ->where('last_activity', '>=', now()->subMinutes($minutosAtivos)->timestamp)
            ->distinct('user_id')
            ->count();
    }

    /**
     * Último acesso da instituição
     */
    public function getLastActivity(): ?\DateTime
    {
        $lastLogin = User::whereNotNull('last_login_at')
            ->latest('last_login_at')
            ->first();

        return $lastLogin?->last_login_at;
    }
}
