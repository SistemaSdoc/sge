<?php

namespace App\Console\Commands;

use App\Models\Central\Curso;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Signature('app:migrate-tenant-courses-to-central {--dry-run : Apenas verifica conflitos sem gravar dados}')]
#[Description('Copia os cursos dos tenants para o catálogo central')]
class MigrateTenantCoursesToCentral extends Command
{
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $total = 0;

        Tenant::query()->orderBy('id')->each(function (Tenant $tenant) use ($dryRun, &$total): void {
            [$rows, $links] = $tenant->run(function (): array {
                if (! Schema::connection('tenant')->hasTable('cursos')) {
                    return [collect(), collect()];
                }

                $connection = DB::connection('tenant');

                return [
                    $connection->table('cursos')->get(),
                    Schema::connection('tenant')->hasTable('curso_tutelado')
                        ? $connection->table('curso_tutelado')
                            ->join('instituicao_curso', 'instituicao_curso.id', '=', 'curso_tutelado.instituicao_curso_id')
                            ->get([
                                'curso_tutelado.id as curso_tutelado_id',
                                'instituicao_curso.curso_id',
                            ])
                        : collect(),
                ];
            });

            foreach ($rows as $row) {
                $existingById = Curso::query()->find($row->id);
                $existingByName = Curso::query()->where('nome', $row->nome)->first();

                if ($existingByName && (string) $existingByName->getKey() !== (string) $row->id) {
                    throw new \RuntimeException(
                        "Conflito de nome [{$row->nome}] entre {$existingByName->getKey()} e {$row->id}."
                    );
                }

                if ($existingById && $existingById->nome !== $row->nome) {
                    throw new \RuntimeException(
                        "Conflito de UUID [{$row->id}]: {$existingById->nome} != {$row->nome}."
                    );
                }

                if (! $dryRun && ! $existingById) {
                    Curso::query()->create([
                        'id' => $row->id,
                        'nome' => $row->nome,
                        'descricao' => $row->descricao,
                        'duracao_anos' => $row->duracao_anos,
                        'status' => $row->status,
                    ]);
                }

                $total++;
            }

            foreach ($links as $link) {
                $sharedQuery = CursoTuteladoShared::query()
                    ->where('tenant_tutelado_id', $tenant->getTenantKey())
                    ->where('curso_tutelado_tutelado_id', $link->curso_tutelado_id);

                if (! $dryRun) {
                    $sharedQuery->update(['curso_id' => $link->curso_id]);
                }
            }
        });

        $prefix = $dryRun ? 'Seriam migrados' : 'Migrados';
        $this->info("{$prefix} {$total} cursos para o catálogo central.");

        return self::SUCCESS;
    }
}
