<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\DeclaracaoEstagio\StoreDeclaracaoEstagioRequest;
use App\Services\Central\DeclaracaoEstagioPdfService;
use App\Services\Central\DeclaracaoEstagioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DeclaracaoEstagioController extends Controller
{
    public function __construct(
        private readonly DeclaracaoEstagioService $service,
        private readonly DeclaracaoEstagioPdfService $pdfService,
    ) {}

    public function index(Request $request): Response
    {
        $instituicoes = $this->service->listarInstituicoes();

        return Inertia::render('central/declaracoes-estagio/index', [
            'instituicoes' => $instituicoes,
            'historico' => $this->service->listarHistorico(),
            'routes' => [
                'alunos' => route('central.dashboard.declaracoes-estagio.alunos'),
                'store' => route('central.dashboard.declaracoes-estagio.store'),
            ],
        ]);
    }

    public function alunos(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'tenant_id' => ['required', 'string', 'exists:tenants,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'pagina' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        $tenant = $this->service->obterInstituicaoElegivel($dados['tenant_id']);

        return response()->json($this->service->pesquisarAlunos(
            $tenant,
            $dados['q'] ?? null,
            $dados['pagina'] ?? 1,
        ));
    }

    public function store(StoreDeclaracaoEstagioRequest $request): HttpResponse
    {
        $dados = $request->validated();
        $tenant = $this->service->obterInstituicaoElegivel($dados['tenant_id']);
        $declaracao = $this->service->emitir(
            $tenant,
            $dados['aluno_id'],
            (string) $request->user('web')->getAuthIdentifier(),
            $dados,
        );
        $conteudo = $this->pdfService->gerar($declaracao);
        $this->service->registrarHistorico($declaracao);
        $nomeAluno = Str::slug($declaracao->aluno_nome) ?: 'aluno';
        $matricula = Str::slug(
            blank($declaracao->matricula) ? 'sem-matricula' : $declaracao->matricula,
        );
        $nomeFicheiro = "Declaracao_Estagio_{$nomeAluno}_{$matricula}.pdf";

        return response($conteudo)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$nomeFicheiro.'"');
    }
}
