<?php

namespace App\Http\Controllers\Tenant\Colegios;

use App\Http\Controllers\Controller;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\CrossTenantAccessService;
use App\Services\Tenant\TrabalhoPapService;
use App\Services\Tenant\Tutela\TutelaService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TrabalhoPapController extends Controller
{
    public function __construct(
        private readonly TrabalhoPapService $service,
        private readonly TutelaService $tutelaService,
        private readonly CrossTenantAccessService $crossTenantAccessService,
    ) {}

    public function submeter(
        Request $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('submeterTrabalho', $grupoPap);

        if ($ficheiro = $request->file('ficheiro')) {
            Log::warning('Falha no upload de trabalho PAP', [
                'erro' => $ficheiro->getError(),
                'mensagem' => $ficheiro->getErrorMessage(),
                'tamanho' => $ficheiro->getSize(),
                'nome' => $ficheiro->getClientOriginalName(),
            ]);
        }

        $request->validate([
            'ficheiro' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ], [
            'ficheiro.uploaded' => 'O servidor não conseguiu receber o trabalho. Tente novamente.',
        ]);

        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerSubmetido()) {
            return back()->withErrors(['ficheiro' => 'O trabalho não pode ser submetido neste momento.']);
        }

        $this->service->submeter($trabalho, Auth::user(), $request->file('ficheiro'));

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Trabalho submetido com sucesso. Aguarda revisão do professor tutor.',
        ]);
    }

    public function aprovarComoTutor(
        Request $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('aprovarTrabalhoComoTutor', $grupoPap);

        $validated = $request->validate([
            'comentario' => ['nullable', 'string', 'max:2000'],
        ]);

        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerAnalisadoPeloTutor()) {
            return back()->withErrors(['trabalho' => 'O trabalho não está disponível para análise do tutor.']);
        }

        $this->service->aprovarComoTutor($trabalho, Auth::user(), $validated['comentario'] ?? null);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Trabalho enviado para a coordenação.',
        ]);
    }

    public function solicitarCorrecaoComoTutor(
        Request $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('solicitarCorrecaoTrabalhoComoTutor', $grupoPap);

        $validated = $request->validate([
            'comentario' => ['required', 'string', 'min:10', 'max:2000'],
            'ficheiro' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerAnalisadoPeloTutor()) {
            return back()->withErrors(['trabalho' => 'O trabalho não está disponível para análise do tutor.']);
        }

        $this->service->solicitarCorrecaoComoTutor(
            $trabalho,
            Auth::user(),
            $validated['comentario'],
            $request->file('ficheiro'),
        );

        return back()->with('toast', ['type' => 'warning', 'message' => 'Correção solicitada ao aluno.']);
    }

    public function aprovarComoCoordenacao(
        Request $request,
        Instituicao $instituicao,
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap
    ) {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        abort_unless($user->can('grupopap.aprovar'), 403);
        $tenantTutorId = (string) tenancy()->tenant->getTenantKey();

        $validated = $request->validate([
            'comentario' => ['nullable', 'string', 'max:2000'],
        ]);

        $resultado = $this->withExternalGrupo(
            $colegio,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
            $user,
            fn (GrupoPap $grupo) => $this->aprovarTrabalhoDaCoordenacao(
                $grupo,
                $user,
                $validated['comentario'] ?? null,
                $tenantTutorId,
            ),
        );

        if (! $resultado) {
            return back()->withErrors(['trabalho' => 'O trabalho não está disponível para análise da coordenação.']);
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Trabalho aprovado com sucesso.',
        ]);
    }

    public function solicitarCorrecaoComoCoordenacao(
        Request $request,
        Instituicao $instituicao,
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap
    ) {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        abort_unless($user->can('grupopap.aprovar'), 403);
        $tenantTutorId = (string) tenancy()->tenant->getTenantKey();

        $validated = $request->validate([
            'comentario' => ['required', 'string', 'min:10', 'max:2000'],
            'ficheiro' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $resultado = $this->withExternalGrupo(
            $colegio,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
            $user,
            fn (GrupoPap $grupo) => $this->solicitarCorrecaoDaCoordenacao(
                $grupo,
                $user,
                $validated['comentario'],
                $request->file('ficheiro'),
                $tenantTutorId,
            ),
        );

        if (! $resultado) {
            return back()->withErrors(['trabalho' => 'O trabalho não está disponível para análise da coordenação.']);
        }

        return back()->with('toast', ['type' => 'warning', 'message' => 'Correção solicitada ao aluno.']);
    }

    private function aprovarTrabalhoDaCoordenacao(
        GrupoPap $grupoPap,
        User $user,
        ?string $comentario,
        string $actorTenantId,
    ): bool {
        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerAnalisadoPelaCoordenacao()) {
            return false;
        }

        $this->service->aprovarComoCoordenacao($trabalho, $user, $comentario, $actorTenantId);

        return true;
    }

    private function solicitarCorrecaoDaCoordenacao(
        GrupoPap $grupoPap,
        User $user,
        string $comentario,
        ?UploadedFile $ficheiroCorrecao,
        string $actorTenantId,
    ): bool {
        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerAnalisadoPelaCoordenacao()) {
            return false;
        }

        $this->service->solicitarCorrecaoComoCoordenacao(
            $trabalho,
            $user,
            $comentario,
            $ficheiroCorrecao,
            $actorTenantId,
        );

        return true;
    }

    private function withExternalGrupo(
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap,
        User $user,
        Closure $operation,
    ): mixed {
        $tenantTutorId = (string) tenancy()->tenant->getTenantKey();
        $vinculo = CursoTuteladoShared::query()
            ->where('tenant_tutor_id', $tenantTutorId)
            ->where('curso_tutelado_tutelado_id', $cursoTutelado)
            ->where('status', 'activo')
            ->firstOrFail();
        $tenantColega = Tenant::query()->findOrFail($vinculo->tenant_tutelado_id);

        $this->crossTenantAccessService->validarAcessoAoGrupoPap(
            $user,
            $tenantColega,
            $grupoPap,
            (string) $vinculo->getKey(),
        );

        return $this->tutelaService->executarNoTenantTutelado(
            $cursoTutelado,
            $tenantTutorId,
            function () use ($colegio, $cursoTutelado, $cursoClasse, $cursoClasseTurno, $turma, $grupoPap, $operation): mixed {
                $colegioModel = Instituicao::findOrFail($colegio);
                $cursoTuteladoModel = CursoTutelado::query()
                    ->whereKey($cursoTutelado)
                    ->whereHas('instituicaoCurso', fn ($query) => $query->where('instituicao_id', $colegioModel->id))
                    ->firstOrFail();
                $cursoClasseModel = CursoClasse::query()
                    ->whereKey($cursoClasse)
                    ->where('curso_tutelado_id', $cursoTuteladoModel->id)
                    ->firstOrFail();
                $cursoClasseTurnoModel = CursoClasseTurno::query()
                    ->whereKey($cursoClasseTurno)
                    ->where('curso_classe_id', $cursoClasseModel->id)
                    ->firstOrFail();
                $turmaModel = Turma::query()
                    ->whereKey($turma)
                    ->where('curso_classe_turno_id', $cursoClasseTurnoModel->id)
                    ->firstOrFail();
                $grupoPapModel = GrupoPap::query()
                    ->whereKey($grupoPap)
                    ->where('turma_id', $turmaModel->id)
                    ->firstOrFail();

                return $operation($grupoPapModel);
            },
        );
    }

    public function download(
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
        int $numeroVersao
    ) {
        $this->authorize('downloadVersaoTrabalho', $grupoPap);

        $versao = $grupoPap->trabalhoPap?->versoes()
            ->where('numero_versao', $numeroVersao)
            ->firstOrFail();

        if (! Storage::disk('private')->exists($versao->caminho_ficheiro)) {
            abort(404, 'Ficheiro não encontrado.');
        }

        return Storage::disk('private')->download($versao->caminho_ficheiro, $versao->nome_original);
    }

    public function visualizar(
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
        int $numeroVersao
    ) {
        $this->authorize('downloadVersaoTrabalho', $grupoPap);

        $versao = $grupoPap->trabalhoPap?->versoes()
            ->where('numero_versao', $numeroVersao)
            ->firstOrFail();

        if (! Storage::disk('private')->exists($versao->caminho_ficheiro)) {
            abort(404, 'Ficheiro não encontrado.');
        }

        return response(
            Storage::disk('private')->get($versao->caminho_ficheiro),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$versao->nome_original.'"',
            ]
        );
    }

    public function downloadCorrecao(
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
        string $feedbackId
    ) {
        $this->authorize('downloadVersaoTrabalho', $grupoPap);

        $feedback = $grupoPap->trabalhoPap?->feedbacks()->findOrFail($feedbackId);

        if (! $feedback->caminho_ficheiro_correcao ||
            ! Storage::disk('private')->exists($feedback->caminho_ficheiro_correcao)) {
            abort(404, 'Ficheiro de correção não encontrado.');
        }

        return Storage::disk('private')->download(
            $feedback->caminho_ficheiro_correcao,
            $feedback->nome_original_correcao ?? 'correcao.pdf'
        );
    }
}
