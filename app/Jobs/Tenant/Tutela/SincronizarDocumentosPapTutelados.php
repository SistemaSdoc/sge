<?php

namespace App\Jobs\Tenant\Tutela;

use App\Enums\TutelaStatus;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoTutelado;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SincronizarDocumentosPapTutelados implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly string $tenantTutorId,
        private readonly string $cursoId,
    ) {}

    public function handle(): void
    {
        $centralConnection = (string) config('tenancy.database.central_connection', config('database.default'));

        $tutelados = CursoTuteladoShared::on($centralConnection)
            ->where('tenant_tutor_id', $this->tenantTutorId)
            ->where('curso_id', $this->cursoId)
            ->where('status', TutelaStatus::ACTIVO)
            ->get();

        if ($tutelados->isEmpty()) {
            return;
        }

        $tenantTutor = Tenant::find($this->tenantTutorId);

        if (! $tenantTutor) {
            return;
        }

        // Lê os ficheiros no contexto do tutor
        $ficheiros = $tenantTutor->run(function (): array {
            $tutor = CursoTutelado::query()
                ->where('tipo_tutela', 'propria')
                ->whereHas('instituicaoCurso', fn ($q) => $q->where('curso_id', $this->cursoId))
                ->first(['criterios_pap_path', 'manual_pt_path', 'estrutura_trabalho_pap_path']);

            if (! $tutor) {
                return [];
            }

            $mapa = [
                'criterios_pap_path'          => 'criterios-pap',
                'manual_pt_path'              => 'manual-pt',
                'estrutura_trabalho_pap_path' => 'estrutura-trabalho-pap',
            ];

            $resultado = [];

            foreach ($mapa as $campo => $directorio) {
                $path = $tutor->$campo;

                if (! $path || ! Storage::disk('public')->exists($path)) {
                    continue;
                }

                $resultado[$campo] = [
                    'conteudo'   => Storage::disk('public')->get($path),
                    'extensao'   => pathinfo($path, PATHINFO_EXTENSION),
                    'directorio' => $directorio,
                ];
            }

            return $resultado;
        });

        if (empty($ficheiros)) {
            Log::info('SincronizarDocumentosPap: tutor sem documentos', [
                'tenant_tutor_id' => $this->tenantTutorId,
                'curso_id'        => $this->cursoId,
            ]);

            return;
        }

        foreach ($tutelados as $shared) {
            $tenantTutelado = Tenant::find($shared->tenant_tutelado_id);

            if (! $tenantTutelado) {
                continue;
            }

            $tenantTutelado->run(function () use ($shared, $ficheiros): void {
                $cursoTutelado = CursoTutelado::query()
                    ->where('curso_tutelado_shared_id', $shared->getKey())
                    ->first();

                if (! $cursoTutelado) {
                    return;
                }

                $novosCaminhos = [];

                foreach ($ficheiros as $campo => $dados) {
                    $pathDestino = "cursos-tutelados/{$cursoTutelado->getKey()}/{$dados['directorio']}/documento.{$dados['extensao']}";

                    Storage::disk('public')->put($pathDestino, $dados['conteudo']);
                    $novosCaminhos[$campo] = $pathDestino;
                }

                $cursoTutelado->forceFill($novosCaminhos)->save();

                Log::info('SincronizarDocumentosPap: documentos copiados', [
                    'tenant_tutelado_id' => (string) tenancy()->tenant->getTenantKey(),
                    'curso_tutelado_id'  => $cursoTutelado->id,
                    'campos'             => array_keys($novosCaminhos),
                ]);
            });
        }
    }
}