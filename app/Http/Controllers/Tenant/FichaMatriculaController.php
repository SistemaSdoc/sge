<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Aluno;
use App\Services\Tenant\Fichas\FichaMatriculaPdfService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FichaMatriculaController extends Controller
{
    public function __construct(
        private readonly FichaMatriculaPdfService $fichaPdf,
    ) {}

    public function pdf(Aluno $aluno): StreamedResponse
    {
        Gate::authorize('view', $aluno);

        $pdf = $this->fichaPdf->gerar($aluno);

        return response()->streamDownload(static function () use ($pdf): void {
            echo $pdf['conteudo'];
        }, $pdf['nome'], [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
