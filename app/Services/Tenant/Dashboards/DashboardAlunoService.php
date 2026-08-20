<?php

namespace App\Services\Dashboards;

use App\Models\Aluno;
use App\Models\Aviso;
use App\Models\GrupoPap;
use App\Models\TurmaAluno;
use App\Traits\DashboardHelpers;
use Auth;
use Carbon\Carbon;

class DashboardAlunoService
{
    use DashboardHelpers;

    /**
     * Obter as próximas aulas do aluno para os próximos dias.
     */
    public function obterProximasAulas(Aluno $aluno, int $dias = 2, int $limite = 6)
    {
        $hoje = now();
        $diasMapa = collect(range(0, $dias))
            ->mapWithKeys(function (int $offset) use ($hoje) {
                $data = $hoje->copy()->addDays($offset);
                $weekday = $data->dayOfWeekIso;

                return [$weekday => [
                    'offset' => $offset,
                    'label' => null,
                    'weekday' => $weekday,
                    'weekday_name' => $this->obterNomeDia($weekday),
                    'date' => $data->toDateString(),
                ]];
            });

        $diasSemana = $diasMapa->keys()->all();

        return $aluno->turmas()
            ->wherePivot('activo', true)
            ->with(['cursoClasseTurno.classeTurnoDisciplinas' => function ($query) use ($diasSemana) {
                $query->with([
                    'horarios' => function ($query) use ($diasSemana) {
                        $query->whereIn('dia_semana', $diasSemana)
                            ->orderBy('hora_inicio');
                    },
                    'turmaDisciplinaProfessores.professor.user',
                    'disciplina',
                ]);
            }])
            ->get()
            ->flatMap(function ($turma) use ($diasMapa) {
                return $turma->cursoClasseTurno->classeTurnoDisciplinas
                    ->flatMap(function ($disciplina) use ($turma, $diasMapa) {
                        $professor = $disciplina->turmaDisciplinaProfessores
                            ->first(fn ($tdp) => $tdp->turma_id === $turma->id)?->professor;

                        return $disciplina->horarios->map(function ($horario) use ($disciplina, $professor, $diasMapa) {
                            $meta = $diasMapa[$horario->dia_semana];

                            return [
                                'id' => $disciplina->id,
                                'disciplina' => [
                                    'nome' => $disciplina->disciplina->nome,
                                    'sigla' => $disciplina->disciplina->sigla,
                                ],
                                'professor' => $professor ? [
                                    'nome' => $professor->user->nome,
                                ] : null,
                                'horario' => [
                                    'dia_semana' => $horario->dia_semana,
                                    'hora_inicio' => $horario->hora_inicio instanceof \DateTimeInterface
                                        ? $horario->hora_inicio->format('H:i')
                                        : $horario->hora_inicio,
                                    'hora_fim' => $horario->hora_fim instanceof \DateTimeInterface
                                        ? $horario->hora_fim->format('H:i')
                                        : $horario->hora_fim,
                                ],
                                'dia_label' => $this->obterLabelDia($meta['offset'], $horario, $meta['weekday']),
                                'dia_nome' => $meta['weekday_name'],
                                'dia' => $meta['date'],
                            ];
                        });
                    });
            })
            ->filter(fn ($item) => $this->aulaAindaNaoTerminou($item['dia'], $item['horario']['hora_fim']))
            ->sortBy(fn ($item) => $item['dia'].' '.$item['horario']['hora_inicio'])
            ->values()
            ->take($limite);
    }

