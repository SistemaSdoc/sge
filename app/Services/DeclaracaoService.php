<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\Turma;
use App\Models\TurmaAluno;
use ZipArchive;

class DeclaracaoService
{
    private string $template;

    public function __construct()
    {
        $this->template = storage_path('app/templates/Declaracao_template.docx');
    }

    public function gerar(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        Aluno $aluno
    ): string {
        $cursoTutelado->load('instituicaoCurso.curso');
        $candidato = $aluno->inscricao->candidato;
        $anoLectivo = $aluno->inscricao->anoLectivo;
        $classe = $turma->cursoClasseTurno->cursoClasse->classe;
        $curso = $cursoTutelado->instituicaoCurso->curso;

        $tipo = match ($instituicao->tipo) {
            'colegio' => 'Colégio',
            default => 'Instituto',
        };

        $turmaAluno = TurmaAluno::where('aluno_id', $aluno->id)
            ->where('turma_id', $turma->id)
            ->first();

        $notaFinal = $turmaAluno?->notas()
            ->where('periodo', 3)
            ->first();

        $resultado = $notaFinal?->situacao_anual ?? 'N/APTO';

        $substituicoes = [
            '[Nome da instituição ou colégio] -[número]'
            => $instituicao->nome . ' -' . ($instituicao->numero ?? ''),
            '[Nºnum/SP/ANO]'
            => '[Nº' . str_pad($aluno->id, 3, '0', STR_PAD_LEFT) . '/SP/' . now()->year . ']',
            '[finalidade do doc.]' => 'de frequência e aproveitamento escolar',
            'ex João Silva' => strtoupper($candidato->nome),
            '[Nome dos encarregados]' => $candidato->nome_encarregado ?? '_______________',
            '[Instituto/Colégio]' => $tipo,
            '[2025/26]' => $anoLectivo->nome,
            '[10ª]' => $classe->nome,
            '[nome do curso]' => $curso->nome,
            '[informática]' => $curso->area ?? $curso->nome,
            '[número do aluno da turma]' => (string) ($aluno->turmaAluno?->numero_na_turma ?? '___'),
            '[número de processo]' => $candidato->numero_estudante,
            '[Apto/Não apto]' => $resultado,
            'nome da instituição ou colégio' => $instituicao->nome,
            '[dia/mes/ano]' => now()->translatedFormat('d \d\e F \d\e Y'),
            '[Nome do director]' => $instituicao->subdirector ?? '_______________',
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'decl_') . '.docx';
        copy($this->template, $tmp);

        $zip = new \ZipArchive();
        $zip->open($tmp);
        $xml = $zip->getFromName('word/document.xml');

        foreach ($substituicoes as $placeholder => $valor) {
            $xml = str_replace(
                htmlspecialchars($placeholder, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($valor, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                $xml
            );
        }

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        return $tmp;
    }
}