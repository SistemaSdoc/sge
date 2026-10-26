<?php

namespace App\Services\Tenant\QuadroHonra;

use App\Models\Tenant\QuadroHonra;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/** Renderiza o snapshot guardado pelo quadro num documento A4 horizontal. */
class QuadroHonraPdfService
{
    public function download(QuadroHonra $quadroHonra): Response
    {
        $quadroHonra->load([
            'itens' => fn ($query) => $query->orderBy('posicao'),
            'anoLectivo',
            'cursoTutelado.instituicaoCurso.curso',
            'geradoPor.instituicao',
        ]);

        $nomeCurso = $quadroHonra->cursoTutelado?->instituicaoCurso?->curso?->nome;
        $nomeAnoLectivo = $quadroHonra->anoLectivo?->nome ?? $quadroHonra->ano_lectivo_id;
        $escopo = $nomeCurso ? Str::slug($nomeCurso) : 'geral';
        $nomeFicheiro = 'quadro-honra-'.$escopo.'-'.Str::slug($nomeAnoLectivo).'.pdf';

        return Pdf::loadView('pdf.quadro-honra', [
            'quadroHonra' => $quadroHonra,
        ])
            ->setPaper('a4', 'landscape')
            ->download($nomeFicheiro);
    }
}