    /**
     * Obter resumo acadêmico do aluno consolidado por disciplina (notas, médias, faltas, situação)
     */
    public function obterResumoAcademico(Aluno $aluno)
    {
        // Load the TurmaAluno pivot records for this student (where pivot is implemented
        // by the TurmaAluno model) and eager-load their notas. The Turma model does not
        // define a `notas` relation, so accessing `$turma->notas` causes the RelationNotFoundException.
        $notas = TurmaAluno::where('aluno_id', $aluno->id)
            ->where('activo', true)
            ->with(['notas' => function ($query) {
                $query->with('turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina')
                    ->orderByDesc('periodo');
            }])
            ->get()
            ->flatMap(fn ($turmaAluno) => $turmaAluno->notas);

        // Consolidar por disciplina com array de períodos
        return $notas->groupBy(fn ($nota) => $nota->turmaDisciplinaProfessor->classeTurnoDisciplina->disciplina->id)
            ->map(function ($notasPorDisciplina) {
                $primeira = $notasPorDisciplina->first();
                $disciplina = $primeira->turmaDisciplinaProfessor->classeTurnoDisciplina->disciplina;
                $professor = $primeira->turmaDisciplinaProfessor->professor->user;

                return [
                    'id' => $disciplina->id,
                    'disciplina' => [
                        'id' => $disciplina->id,
                        'nome' => $disciplina->nome,
                        'sigla' => $disciplina->sigla,
                    ],
                    'professor' => [
                        'id' => $professor->id,
                        'nome' => $professor->nome,
                    ],
                    'periodos' => $notasPorDisciplina->map(fn ($nota) => [
                        'numero' => $nota->periodo,
                        'faltas' => $nota->faltas,
                        'mac' => $nota->mac,
                        'nota_prova_professor' => $nota->nota_prova_professor,
                        'nota_prova_trimestral' => $nota->nota_prova_trimestral,
                        'media_trimestral' => $nota->media_trimestral,
                        'media_final' => $nota->media_final,
                        'situacao_trimestral' => $nota->situacao_trimestral,
                        'situacao_anual' => $nota->situacao_anual,
                        'observacao' => $nota->observacao,
                    ])->sortByDesc('numero')->values(),
                    'mediaGeral' => $notasPorDisciplina->avg('media_final'),
                    'statusAnual' => $notasPorDisciplina->first()?->situacao_anual ?? 'Sem dados',
                ];
            })
            ->values();
    }

    /**
     * Obter avisos/notificações (será implementado quando tabela existir)
     */
    public function obterAvisos(Aluno $aluno, ?int $limite = 10)
    {
        $user = Auth::user();
        $instituicaoId = $user?->instituicaoFiltro();
        $today = Carbon::today();

        // Avisos ativos
        $avisos = Aviso::where('ativo', true)
            ->whereIn('destinatario', ['todos', 'alunos'])
            ->when(
                $instituicaoId,
                fn ($q) => $q->where('instituicao_id', $instituicaoId)
            )
            ->orderByRaw("FIELD(tipo, 'urgente', 'evento', 'aviso')")
            ->orderBy('data', 'asc')
            ->get()
            ->map(fn (Aviso $a) => [
                'id' => $a->id,
                'type' => $a->tipo,
                'titulo' => $a->titulo,
                'descricao' => $a->descricao,
                'data' => $a->data?->toISOString(),
            ]);

        // Eventos de defesa de PAP
        $eventos = GrupoPap::whereHas('turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso', function ($query) use ($instituicaoId) {
            if ($instituicaoId) {
                $query->where('instituicao_id', $instituicaoId);
            }
        })
            ->whereNotNull('data_defesa')
            ->whereDate('data_defesa', '>=', $today)
            ->orderBy('data_defesa')
            ->get()
            ->map(fn (GrupoPap $grupo) => [
                'id' => "pap-{$grupo->id}",
                'type' => 'evento',
                'titulo' => "Banca de Defesa - {$grupo->nome_grupo}",
                'descricao' => null,
                'data' => $grupo->data_defesa?->toISOString(),
            ]);

        // Combinar e ordenar por data
        $combined = collect($avisos)->concat($eventos)
            ->sortBy(function ($item) {
                return $item['data'] ?? now();
            })
            ->values();

        return $combined;
    }
}
