<?php

namespace App\Http\Controllers\Tenant;

use App\Models\Tenant\GrupoPap;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class FolhaAprovacaoController extends Controller
{
    public function index()
    {
        $grupos = GrupoPap::with(['professor', 'turma'])->get();

        return view('documentos.folhaAprovacao.index', compact('grupos'));
    }

    public function folhaAprovacao($id)
    {
        $grupo = GrupoPap::with([
            'professor.user',
            'turma.cursoClasseTurno.cursoClasse.classe',
        ])->findOrFail($id);

        // Usuário logado
        $user = Auth::user();

        $instituicao = $user->instituicao->nome ?? 'Instituição não definida';

        $porExtenso = $this->porExtenso($grupo->nota_final);

        $pdf = Pdf::loadView('documentos.folhaAprovacao', compact(
            'grupo',
            'porExtenso',
            'instituicao' // 👈 passar para a view
        ))->setPaper('a4', 'portrait');

        return $pdf->download('Folha_Aprovacao_'.$grupo->nome_grupo.'.pdf');

        // Para pré-visualizar no browser em vez de baixar:
        // return $pdf->stream('folha-aprovacao.pdf');
    }

    private function porExtenso($nota): string
    {
        $mapa = [
            0 => 'ZERO',
            1 => 'UM',
            2 => 'DOIS',
            3 => 'TRÊS',
            4 => 'QUATRO',
            5 => 'CINCO',
            6 => 'SEIS',
            7 => 'SETE',
            8 => 'OITO',
            9 => 'NOVE',
            10 => 'DEZ',
            11 => 'ONZE',
            12 => 'DOZE',
            13 => 'TREZE',
            14 => 'CATORZE',
            15 => 'QUINZE',
            16 => 'DEZASSEIS',
            17 => 'DEZASSETE',
            18 => 'DEZOITO',
            19 => 'DEZANOVE',
            20 => 'VINTE',
        ];

        return $mapa[(int) $nota] ?? '';
    }
}
