<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\Aula;
use App\Models\Tenant\ClasseTurnoDisciplinaHorario;
use App\Models\Tenant\User;
use Illuminate\Console\Command;

class TestAulas extends Command
{
    protected $signature   = 'test:aulas';
    protected $description = 'Testa a estrutura de aulas/sumários de ponta a ponta';

    public function handle(): void
    {
        $tenant = Tenant::first();
        if (! $tenant) {
            $this->error('Sem tenant.');
            return;
        }

        tenancy()->initialize($tenant);

        $this->info("═══════════════════════════════════════════════════════");
        $this->info("  TENANT: {$tenant->id}");
        $this->info("═══════════════════════════════════════════════════════");

        $profUser = User::whereHas('professor')->first();

        if (! $profUser) {
            $this->error('Nenhum professor encontrado. Aborta.');
            tenancy()->end();
            return;
        }

        // ══════════════════════════════════════════════════════
        // 1. SLOTS DO PROFESSOR
        // ══════════════════════════════════════════════════════
        $this->newLine();
        $this->info('▸ 1. Slots do professor');
        $this->line("   {$profUser->email}");

        $slots = ClasseTurnoDisciplinaHorario::query()
            ->whereHas('classeTurnoDisciplina.turmaDisciplinaProfessores', function ($q) use ($profUser) {
                $q->where('professor_id', $profUser->professor->id);
            })
            ->with(['turma:id,nome', 'classeTurnoDisciplina.disciplina:id,nome'])
            ->get();

        $this->line("   → {$slots->count()} slots");
        foreach ($slots->take(3) as $s) {
            $this->line(sprintf(
                '     %s | %s | dia=%d | %s-%s',
                $s->turma?->nome ?? '—',
                $s->classeTurnoDisciplina?->disciplina?->nome ?? '—',
                $s->dia_semana,
                $s->hora_inicio,
                $s->hora_fim,
            ));
        }

        if ($slots->isEmpty()) {
            $this->error('Sem slots — não é possível testar mais.');
            tenancy()->end();
            return;
        }

        // ══════════════════════════════════════════════════════
        // 2. ALUNO E TURMAS
        // ══════════════════════════════════════════════════════
        $this->newLine();
        $this->info('▸ 2. Aluno e turmas');

        $alunoUser = User::role('Aluno')->first();
        $turmasAluno = [];

        if (! $alunoUser) {
            $this->warn('   Nenhum aluno encontrado.');
        } else {
            $this->line("   {$alunoUser->email}");
            $turmasAluno = $alunoUser->aluno?->turmas()->pluck('turmas.id')->toArray() ?? [];
            $this->line('   → Turmas: ' . count($turmasAluno));
            foreach ($turmasAluno as $t) {
                $this->line("     - {$t}");
            }
        }

        // ══════════════════════════════════════════════════════
        // 3. INDEX DO CONTROLLER (professor)
        // ══════════════════════════════════════════════════════
        $this->newLine();
        $this->info('▸ 3. index() — professor');

        try {
            auth('tenant')->setUser($profUser);
            $request = \Illuminate\Http\Request::create('/dashboard/aulas', 'GET');
            $request->setUserResolver(fn () => $profUser);
            app(\App\Http\Controllers\Tenant\AulaController::class)->index($request);
            $this->info('   ✅ correu sem erro');
        } catch (\Throwable $e) {
            $this->error('   ❌ ' . $e->getMessage());
            $this->line('      → ' . $e->getFile() . ':' . $e->getLine());
        }

        // ══════════════════════════════════════════════════════
        // 4. INDEX DO CONTROLLER (aluno)
        // ══════════════════════════════════════════════════════
        if ($alunoUser) {
            $this->newLine();
            $this->info('▸ 4. index() — aluno');

            try {
                auth('tenant')->setUser($alunoUser);
                $request = \Illuminate\Http\Request::create('/dashboard/aulas', 'GET');
                $request->setUserResolver(fn () => $alunoUser);
                app(\App\Http\Controllers\Tenant\AulaController::class)->index($request);
                $this->info('   ✅ correu sem erro');
            } catch (\Throwable $e) {
                $this->error('   ❌ ' . $e->getMessage());
                $this->line('      → ' . $e->getFile() . ':' . $e->getLine());
            }
        }

        // ══════════════════════════════════════════════════════
        // 5. CICLO COMPLETO — criar / publicar / cancelar
        // ══════════════════════════════════════════════════════
        $this->newLine();
        $this->info('▸ 5. Ciclo completo — criar / publicar / cancelar');

        $slot = $slots->first();
        $data = now()->addDays(1)->toDateString();

        // Limpar resto de testes anteriores
        Aula::where('classe_turno_disciplina_horario_id', $slot->id)
            ->where('data', $data)
            ->forceDelete();

        // Criar rascunho
        $aula = Aula::create([
            'classe_turno_disciplina_horario_id' => $slot->id,
            'turma_id'      => $slot->turma_id,
            'disciplina_id' => $slot->classeTurnoDisciplina->disciplina_id,
            'professor_id'  => $profUser->professor->id,
            'data'          => $data,
            'hora_inicio'   => $slot->hora_inicio,
            'hora_fim'      => $slot->hora_fim,
            'tipo'          => 'normal',
            'titulo'        => 'Aula de teste',
            'conteudo_previsto' => 'Conteúdo previsto de teste',
            'sumario'       => 'Conteúdo dado em teste',
            'estado'        => 'rascunho',
        ]);
        $this->line("   ✅ Rascunho criado: {$aula->id}");
        $this->line("      estado: {$aula->estado_label} | editável: " . ($aula->esta_editavel ? 'sim' : 'não'));

        // Publicar
        $aula->update(['estado' => 'publicado', 'publicado_em' => now()]);
        $aula->refresh();
        $this->line("   ✅ Publicado | horas restantes: {$aula->horas_restantes_edicao}");

        // Aula visível para o aluno?
        if ($alunoUser) {
            $visivelAluno = Aula::query()
                ->where('id', $aula->id)
                ->publicado()
                ->whereIn('turma_id', $turmasAluno)
                ->exists();
            $this->line('   Aluno vê a aula? ' . ($visivelAluno ? '✅ sim' : '❌ não'));
        }

        // Cancelar
        $aula->update(['estado' => 'cancelado']);
        $aula->refresh();
        $this->line("   ✅ Cancelado | editável: " . ($aula->esta_editavel ? 'sim' : 'não'));

        // Apagar definitivamente
        $aula->forceDelete();
        $this->line('   ✅ Aula de teste removida');

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('  FIM');
        $this->info('═══════════════════════════════════════════════════════');

        tenancy()->end();
    }
}