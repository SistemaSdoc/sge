<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Aluno;
use App\Services\Tenant\DesempenhoAlunoService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DesempenhoAlunoController extends Controller
{
    public function __construct(private DesempenhoAlunoService $service) {}

    public function index(Request $request)
    {
        $aluno = Aluno::where('user_id', $request->user()->id)->firstOrFail();

        return Inertia::render('tenant/desempenho', [
            ...$this->service->gerar($aluno, $request->input('ano_lectivo_id') ?: null),
            'rotaIndex' => route('tenant.dashboard.aluno.desempenho'),
        ]);
    }
}
