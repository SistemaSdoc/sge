<?php

namespace App\Http\Controllers\Central;

use App\Actions\Central\CalendarioAnual\UploadCalendarioAnual;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\CalendarioAnual\CalendarioAnualRequest;
use App\Models\Central\CalendarioAnual;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CalendarioAnualController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('central/calendarios-anuais/index', [
            'calendarios' => CalendarioAnual::query()
                ->orderByDesc('ano')
                ->paginate(10, ['id', 'ano', 'ficheiro_nome', 'ativo']),
        ]);
    }

    public function store(CalendarioAnualRequest $request, UploadCalendarioAnual $upload): RedirectResponse
    {
        $dados = $request->validated();

        $calendario = CalendarioAnual::create([
            'ano' => $dados['ano'],
            'ficheiro_path' => '',
            'ficheiro_nome' => '',
        ]);

        $upload->handle($calendario, $dados['ficheiro']);

        return redirect()->route('central.dashboard.calendarios-anuais.index')
            ->with('success', 'Calendário anual criado com sucesso.');
    }

    public function update(CalendarioAnualRequest $request, CalendarioAnual $calendarioAnual, UploadCalendarioAnual $upload): RedirectResponse
    {
        $dados = $request->validated();

        if (isset($dados['ficheiro'])) {
            $upload->handle($calendarioAnual, $dados['ficheiro']);
        }

        if (array_key_exists('ativo', $dados)) {
            $calendarioAnual->update(['ativo' => $dados['ativo']]);
        }

        return back()->with('success', 'Calendário anual actualizado com sucesso.');
    }

    public function destroy(CalendarioAnual $calendarioAnual): RedirectResponse
    {
        Storage::disk('public')->delete($calendarioAnual->ficheiro_path);
        $calendarioAnual->delete();

        return back()->with('success', 'Calendário anual eliminado com sucesso.');
    }

    public function download(CalendarioAnual $calendarioAnual): mixed
    {
        /** @var FilesystemAdapter $disco */
        $disco = Storage::disk('public');

        abort_unless($disco->exists($calendarioAnual->ficheiro_path), 404);

        return $disco->download(
            $calendarioAnual->ficheiro_path,
            $calendarioAnual->ficheiro_nome
        );
    }

    public function view(CalendarioAnual $calendarioAnual): mixed
    {
        /** @var FilesystemAdapter $disco */
        $disco = Storage::disk('public');

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
