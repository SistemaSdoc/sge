<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\AnoLectivo;
use App\Models\Tenant\Aula;
use App\Services\Tenant\AnoLectivo\AnoLectivoResolverService;
use App\Services\Tenant\HorarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class HorarioController extends Controller
{
    public function __construct(
        private readonly AnoLectivoResolverService $anoLectivoResolverService,
        private readonly HorarioService $horarioService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user('tenant');

        $anoLectivoId = $request->input('ano_lectivo_id')
            ?? $this->anoLectivoResolverService->obterAnoLectivoDefault();

        $anosLectivos = AnoLectivo::query()
            ->select('id', 'nome', 'data_inicio', 'data_fim')
            ->orderByDesc('data_inicio')
            ->get();

        if ($user->professor) {
            $perfil = 'professor';
            $aulas  = $this->horarioService->doProfessor($user->professor, $anoLectivoId);
        } elseif ($user->aluno) {
            $perfil = 'aluno';
            $aulas  = $this->horarioService->doAluno($user->aluno, $anoLectivoId);
        } else {
            $perfil = 'outro';
            $aulas  = collect();
        }

        // ✅ NOVO — enriquece cada slot com sumário / próxima aula / url de criação
        $aulas = $this->enriquecerComSumarios($aulas, $perfil);

        return Inertia::render('tenant/horarios/index', [
            'aulas'            => $aulas,
            'perfil'           => $perfil,
            'anosLectivos'     => $anosLectivos,
            'anoLectivoActual' => $anoLectivoId,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // ENRIQUECIMENTO COM SUMÁRIOS
    // ═══════════════════════════════════════════════════════════

    /**
     * Para cada slot do horário, adiciona:
     *  - aula_ultima    (última aula registada até hoje)
     *  - aula_proxima   (próxima aula publicada, futura)
     *  - url_criar      (link "Escrever sumário" — só professor)
     */
    private function enriquecerComSumarios($aulas, string $perfil): array
    {
        $aulasArray = collect($aulas);

        if ($aulasArray->isEmpty()) {
            return [];
        }

        $slotIds = $aulasArray
            ->pluck('id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($slotIds)) {
            return $aulasArray->toArray();
        }

        $hoje = Carbon::today()->toDateString();

        // ── 1. Últimas aulas (até hoje), agrupadas por slot+turma ──
        $ultimas = Aula::query()
            ->whereIn('classe_turno_disciplina_horario_id', $slotIds)
            ->whereIn('estado', ['publicado', 'rascunho'])
            ->where('data', '<=', $hoje)
            ->orderBy('data', 'desc')
            ->get()
            ->groupBy(fn (Aula $a) => $a->classe_turno_disciplina_horario_id . '|' . $a->turma_id)
            ->map(fn ($grupo) => $grupo->first());

        // ── 2. Próximas aulas (publicadas, futuras) ──
        $proximas = Aula::query()
            ->whereIn('classe_turno_disciplina_horario_id', $slotIds)
            ->where('estado', 'publicado')
            ->where('data', '>', $hoje)
            ->orderBy('data')
            ->get()
            ->groupBy(fn (Aula $a) => $a->classe_turno_disciplina_horario_id . '|' . $a->turma_id)
            ->map(fn ($grupo) => $grupo->first());

        // ── 3. Enriquecer cada slot ──
        return $aulasArray->map(function ($slot) use ($ultimas, $proximas, $perfil) {
            $slotArray = is_array($slot) ? $slot : $slot->toArray();

            $slotId  = $slotArray['id'] ?? null;
            $turmaId = $slotArray['turma']['id'] ?? null;
            $chave   = $slotId . '|' . $turmaId;

            $ultima  = $ultimas->get($chave);
            $proxima = $proximas->get($chave);

            return array_merge($slotArray, [
                'aula_ultima'  => $ultima  ? $this->formatarResumoAula($ultima)  : null,
                'aula_proxima' => $proxima ? $this->formatarResumoAula($proxima) : null,
                'url_criar'    => $perfil === 'professor'
                    ? $this->urlCriarAula($slotId, $slotArray['dia_semana'] ?? null)
                    : null,
            ]);
        })->toArray();
    }

    private function formatarResumoAula(Aula $aula): array
    {
        return [
            'id'                => $aula->id,
            'data'              => $aula->data?->format('Y-m-d'),
            'data_humana'       => $aula->data?->format('d/m/Y'),
            'titulo'            => $aula->titulo,
            'sumario'           => $aula->sumario,
            'conteudo_previsto' => $aula->conteudo_previsto,
            'estado'            => $aula->estado,
            'estado_label'      => $aula->estado_label,
            'tem_sumario'       => $aula->tem_sumario,
            'url_ver'           => route('tenant.dashboard.aulas.show', $aula),
        ];
    }

    private function urlCriarAula(?string $slotId, $diaSemana): ?string
    {
        if (! $slotId || ! $diaSemana) {
            return null;
        }

        $data = $this->ultimaDataDoDia((int) $diaSemana);

        return route('tenant.dashboard.professor.aulas.create', [
            'slot_id' => $slotId,
            'data'    => $data,
        ]);
    }

    /**
     * Data da ocorrência mais recente deste dia da semana (inclui hoje).
     */
    private function ultimaDataDoDia(int $diaSemanaIso): string
    {
        $mapaCarbon = [
            1 => Carbon::MONDAY,
            2 => Carbon::TUESDAY,
            3 => Carbon::WEDNESDAY,
            4 => Carbon::THURSDAY,
            5 => Carbon::FRIDAY,
            6 => Carbon::SATURDAY,
            7 => Carbon::SUNDAY,
        ];

        $hoje = Carbon::today();

        if ($hoje->dayOfWeekIso === $diaSemanaIso) {
            return $hoje->toDateString();
        }

        return $hoje->previous($mapaCarbon[$diaSemanaIso])->toDateString();
    }
}
