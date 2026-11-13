<?php

namespace App\Http\Controllers\Tenant\Colegios\Pap;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Pap\Trabalho\AprovarTrabalhoPapRequest;
use App\Http\Requests\Tenant\Pap\Trabalho\SolicitarCorrecaoTrabalhoPapRequest;
use App\Http\Requests\Tenant\Pap\Trabalho\SubmeterTrabalhoPapRequest;
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
use App\Services\Tenant\Pap\Trabalho\RevisaoTrabalhoPapCoordenacaoService;
use App\Services\Tenant\Pap\Trabalho\RevisaoTrabalhoPapTutorService;
use App\Services\Tenant\Pap\Trabalho\SubmissaoTrabalhoPapService;
use App\Services\Tenant\Tutela\TutelaService;
use Closure;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TrabalhoPapController extends Controller
{
    /**
     * Recebe os serviços de trabalho PAP e de contexto cross-tenant.
     */
    public function __construct(
        private readonly SubmissaoTrabalhoPapService $submissaoTrabalhoPapService,
        private readonly RevisaoTrabalhoPapTutorService $revisaoTrabalhoPapTutorService,
        private readonly RevisaoTrabalhoPapCoordenacaoService $revisaoTrabalhoPapCoordenacaoService,
        private readonly TutelaService $tutelaService,
        private readonly CrossTenantAccessService $crossTenantAccessService,
    ) {}

    /**
     * Recebe uma versão do trabalho submetida pelo aluno.
     */
    public function submeter(
        SubmeterTrabalhoPapRequest $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
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

        $validated = $request->validated();
        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerSubmetido()) {
            return back()->withErrors(['ficheiro' => 'O trabalho não pode ser submetido neste momento.']);
        }

        $this->submissaoTrabalhoPapService->submeter(
            $trabalho,
            Auth::guard('tenant')->user(),
            $validated['ficheiro'],
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Trabalho submetido com sucesso. Aguarda revisão do professor tutor.',
        ]);
    }

    /**
     * Regista a aprovação do trabalho pelo tutor.
     */
    public function aprovarComoTutor(
        AprovarTrabalhoPapRequest $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        abort_unless(! $user->hasAnyRole(['Director', 'Subdirector']), 403);
        $this->authorize('aprovarTrabalhoComoTutor', $grupoPap);
        $validated = $request->validated();
        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerAnalisadoPeloTutor()) {
            return back()->withErrors(['trabalho' => 'O trabalho não está disponível para análise do tutor.']);
        }

        $this->revisaoTrabalhoPapTutorService->aprovar($trabalho, $user, $validated['comentario'] ?? null);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Trabalho enviado para a coordenação.',
        ]);
    }

    /**
     * Regista um pedido de correcção do trabalho feito pelo tutor.
     */
    public function solicitarCorrecaoComoTutor(
        SolicitarCorrecaoTrabalhoPapRequest $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        $this->authorize('solicitarCorrecaoTrabalhoComoTutor', $grupoPap);
        $validated = $request->validated();
        $trabalho = $grupoPap->trabalhoPap;

        if (! $trabalho || ! $trabalho->podeSerAnalisadoPeloTutor()) {
            return back()->withErrors(['trabalho' => 'O trabalho não está disponível para análise do tutor.']);
        }

        $this->revisaoTrabalhoPapTutorService->solicitarCorrecao(
            $trabalho,
            Auth::guard('tenant')->user(),
            $validated['comentario'],
            $request->file('ficheiro'),
        );

        return back()->with('toast', ['type' => 'warning', 'message' => 'Correção solicitada ao aluno.']);
    }

    /**
     * Regista a aprovação definitiva do trabalho pela coordenação do colégio.
     */
    public function aprovarComoCoordenacao(
        AprovarTrabalhoPapRequest $request,
        Instituicao $instituicao,
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap,
    ) {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        abort_if($user->hasRole('Secretario do Curso'), 403);
        abort_unless($user->can('grupopap.aprovar'), 403);
        $tenantTutorId = (string) tenancy()->tenant->getTenantKey();
        $validated = $request->validated();
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

    /**
     * Regista um pedido de correcção do trabalho pela coordenação do colégio.
     */
    public function solicitarCorrecaoComoCoordenacao(
        SolicitarCorrecaoTrabalhoPapRequest $request,
        Instituicao $instituicao,
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap,
    ) {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        abort_if($user->hasRole('Secretario do Curso'), 403);
        abort_unless($user->can('grupopap.aprovar'), 403);
        $tenantTutorId = (string) tenancy()->tenant->getTenantKey();
        $validated = $request->validated();
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

    /**
     * Aprova o trabalho no tenant onde o grupo está guardado.
     */
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

        $this->revisaoTrabalhoPapCoordenacaoService->aprovar(
            $trabalho,
            $user,
            $comentario,
            $actorTenantId,
        );

        return true;
    }

    /**
     * Solicita uma correcção no tenant onde o grupo está guardado.
     */
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

        $this->revisaoTrabalhoPapCoordenacaoService->solicitarCorrecao(
            $trabalho,
            $user,
            $comentario,
            $ficheiroCorrecao,
            $actorTenantId,
        );

        return true;
    }

    /**
     * Valida o acesso e executa uma operação no tenant tutelado.
     *
     * @param  Closure(GrupoPap): mixed  $operation
     */
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
            function () use (
                $colegio,
                $cursoTutelado,
                $cursoClasse,
                $cursoClasseTurno,
                $turma,
                $grupoPap,
                $operation,
            ): mixed {
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

    /**
     * Descarrega uma versão do trabalho no tenant de origem.
     */
    public function download(
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap,
        int $numeroVersao,
    ) {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        abort_unless($user->can('grupopap.view'), 403);

        return $this->withExternalGrupo(
            $colegio,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
            $user,
            function (GrupoPap $grupoPap) use ($numeroVersao) {
                $versao = $grupoPap->trabalhoPap?->versoes()
                    ->where('numero_versao', $numeroVersao)
                    ->firstOrFail();
                /** @var FilesystemAdapter $privateStorage */
                $privateStorage = Storage::disk('private');

                if (! $privateStorage->exists($versao->caminho_ficheiro)) {
                    abort(404, 'Ficheiro não encontrado.');
                }

                return $privateStorage->download($versao->caminho_ficheiro, $versao->nome_original);
            },
        );
    }

    /**
     * Apresenta no navegador uma versão do trabalho no tenant de origem.
     */
    public function visualizar(
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap,
        int $numeroVersao,
    ) {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        abort_unless($user->can('grupopap.view'), 403);

        return $this->withExternalGrupo(
            $colegio,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
            $user,
            function (GrupoPap $grupoPap) use ($numeroVersao) {
                $versao = $grupoPap->trabalhoPap?->versoes()
                    ->where('numero_versao', $numeroVersao)
                    ->firstOrFail();
                /** @var FilesystemAdapter $privateStorage */
                $privateStorage = Storage::disk('private');

                if (! $privateStorage->exists($versao->caminho_ficheiro)) {
                    abort(404, 'Ficheiro não encontrado.');
                }

                return response(
                    $privateStorage->get($versao->caminho_ficheiro),
                    200,
                    [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="'.$versao->nome_original.'"',
                    ]
                );
            },
        );
    }

    /**
     * Descarrega um ficheiro de correcção no tenant de origem.
     */
    public function downloadCorrecao(
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap,
        string $feedbackId,
    ) {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();
        abort_unless($user->can('grupopap.view'), 403);

        return $this->withExternalGrupo(
            $colegio,
            $cursoTutelado,
            $cursoClasse,
            $cursoClasseTurno,
            $turma,
            $grupoPap,
            $user,
            function (GrupoPap $grupoPap) use ($feedbackId) {
                $feedback = $grupoPap->trabalhoPap?->feedbacks()->findOrFail($feedbackId);
                /** @var FilesystemAdapter $privateStorage */
                $privateStorage = Storage::disk('private');

                if (
                    ! $feedback?->caminho_ficheiro_correcao
                    || ! $privateStorage->exists($feedback->caminho_ficheiro_correcao)
                ) {
                    abort(404, 'Ficheiro de correção não encontrado.');
                }

                return $privateStorage->download(
                    $feedback->caminho_ficheiro_correcao,
                    $feedback->nome_original_correcao ?? 'correcao.pdf'
                );
            },
        );
    }
}
