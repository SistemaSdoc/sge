<?php

namespace App\Http\Controllers;

use App\Models\Aluno;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\ElementoGrupoPap;
use App\Models\Instituicao;
use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Models\TurmaDisciplinaProfessor;
use Illuminate\Http\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\Browsershot\Browsershot;
use App\Helpers\BrowsershotHelper;

class CertificadoController extends Controller
{
    // =========================================================================
    //  MÉTODO CENTRAL — toda a lógica de cálculo num único lugar
    // =========================================================================
    private function calcularDadosCertificado(Aluno $aluno, Turma $turma): array
    {
        $nomesPapEcs = ['prova de aptidão profissional', 'estágio curricular supervisionado'];

        $turmasDoAluno = TurmaAluno::where('aluno_id', $aluno->id)
            ->pluck('turma_id');

        $tdps = TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->whereIn('turma_id', $turmasDoAluno)
            ->get();

        $porDisciplina = [];

        foreach ($tdps as $tdp) {
            $disciplina = $tdp->classeTurnoDisciplina?->disciplina;

            if (!$disciplina)
                continue;

            $turmaAluno = TurmaAluno::where('turma_id', $tdp->turma_id)
                ->where('aluno_id', $aluno->id)
                ->first();

            if (!$turmaAluno)
                continue;

            $nota = $turmaAluno->notas()
                ->where('turma_disciplina_professor_id', $tdp->id)
                ->whereNotNull('media_final')
                ->first();

            if (!$nota)
                continue;

            $mediaArredondada = round((float) $nota->media_final * 2) / 2;
            $id = $disciplina->id;

            if (!isset($porDisciplina[$id])) {
                $porDisciplina[$id] = [
                    'disciplina' => $disciplina->nome,
                    'componente' => $disciplina->componente ?? 'tecnica',
                    'medias' => [],
                ];
            }

            $porDisciplina[$id]['medias'][] = $mediaArredondada;
        }

        $notas = [];
        $somaMedias = 0;
        $totalDisciplinas = 0;
        $notaEcs = null; // ← inicializar aqui

        foreach ($porDisciplina as $item) {
            $mediaFinal = round(
                (array_sum($item['medias']) / count($item['medias'])) * 2
            ) / 2;

            $componente = $item['componente'];
            $nomeDisc = strtolower($item['disciplina']);

            // Capturar ECS para linha fixa
            if (str_contains($nomeDisc, 'estágio')) {
                $notaEcs = $mediaFinal;
            }

            // ← PAP e ECS NÃO entram na tabela de disciplinas
            if (!in_array($nomeDisc, $nomesPapEcs)) {
                $notas[$componente][] = [
                    'disciplina' => $item['disciplina'],
                    'media_final' => $mediaFinal,
                    'extenso' => $this->numeroParaExtenso($mediaFinal),
                ];

                $somaMedias += $mediaFinal;
                $totalDisciplinas++;
            }
        }

        $mediaPC = $totalDisciplinas > 0
            ? round(($somaMedias / $totalDisciplinas) * 2) / 2
            : null;

        // Nota PAP via ElementoGrupoPap (mantém igual)
        $elementoPap = ElementoGrupoPap::whereHas(
            'grupoPap',
            fn($q) => $q->whereIn('turma_id', $turmasDoAluno)
        )->where('aluno_id', $aluno->id)->first();

        $notaPap = $elementoPap?->nota_individual
            ?? $elementoPap?->grupoPap?->nota_final
            ?? null;

        if ($notaPap !== null) {
            $notaPap = round((float) $notaPap * 2) / 2;
        }

        $classificacaoFinal = null;
        if ($mediaPC !== null && $notaPap !== null && $notaEcs !== null) {
            $classificacaoFinal = round(
                ((4 * $mediaPC) + $notaPap + $notaEcs) / 6 * 2
            ) / 2;
        }

        return [
            'notas' => $notas,
            'media_pc' => $mediaPC,
            'media_pc_extenso' => $this->numeroParaExtenso($mediaPC),
            'nota_pap' => $notaPap,
            'nota_pap_extenso' => $this->numeroParaExtenso($notaPap),
            'nota_ecs' => $notaEcs,
            'nota_ecs_extenso' => $this->numeroParaExtenso($notaEcs),
            'classificacao_final' => $classificacaoFinal,
            'classificacao_final_extenso' => $this->numeroParaExtenso($classificacaoFinal),
        ];
    }

