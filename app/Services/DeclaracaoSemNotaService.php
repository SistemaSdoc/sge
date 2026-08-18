<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\Turma;
use App\Models\TurmaAluno;
use Carbon\Carbon;
use ZipArchive;

class DeclaracaoSemNotaService
{
    private string $template;

    public function __construct()
    {
        $this->template = storage_path('app/templates/Declaracao_template.docx');
    }

    public function obterAnoLectivoNome(Aluno $aluno, Turma $turma): string
    {
        return $turma->anoLectivo?->nome
            ?? $aluno->inscricao?->anoLectivo?->nome
            ?? date('Y') . '/' . (date('Y') + 1);
    }

    public function gerar(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        Aluno $aluno,
        ?string $efeito = null
    ): string {
        Carbon::setLocale('pt');

        $cursoTutelado->load('instituicaoCurso.curso');
        $candidato = $aluno->inscricao->candidato;
        $anoLectivo = $turma->anoLectivo ?? $aluno->inscricao->anoLectivo;
        $classe = $turma->cursoClasseTurno->cursoClasse->classe;
        $curso = $cursoTutelado->instituicaoCurso->curso;

        $tipo = match ($instituicao->tipo) {
            'colegio' => 'Colégio',
            default => 'Instituto',
        };

        $turno = $cct->turno?->nome ?? '';
        $curriculum = match (true) {
            str_contains(strtolower($turno), 'noite') => 'Noturno',
            default => 'Diurno',
        };

        $turmaAluno = TurmaAluno::where('aluno_id', $aluno->id)
            ->where('turma_id', $turma->id)
            ->first();

        $notaFinal = $turmaAluno?->notas()
            ->where('periodo', 3)
            ->first();

        $numeroDeclaracao = TurmaAluno::whereHas(
            'turma',
            fn($q) =>
            $q->where('ano_lectivo_id', $anoLectivo->id)
        )->where('created_at', '<=', $turmaAluno->created_at)
            ->count();

        $resultado = $notaFinal?->situacao_anual ?? 'Não Apto';

        $substituicoes = [
            'nome da instituição ou colégio' => mb_strtoupper($instituicao->nome, 'UTF-8'),
            'declaracao_numero' => 'Nº' . str_pad($numeroDeclaracao, 3, '0', STR_PAD_LEFT) . '/SP/' . now()->year,
            '[finalidade do doc.]' => 'de ' . ($efeito ?? 'de frequência e aproveitamento escolar'),
            'ex João Silva' => mb_strtoupper($candidato->nome, 'UTF-8'),
            '[Nome dos encarregados]' => $candidato->filiacao ?? '_______________',
            '[Instituto/Colégio]' => $tipo,
            '[2025/26]' => $anoLectivo->nome,
            '[10ª]' => $classe->nome,
            '[nome do curso]' => $curso->nome,
            'Curriculum Diúrno' => 'Curriculum ' . $curriculum,
            '[turma]' => $turma->nome,
            '[informática]' => $curso->area ?? $curso->nome,
            '[número do aluno da turma]' => (string) ($turmaAluno?->numero_na_turma ?? '___'),
            '[número de processo]' => $candidato->numero_estudante,
            '[resultado_final]' => $resultado ?? 'Não Apto',
            'RSLTFINAL' => $resultado,
            '[dia/mes/ano]' => now()->locale('pt')->isoFormat('D [de] MMMM [de] YYYY'),
            '[Nome do director]' => $instituicao->subdirector ?? '_______________',
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'decl_') . '.docx';
        copy($this->template, $tmp);

        $zip = new ZipArchive;
        $zip->open($tmp);
        $xml = $zip->getFromName('word/document.xml');

        foreach ($substituicoes as $placeholder => $valor) {
            $xml = str_replace(
                $placeholder,
                htmlspecialchars($valor, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                $xml
            );
        }

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        return $tmp;
    }
}