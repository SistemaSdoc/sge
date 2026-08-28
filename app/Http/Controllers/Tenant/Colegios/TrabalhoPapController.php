<?php

namespace App\Http\Controllers\Colegios;

use App\Http\Controllers\Controller;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\GrupoPap;
use App\Models\Instituicao;
use App\Models\Turma;
use App\Services\TrabalhoPapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TrabalhoPapController extends Controller
{
    public function __construct(
        private readonly TrabalhoPapService $service
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

        $request->validate([
            'ficheiro' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $trabalho = $grupoPap->trabalhoPap;

        if (!$trabalho || !$trabalho->podeSerSubmetido()) {
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

        if (!$trabalho || !$trabalho->podeSerAnalisadoPeloTutor()) {
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

        if (!$trabalho || !$trabalho->podeSerAnalisadoPeloTutor()) {
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
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('aprovarTrabalhoComoCoordenacao', $grupoPap);

        $validated = $request->validate([
            'comentario' => ['nullable', 'string', 'max:2000'],
        ]);

        $trabalho = $grupoPap->trabalhoPap;

        if (!$trabalho || !$trabalho->podeSerAnalisadoPelaCoordenacao()) {
            return back()->withErrors(['trabalho' => 'O trabalho não está disponível para análise da coordenação.']);
        }

        $this->service->aprovarComoCoordenacao($trabalho, Auth::user(), $validated['comentario'] ?? null);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Trabalho aprovado com sucesso.',
        ]);
    }

    public function solicitarCorrecaoComoCoordenacao(
        Request $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('solicitarCorrecaoTrabalhoComoCoordenacao', $grupoPap);

        $validated = $request->validate([
            'comentario' => ['required', 'string', 'min:10', 'max:2000'],
            'ficheiro' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $trabalho = $grupoPap->trabalhoPap;

        if (!$trabalho || !$trabalho->podeSerAnalisadoPelaCoordenacao()) {
            return back()->withErrors(['trabalho' => 'O trabalho não está disponível para análise da coordenação.']);
        }

        $this->service->solicitarCorrecaoComoCoordenacao(
            $trabalho,
            Auth::user(),
            $validated['comentario'],
            $request->file('ficheiro'),
        );

        return back()->with('toast', ['type' => 'warning', 'message' => 'Correção solicitada ao aluno.']);
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

        if (!Storage::disk('private')->exists($versao->caminho_ficheiro)) {
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

        if (!Storage::disk('private')->exists($versao->caminho_ficheiro)) {
            abort(404, 'Ficheiro não encontrado.');
        }

        return response(
            Storage::disk('private')->get($versao->caminho_ficheiro),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $versao->nome_original . '"',
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

        if (!$feedback->caminho_ficheiro_correcao ||
            !Storage::disk('private')->exists($feedback->caminho_ficheiro_correcao)) {
            abort(404, 'Ficheiro de correção não encontrado.');
        }

        return Storage::disk('private')->download(
            $feedback->caminho_ficheiro_correcao,
            $feedback->nome_original_correcao ?? 'correcao.pdf'
        );
    }
}