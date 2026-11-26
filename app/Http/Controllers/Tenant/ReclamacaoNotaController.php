<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ReclamacaoNota\DecidirReclamacaoRequest;
use App\Http\Requests\Tenant\ReclamacaoNota\StoreReclamacaoNotaRequest;
use App\Models\Central\Disciplina;
use App\Models\Tenant\AnexoReclamacaoNota;
use App\Models\Tenant\CursoTuteladoProfessor;
use App\Models\Tenant\Nota;
use App\Models\Tenant\ReclamacaoNota;
use App\Models\Tenant\User;
use App\Notifications\ReclamacaoNotaCriadaNotification;
use App\Notifications\ReclamacaoNotaDecididaNotification;
use App\Services\Tenant\NotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReclamacaoNotaController extends Controller
{
    public function __construct(private readonly NotaService $notaService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', ReclamacaoNota::class);

        $user = $request->user();

        $query = ReclamacaoNota::query()
            ->with([
                'nota.turmaAluno.aluno.user',
                'nota.turmaAluno.aluno.inscricao.candidato',
                'nota.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
                'nota.turmaDisciplinaProfessor.professor.user',
                'solicitante.aluno',
                'decisor',
                'anexos',
            ])
            ->orderByDesc('created_at');

        if ($user->hasRole('Aluno')) {
            $query->where('solicitado_por', $user->id);
        } elseif ($user->hasRole('Professor')
            || $user->hasAnyRole([
                'Membro do Grupo Disciplinar',
                'Coordenador do Grupo Disciplinar',
            ])) {
            $professorId = $user->professor?->id;
            $cursosGrupoDisciplinar = CursoTuteladoProfessor::query()
                ->where('professor_id', $professorId)
                ->whereIn('grupo_disciplinar', ['membro', 'coordenador'])
                ->pluck('curso_tutelado_id');

            $query->where(function ($q) use ($professorId, $cursosGrupoDisciplinar): void {
                $q->whereHas('nota.turmaDisciplinaProfessor', fn ($tdp) => $tdp->where('professor_id', $professorId));

                if ($cursosGrupoDisciplinar->isNotEmpty()) {
                    $q->orWhereHas(
                        'nota.turmaDisciplinaProfessor.turma.cursoClasseTurno.cursoClasse.cursoTutelado',
                        fn ($curso) => $curso->whereIn('id', $cursosGrupoDisciplinar)
                    );
                }
            });
        } elseif ($user->hasAnyRole(['Director', 'Subdirector'])) {
            $instituicaoId = $user->instituicao_id;
            $query->whereHas('nota.turmaDisciplinaProfessor.professor.user', fn ($q) => $q->where('instituicao_id', $instituicaoId));
        }

        $statusCounts = array_fill_keys([
            'pendente',
            'em_analise',
            'aguarda_informacao',
            'aprovada',
            'reprovada',
        ], 0);

        $statusCounts = array_replace(
            $statusCounts,
            (clone $query)
                ->reorder()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status')
                ->all(),
        );

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => $request->query('status'),
            'periodo' => $request->query('periodo'),
        ];

        if ($filters['search'] !== '') {
            $termo = '%'.$filters['search'].'%';
            $disciplinaIds = Disciplina::query()
                ->where('nome', 'like', $termo)
                ->pluck('id');

            $query->where(function ($searchQuery) use ($termo, $disciplinaIds): void {
                $searchQuery
                    ->where('justificativa', 'like', $termo)
                    ->orWhereHas('nota.turmaAluno.aluno', function ($alunoQuery) use ($termo): void {
                        $alunoQuery
                            ->where('matricula', 'like', $termo)
                            ->orWhere('numero_processo', 'like', $termo)
                            ->orWhereHas('user', fn ($userQuery) => $userQuery->where('nome', 'like', $termo))
                            ->orWhereHas('inscricao.candidato', fn ($candidatoQuery) => $candidatoQuery->where('nome', 'like', $termo));
                    })
                    ->orWhereHas(
                        'nota.turmaDisciplinaProfessor.classeTurnoDisciplina',
                        fn ($classeTurnoDisciplinaQuery) => $classeTurnoDisciplinaQuery->whereIn('disciplina_id', $disciplinaIds),
                    );
            });
        }

        if (in_array($filters['status'], ['pendente', 'em_analise', 'aguarda_informacao', 'aprovada', 'reprovada'], true)) {
            $query->where('status', $filters['status']);
        } else {
            $filters['status'] = '';
        }

        if (in_array((string) $filters['periodo'], ['1', '2', '3', '4'], true)) {
            $query->whereHas('nota', fn ($notaQuery) => $notaQuery->where('periodo', (int) $filters['periodo']));
        } else {
            $filters['periodo'] = '';
        }

        return inertia('tenant/reclamacoes-notas/index', [
            'reclamacoes' => Inertia::scroll(fn () => $query
                ->orderByDesc('created_at')
                ->paginate(15)
                ->withQueryString()),
            'filters' => $filters,
            'statusCounts' => $statusCounts,
            'canDecidir' => $user->hasRole('Professor')
                || $user->hasAnyRole([
                    'Membro do Grupo Disciplinar',
                    'Coordenador do Grupo Disciplinar',
                ]),
        ]);
    }

    public function store(StoreReclamacaoNotaRequest $request): RedirectResponse
    {
        $this->authorize('create', ReclamacaoNota::class);
        $validated = $request->validated();

        $nota = Nota::query()
            ->with(['turmaAluno.aluno', 'turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina'])
            ->findOrFail($validated['nota_id']);

        abort_unless(
            $nota->turmaAluno?->aluno?->user_id === $request->user()->id,
            404
        );

        if ($nota->periodo === 4 || $nota->{$validated['componente']} === null) {
            throw ValidationException::withMessages([
                'componente' => 'Esta nota ainda não está disponível para reclamação.',
            ]);
        }

        $existe = ReclamacaoNota::query()
            ->where('nota_id', $nota->id)
            ->where('componente', $validated['componente'])
            ->whereIn('status', ['pendente', 'em_analise', 'aguarda_informacao'])
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages([
                'componente' => 'Já existe uma reclamação aberta para esta nota.',
            ]);
        }

        $reclamacao = DB::transaction(function () use ($request, $nota, $validated): ReclamacaoNota {
            $reclamacao = ReclamacaoNota::create([
                'nota_id' => $nota->id,
                'solicitado_por' => $request->user()->id,
                'componente' => $validated['componente'],
                'nota_original' => $nota->{$validated['componente']},
                'nota_reclamada' => $validated['nota_reclamada'] ?? null,
                'justificativa' => $validated['justificativa'],
                'status' => 'pendente',
            ]);

            foreach ($request->file('anexos', []) as $anexo) {
                $caminho = $anexo->store("reclamacoes-notas/{$reclamacao->id}", 'private');

                AnexoReclamacaoNota::create([
                    'reclamacao_nota_id' => $reclamacao->id,
                    'caminho' => $caminho,
                    'nome_original' => $anexo->getClientOriginalName(),
                    'mime_type' => $anexo->getMimeType() ?: 'application/octet-stream',
                    'tamanho_bytes' => $anexo->getSize() ?: 0,
                ]);
            }

            return $reclamacao;
        });

        $reclamacao->loadMissing('nota.turmaDisciplinaProfessor.professor.user');
        $professorUser = $reclamacao->nota?->turmaDisciplinaProfessor?->professor?->user;
        if ($professorUser) {
            $professorUser->notify(new ReclamacaoNotaCriadaNotification($reclamacao));
        }

        User::query()
            ->role(['Director', 'Subdirector'])
            ->where('instituicao_id', $professorUser?->instituicao_id)
            ->get()
            ->each(fn (User $diretor) => $diretor->notify(new ReclamacaoNotaCriadaNotification($reclamacao)));

        $reclamacao->loadMissing('nota.turmaDisciplinaProfessor.turma.cursoClasseTurno.cursoClasse.cursoTutelado');
        $cursoTuteladoId = $reclamacao->nota
            ?->turmaDisciplinaProfessor
            ?->turma
            ?->cursoClasseTurno
            ?->cursoClasse
            ?->cursoTutelado
            ?->id;

        if ($cursoTuteladoId) {
            CursoTuteladoProfessor::query()
                ->with('professor.user')
                ->where('curso_tutelado_id', $cursoTuteladoId)
                ->whereIn('grupo_disciplinar', ['membro', 'coordenador'])
                ->get()
                ->each(function ($vinculo) use ($reclamacao): void {
                    $vinculo->professor?->user?->notify(new ReclamacaoNotaCriadaNotification($reclamacao));
                });
        }

        return back()->with('success', 'Reclamação enviada ao professor e à direção.');
    }

    public function decidir(DecidirReclamacaoRequest $request, ReclamacaoNota $reclamacaoNota): RedirectResponse
    {
        $user = Auth::guard('tenant')->user();
        logger()->info('update reclamacao', [
            'exists' => $reclamacaoNota->exists,
            'id' => $reclamacaoNota->id,
            'status' => $reclamacaoNota->status,
        ]);

        $this->authorize('update', $reclamacaoNota);
        $validated = $request->validated();

        DB::transaction(function () use ($request, $reclamacaoNota, $validated): void {
            if ($validated['decisao'] === 'aprovada') {
                $dados = match ($reclamacaoNota->componente) {
                    'mac' => ['mac' => (float) $validated['nota_corrigida']],
                    'nota_prova_professor' => ['npp' => (float) $validated['nota_corrigida']],
                    'nota_prova_trimestral' => ['npt' => (float) $validated['nota_corrigida']],
                };

                $this->notaService->corrigirNota($reclamacaoNota->nota, $dados);
            }

            $reclamacaoNota->update([
                'status' => $validated['decisao'],
                'parecer' => $validated['parecer'],
                'nota_corrigida' => $validated['decisao'] === 'aprovada'
                    ? $validated['nota_corrigida']
                    : null,
                'decidido_por' => $request->user()->id,
                'decidido_em' => now(),
            ]);
        });

        $reclamacaoNota->loadMissing('solicitante');
        $reclamacaoNota->solicitante?->notify(new ReclamacaoNotaDecididaNotification($reclamacaoNota));

        return back()->with('success', 'Decisão registada na reclamação.');
    }

    public function anexos(Request $request, ReclamacaoNota $reclamacaoNota, AnexoReclamacaoNota $anexo): Response
    {
        $this->authorize('download', $reclamacaoNota);
        abort_unless($anexo->reclamacao_nota_id === $reclamacaoNota->id, 404);

        abort_unless(in_array($anexo->mime_type, ['application/pdf', 'image/jpeg', 'image/png'], true), 415);

        $conteudo = Storage::disk('private')->get($anexo->caminho);
        abort_if($conteudo === null, 404);

        $nome = preg_replace('/[\r\n"]/', '', basename($anexo->nome_original));

        return response($conteudo, 200, [
            'Content-Type' => $anexo->mime_type,
            'Content-Disposition' => 'inline; filename="'.$nome.'"',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    public function baixarAnexo(Request $request, ReclamacaoNota $reclamacaoNota, AnexoReclamacaoNota $anexo): StreamedResponse
    {
        $this->authorize('download', $reclamacaoNota);
        abort_unless($anexo->reclamacao_nota_id === $reclamacaoNota->id, 404);

        $conteudo = Storage::disk('private')->get($anexo->caminho);
        abort_if($conteudo === null, 404);

        $nome = preg_replace('/[\r\n"]/', '', basename($anexo->nome_original));

        return response()->streamDownload(
            static function () use ($conteudo): void {
                echo $conteudo;
            },
            $nome,
            ['Content-Type' => $anexo->mime_type],
        );
    }
}
