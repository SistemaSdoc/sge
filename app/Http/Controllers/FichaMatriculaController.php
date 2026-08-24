<?php

namespace App\Http\Controllers;

use App\Helpers\BrowsershotHelper;
use App\Models\Aluno;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

class FichaMatriculaController extends Controller
{
    public function pdf(Aluno $aluno)
    {
        Gate::authorize('view', $aluno);

        $aluno->load([
            'inscricao.candidato:id,nome,bi,email,telefone,genero,nacionalidade,naturalidade,morada,filiacao,data_nascimento',
            'inscricao.cursoClasseTurno.turno:id,nome',
            'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
            'turmas' => fn ($q) => $q->wherePivot('activo', true)
                ->with([
                    'cursoClasseTurno.cursoClasse.classe:id,nome',
                    'anoLectivo:id,nome',
                ]),
        ]);

        $dados = $this->montarDados($aluno);

        $html = view('fichas.ficha-matricula', $dados)->render();

        $pdf = Browsershot::html($html)
            ->setChromePath(BrowsershotHelper::getChromePath())
            ->setNodeBinary(BrowsershotHelper::getNodeBinary())
            ->setNpmBinary(BrowsershotHelper::getNpmBinary())
            ->addChromiumArguments([
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--headless=new',
                '--no-zygote',
                '--single-process',
            ])
            ->timeout(60000)
            ->format('A4')
            ->portrait()
            ->noSandbox()
            ->waitUntilNetworkIdle()
            ->margins(0, 0, 0, 0)
            ->pdf();

        // [ALTERADO] Str::slug trata acentos, espaços e caracteres especiais automaticamente
        $nome = $aluno->inscricao?->candidato?->nome ?? 'aluno';
        $nomeSanitizado = Str::slug($nome);
        $nomeArquivo = "ficha-matricula-{$nomeSanitizado}-{$aluno->numero_processo}.pdf";

        // [ALTERADO] streamDownload já trata corretamente o Content-Disposition com acentos (RFC 6266)
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf;
        }, $nomeArquivo, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function montarDados(Aluno $aluno): array
    {
        $candidato = $aluno->inscricao?->candidato;
        [$nomePai, $nomeMae] = $this->separarFiliacao($candidato?->filiacao);

        return [
            'numero_processo' => $aluno->numero_processo ?? '',
            'nome' => $candidato?->nome ?? '',
            'sexo' => match ($candidato?->genero) {
                'M' => 'Masculino',
                'F' => 'Feminino',
                default => '',
            },
            'idade' => $candidato?->data_nascimento
                ? Carbon::parse($candidato->data_nascimento)->age
                : '',
            'data_nascimento' => $candidato?->data_nascimento
                ? Carbon::parse($candidato->data_nascimento)->format('d/m/Y')
                : '',
            'local_nascimento' => $candidato?->naturalidade ?? '',
            'numero_bi' => $candidato?->bi ?? '',
            'data_emissao_bi' => '',
            'arquivo_identificacao' => '',
            'nome_pai' => $nomePai ?? '',
            'nome_mae' => $nomeMae ?? '',
            'classe' => $aluno->turmas->first()?->cursoClasseTurno?->cursoClasse?->classe?->nome
                ?? $aluno->inscricao?->cursoClasseTurno?->cursoClasse?->classe?->nome
                ?? '',
            'turno' => $aluno->inscricao?->cursoClasseTurno?->turno?->nome ?? '',
            'telefone' => $candidato?->telefone ?? '',
            'curso' => $aluno->inscricao?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome ?? '',
            'nome_encarregado' => $nomeMae ?? '',
            'nome_director' => '',
            'escola' => $aluno->inscricao?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->instituicao?->nome ?? '',
            'local_data' => now()->format('d/m/Y'),
        ];
    }

    private function separarFiliacao(?string $filiacao): array
    {
        if (! $filiacao || trim($filiacao) === '') {
            return ['', ''];
        }

        $partes = array_values(array_filter(array_map(
            'trim',
            preg_split('/\s+e\s+/i', $filiacao)
        )));

        return [$partes[0] ?? '', $partes[1] ?? ''];
    }
}
