<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\CalendarioAnual;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CalendarioAnualController extends Controller
{
    private function centralStorage(): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disco */
        $disco = Storage::build([
            'driver' => 'local',
            'root' => base_path('storage/app/public'),
            'throw' => false,
        ]);

        return $disco;
    }

    public function index(): Response
    {
        return Inertia::render('tenant/calendarios-anuais/index', [
            'calendarios' => CalendarioAnual::query()
                ->where('ativo', true)
                ->orderByDesc('ano')
                ->paginate(10, ['id', 'ano', 'ficheiro_nome', 'ativo']),
        ]);
    }

    public function download(CalendarioAnual $calendarioAnual): mixed
    {
        $disco = $this->centralStorage();

        abort_unless($calendarioAnual->ativo, 404);
        abort_unless($disco->exists($calendarioAnual->ficheiro_path), 404);

        return $disco->download(
            $calendarioAnual->ficheiro_path,
            $calendarioAnual->ficheiro_nome
        );
    }

    public function view(CalendarioAnual $calendarioAnual): mixed
    {
        $disco = $this->centralStorage();

        abort_unless($calendarioAnual->ativo, 404);
        abort_unless($disco->exists($calendarioAnual->ficheiro_path), 404);

        if (strtolower(pathinfo($calendarioAnual->ficheiro_nome, PATHINFO_EXTENSION)) !== 'pdf') {
            return $disco->download(
                $calendarioAnual->ficheiro_path,
                $calendarioAnual->ficheiro_nome
            );
        }

        return response()->file($disco->path($calendarioAnual->ficheiro_path), [
            'Content-Disposition' => 'inline; filename="'.basename($calendarioAnual->ficheiro_nome).'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
