<?php

namespace App\Http\Controllers\Central;

use App\Actions\Central\CalendarioAnual\CreateCalendarioAnual;
use App\Actions\Central\CalendarioAnual\DeleteCalendarioAnual;
use App\Actions\Central\CalendarioAnual\UpdateCalendarioAnual;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\CalendarioAnual\StoreCalendarioAnualRequest;
use App\Http\Requests\Central\CalendarioAnual\UpdateCalendarioAnualRequest;
use App\Models\Central\CalendarioAnual;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CalendarioAnualController extends Controller
{
    public function __construct(
        private readonly CreateCalendarioAnual $createCalendarioAnual,
        private readonly UpdateCalendarioAnual $updateCalendarioAnual,
        private readonly DeleteCalendarioAnual $deleteCalendarioAnual,
    ) {
        $this->authorizeResource(CalendarioAnual::class, 'calendarioAnual');
    }

    public function index(): Response
    {
        return Inertia::render('central/calendarios-anuais/index', [
            'calendarios' => CalendarioAnual::query()
                ->orderByDesc('ano')
                ->paginate(10, ['id', 'ano', 'ficheiro_nome', 'ativo']),
        ]);
    }

    public function store(StoreCalendarioAnualRequest $request): RedirectResponse
    {
        $this->createCalendarioAnual->handle($request->validated());

        return redirect()->route('central.dashboard.calendarios-anuais.index')
            ->with('success', 'Calendário anual criado com sucesso.');
    }

    public function update(UpdateCalendarioAnualRequest $request, CalendarioAnual $calendarioAnual): RedirectResponse
    {
        $this->updateCalendarioAnual->handle($calendarioAnual, $request->validated());

        return back()->with('success', 'Calendário anual actualizado com sucesso.');
    }

    public function destroy(CalendarioAnual $calendarioAnual): RedirectResponse
    {
        $this->deleteCalendarioAnual->handle($calendarioAnual);

        return back()->with('success', 'Calendário anual eliminado com sucesso.');
    }

    public function download(CalendarioAnual $calendarioAnual): mixed
    {
        $this->authorize('view', $calendarioAnual);

        /** @var FilesystemAdapter $disco */
        $disco = Storage::disk(config('filesystems.default'));

        abort_unless($disco->exists($calendarioAnual->ficheiro_path), 404);

        return $disco->download(
            $calendarioAnual->ficheiro_path,
            $calendarioAnual->ficheiro_nome
        );
    }

    public function view(CalendarioAnual $calendarioAnual): mixed
    {
        $this->authorize('view', $calendarioAnual);

        /** @var FilesystemAdapter $disco */
        $disco = Storage::disk(config('filesystems.default'));

        abort_unless($disco->exists($calendarioAnual->ficheiro_path), 404);

        if (strtolower(pathinfo($calendarioAnual->ficheiro_nome, PATHINFO_EXTENSION)) !== 'pdf') {
            return $disco->download(
                $calendarioAnual->ficheiro_path,
                $calendarioAnual->ficheiro_nome
            );
        }

        return response($disco->get($calendarioAnual->ficheiro_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($calendarioAnual->ficheiro_nome).'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