    // =========================================================================
    //  SHOW — página de verificação via QR Code
    // =========================================================================
    public function show(Aluno $aluno): Response
    {
        // ✅ CORRIGIDO: Acesso via turmas (relação atual)
        $turmaAluno = $aluno->turmas()
            ->with([
                'cursoClasseTurno.cursoClasse.classe',
                'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
                'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao',
            ])
            ->wherePivot('ano_lectivo', date('Y'))
            ->first();

        if (!$turmaAluno) {
            return response(['erro' => 'Aluno não possui turma no ano lectivo atual'], 404);
        }

        if (!$aluno->inscricao || !$aluno->inscricao->candidato) {
            return response(['erro' => 'Aluno não possui inscrição ou candidato associado'], 404);
        }

        $candidato = $aluno->inscricao->candidato;

        // ✅ CORRIGIDO: Acesso via cursoClasseTurno
        $instituicaoCurso = $turmaAluno->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso;

        // ── Usa o mesmo cálculo do gerar() ───────────────────────────────
        $calc = $this->calcularDadosCertificado($aluno, $turmaAluno);

        return response([
            'id' => $aluno->id,
            'nome' => $candidato->nome,           //  CORRIGIDO: nome_completo → nome
            'matricula' => $aluno->matricula,
            'bi' => $aluno->user->bi,
            'instituicao' => $instituicaoCurso?->instituicao?->nome,
            'curso' => $instituicaoCurso?->curso?->nome,  // ✅ CORRIGIDO: name → nome
            'classe' => $turmaAluno->cursoClasseTurno?->cursoClasse?->classe?->nome,  //  CORRIGIDO
            'ano_lectivo' => date('Y') . '/' . (date('Y') + 1),  // ✅ CORRIGIDO: removido ano_letivo
            'ano_defesa' => date('Y'),
            'resultado_final' => $this->determinarResultadoFinal($calc['classificacao_final']),
            'media_pc' => $calc['media_pc'],
            'nota_pap' => $calc['nota_pap'],
            'nota_ecs' => $calc['nota_ecs'],
            'classificacao_final' => $calc['classificacao_final'],
            'notas' => $calc['notas'],
        ]);
    }

    private function determinarResultadoFinal(?float $media): string
    {
        if ($media === null) {
            return 'PENDENTE';
        }

        return $media >= 10 ? 'APTO' : 'NÃO_APTO';
    }

    // =========================================================================
    //  GERAR — PDF via Browsershot
    // =========================================================================
    public function gerar(
        Instituicao $instituicao,           // 1º - {instituicao}
        CursoTutelado $cursoTutelado,       // 2º - {cursoTutelado}
        CursoClasse $cursoClasse,           // 3º - {cursoClasse}
        CursoClasseTurno $cursoClasseTurno, // 4º - {cursoClasseTurno}
        Turma $turma,                       // 5º - {turma}
        Aluno $aluno
    ) {
        // ✅ ADICIONAR ESTA LINHA AQUI (início do método)
        $instituicaoCurso = $cursoTutelado->instituicaoCurso;
        $candidato = $aluno->inscricao->candidato;

        // ── Usa o mesmo cálculo do show() ────────────────────────────────
        $calc = $this->calcularDadosCertificado($aluno, $turma);

        // QR Code
        // ✅ Depois
        $url = env('FRONTEND_URL', 'http://192.168.1.168:3000') . '/certificados/' . $aluno->id . '/verificar';
        $qrcode = base64_encode(QrCode::format('png')->size(120)->generate($url));

        $dados = array_merge($calc, [
            'instituicao' => $instituicao,
            'curso' => $instituicaoCurso->curso,
            'turma' => $turma,
            'candidato' => $candidato,
            'aluno' => $aluno,
            'ano_letivo' => date('Y') . '/' . (date('Y') + 1),  // CORRIGIDO: removido ano_letivo
            'qrcode' => $qrcode,
        ]);

        $html = view('certificados.certificado', $dados)->render();

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

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header(
                'Content-Disposition',
                'attachment; filename="certificado_' . $candidato->nome . '.pdf"'  //  CORRIGIDO: nome_completo → nome
            );
    }

    public function gerarTutora(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        Turma $turma,
        Aluno $aluno
    ) {
        $cursoTutelado->load('instituicaoCurso.curso');
        $instituicaoCurso = $cursoTutelado->instituicaoCurso;
        $candidato = $aluno->inscricao->candidato;

        $calc = $this->calcularDadosCertificado($aluno, $turma);

        $url = env('FRONTEND_URL', 'http://192.168.1.168:3000') . '/certificados/' . $aluno->id . '/verificar';
        $qrcode = base64_encode(QrCode::format('png')->size(120)->generate($url));

        $dados = array_merge($calc, [
            'instituicao' => $instituicao,
            'curso' => $instituicaoCurso->curso,
            'turma' => $turma,
            'candidato' => $candidato,
            'aluno' => $aluno,
            'ano_letivo' => date('Y') . '/' . (date('Y') + 1),
            'qrcode' => $qrcode,
        ]);

        $html = view('certificados.certificado', $dados)->render();

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

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header(
                'Content-Disposition',
                'attachment; filename="certificado_' . $candidato->nome . '.pdf"'
            );
    }

    // =========================================================================
    //  HELPER — número → extenso
    // =========================================================================
    private function numeroParaExtenso(?float $numero): string
    {
        if ($numero === null) {
            return '—';
        }

        $chave = (int) round($numero);

        $mapa = [
            0 => 'Zero',
            1 => 'Um',
            2 => 'Dois',
            3 => 'Três',
            4 => 'Quatro',
            5 => 'Cinco',
            6 => 'Seis',
            7 => 'Sete',
            8 => 'Oito',
            9 => 'Nove',
            10 => 'Dez',
            11 => 'Onze',
            12 => 'Doze',
            13 => 'Treze',
            14 => 'Catorze',
            15 => 'Quinze',
            16 => 'Dezasseis',
            17 => 'Dezassete',
            18 => 'Dezoito',
            19 => 'Dezanove',
            20 => 'Vinte',
        ];

        return ($mapa[$chave] ?? (string) $chave) . ' Valores';
    }
}