<?php

namespace App\Http\Controllers\Tenant\Pap;

use App\Actions\Tenant\GrupoPap\DefinirDataDefesa;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\GrupoPap\DefinirDataDefesaRequest;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Traits\NotificaGrupoPap;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class DefesaPapController extends Controller
{
    use NotificaGrupoPap;

    /**
     * Recebe a operação de definição da data da defesa.
     */
    public function __construct(private readonly DefinirDataDefesa $definirDataDefesa) {}

    /**
     * Define a data, hora e local da defesa do grupo PAP.
     */
    public function definirData(
        DefinirDataDefesaRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        Gate::forUser(Auth::guard('tenant')->user())->authorize('definirData', $grupoPap);

        $this->definirDataDefesa->handle($grupoPap, $request->validated());
        $this->notificarDataDefesaDefinida($grupoPap);

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ])->with('toast', [
            'type' => 'success',
            'message' => 'Data e local da defesa definidos com sucesso!',
        ]);
    }
}
