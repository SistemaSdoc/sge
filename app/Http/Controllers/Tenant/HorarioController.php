<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\AnoLectivo;
use App\Services\Tenant\AnoLectivo\AnoLectivoResolverService;
use App\Services\Tenant\HorarioService;
// + o mesmo `use` de AnoLectivo que o InscricaoController
use Illuminate\Http\Request;
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
            $aulas = $this->horarioService->doProfessor($user->professor, $anoLectivoId);
        } elseif ($user->aluno) {
            $perfil = 'aluno';
            $aulas = $this->horarioService->doAluno($user->aluno, $anoLectivoId);
        } else {
            $perfil = 'outro';
            $aulas = collect();
        }

        return Inertia::render('tenant/horarios/index', [
            'aulas' => $aulas,
            'perfil' => $perfil,
            'anosLectivos' => $anosLectivos,
            'anoLectivoActual' => $anoLectivoId,
        ]);
    }
}