<?php

namespace App\Http\Controllers\Tenant\Colegios;

use App\Exports\ListaAlunosTurmaExport;
use App\Http\Controllers\Controller;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportarListaAlunosTurmaController extends Controller
{
    public function __construct(private readonly ListaAlunosTurmaExport $export) {}

    public function exportarLocal(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
    ): BinaryFileResponse {
        abort_unless(
            (string) $cursoTutelado->instituicaoCurso?->instituicao_id === (string) $instituicao->getKey()
                && (string) $cursoClasse->curso_tutelado_id === (string) $cursoTutelado->getKey()
                && (string) $cursoClasseTurno->curso_classe_id === (string) $cursoClasse->getKey()
                && (string) $turma->curso_classe_turno_id === (string) $cursoClasseTurno->getKey(),
            404,
        );

        Gate::authorize('view', $turma);

        return $this->gerarLista($turma);
    }

    public function exportar(
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
    ): BinaryFileResponse {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $shared = CursoTuteladoShared::query()
            ->where('tenant_tutor_id', tenancy()->tenant->getTenantKey())
            ->where('curso_tutelado_tutelado_id', $cursoTutelado)
            ->where('status', 'activo')
            ->firstOrFail();

        abort_unless($user->can('pautas.view'), 403);

        if ($user->hasRole('Secretario do Curso')) {
            abort_unless(
                $user->instituicao?->tipo === 'instituto'
                    && $user->cursosSecretariados()
                        ->whereHas('instituicaoCurso', fn ($query) => $query->where('curso_id', $shared->curso_id))
                        ->exists(),
                404,
            );
        }

        $tenant = Tenant::findOrFail($shared->tenant_tutelado_id);

        return $tenant->run(function () use ($colegio, $cursoTutelado, $cursoClasse, $cursoClasseTurno, $turma): BinaryFileResponse {
            $instituicao = Instituicao::findOrFail($colegio);
            $cursoTuteladoModel = CursoTutelado::query()
                ->whereKey($cursoTutelado)
                ->whereHas('instituicaoCurso', fn ($query) => $query->where('instituicao_id', $instituicao->id))
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

            return $this->gerarLista($turmaModel);
        });
    }

    private function gerarLista(Turma $turma): BinaryFileResponse
    {
        $turma->load([
            'anoLectivo',
            'cursoClasseTurno.cursoClasse.classe',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao',
        ]);

        $instituicao = $turma->cursoClasseTurno
            ->cursoClasse
            ->cursoTutelado
            ->instituicaoCurso
            ->instituicao;
        $director = $instituicao->users()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Director'))
            ->first();
        $curso = $turma->cursoClasseTurno
            ->cursoClasse
            ->cursoTutelado
            ->instituicaoCurso
            ->curso;
        $classe = $turma->cursoClasseTurno->cursoClasse->classe;

        $alunos = $turma->alunos()
            ->wherePivot('activo', true)
            ->with([
                'inscricao.candidato:id,nome,genero,data_nascimento,telefone',
                'user:id,telefone',
            ])
            ->get()
            ->values()
            ->map(fn ($aluno, $index) => [
                'numero' => $index + 1,
                'nome' => $aluno->inscricao?->candidato?->nome ?? '—',
                'processo' => (string) ($aluno->numero_processo ?? $aluno->matricula ?? '—'),
                'idade' => $aluno->inscricao?->candidato?->data_nascimento?->age ?? '—',
                'sexo' => $aluno->inscricao?->candidato?->genero_label ?? '—',
                'contacto' => (string) ($aluno->inscricao?->candidato?->telefone ?? $aluno->user?->telefone ?? '—'),
            ])
            ->all();

        $path = $this->export->gerar([
            'instituicao' => $instituicao->nome,
            'director' => $director?->nome ?? '',
            'curso' => $curso?->nome ?? '',
            'ano_lectivo' => $turma->anoLectivo?->nome ?? now()->year.'/'.(now()->year + 1),
            'classe' => $classe?->nome ?? '',
            'turma' => $turma->nome,
            'sala' => $turma->sala ?? '',
        ], $alunos);

        return response()
            ->download(
                $path,
                'lista_alunos_turma_'.str($turma->nome)->slug().'.xlsx',
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            )
            ->deleteFileAfterSend(true);
    }
}
