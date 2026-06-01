<?php

namespace App\Services\Dashboards;

use App\Models\Aviso;
use App\Models\Professor;
use App\Models\User;
use App\Traits\DashboardHelpers;
use Auth;
use Illuminate\Database\Eloquent\Collection;

class DashboardProfessorService
{
    use DashboardHelpers;

    /**
     * Obter as próximas aulas do professor para os próximos dias.
     */
    public function obterProximasAulas(Professor $professor, int $dias = 2, int $limite = 6)
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

        return $professor->turmaDisciplinaProfessor()
            ->with([
                'classeTurnoDisciplina' => function ($query) use ($diasSemana) {
                    $query->with([
                        'horarios' => function ($query) use ($diasSemana) {
                            $query->whereIn('dia_semana', $diasSemana)
                                ->orderBy('hora_inicio');
                        },
                        'disciplina',
                    ]);
                },
                'turma',
            ])
            ->get()
            ->flatMap(function ($tdp) use ($diasMapa) {
                $disciplina = $tdp->classeTurnoDisciplina;
                $turma = $tdp->turma;

                return $disciplina->horarios->map(function ($horario) use ($disciplina, $turma, $diasMapa) {
                    $meta = $diasMapa[$horario->dia_semana];

                    return [
                        'id' => $disciplina->id,
                        'disciplina' => [
                            'nome' => $disciplina->disciplina->nome,
                            'sigla' => $disciplina->disciplina->sigla,
                        ],
                        'turma' => [
                            'id' => $turma->id,
                            'nome' => $turma->nome,
                        ],
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
            })
            ->filter(fn ($item) => $this->aulaAindaNaoTerminou($item['dia'], $item['horario']['hora_fim']))
            ->sortBy(fn ($item) => $item['dia'].' '.$item['horario']['hora_inicio'])
            ->values()
            ->take($limite);
    }

    /**
     * Obter resumo acadêmico do professor (disciplinas que leciona, turmas, etc)
     */
    public function obterResumoAcademico(Professor $professor): array
    {
        return $professor->turmaDisciplinaProfessor()
            ->with([
                'classeTurnoDisciplina.disciplina',
                'turma',
            ])
            ->get()
            ->groupBy(fn ($tdp) => $tdp->classeTurnoDisciplina->disciplina->id)
            ->map(function ($items) {
                $primeiro = $items->first();
                $disciplina = $primeiro->classeTurnoDisciplina->disciplina;

                return [
                    'id' => $disciplina->id,
                    'disciplina' => [
                        'id' => $disciplina->id,
                        'nome' => $disciplina->nome,
                        'sigla' => $disciplina->sigla,
                    ],
                    'turmas' => $items->map(fn ($item) => [
                        'id' => $item->turma->id,
                        'nome' => $item->turma->nome,
                    ])->values(),
                    'totalTurmas' => $items->count(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Obter avisos/notificações (será implementado quando tabela existir)
     */
    public function obterAvisos(Professor $professor, ?int $limite = 10)
    {
        /** @var User $user */
        $user = Auth::user();
        $instituicaoId = $user?->instituicaoFiltro();

        // Avisos ativos para professores
        $avisos = Aviso::where('ativo', true)
            ->whereIn('destinatario', ['todos', 'professores'])
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

        return $avisos;
    }
}
