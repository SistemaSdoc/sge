<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class CursoTuteladoSecretarioController extends Controller
{
    public function store(Request $request, Instituicao $instituicao, CursoTutelado $cursoTutelado): RedirectResponse
    {
        $this->assertCourseBelongsToInstitution($instituicao, $cursoTutelado);
        $this->authorizeSecretaryManagement($request->user('tenant'), $cursoTutelado);

        $validated = $request->validate([
            'user_id' => [
                'required',
                'uuid',
                Rule::exists('users', 'id')->where('instituicao_id', $instituicao->id),
            ],
        ]);

        $secretario = User::query()->findOrFail($validated['user_id']);

        if (! $secretario->hasRole('Secretario do Curso')) {
            throw ValidationException::withMessages([
                'user_id' => 'O utilizador seleccionado precisa da função Secretário do Curso antes de ser associado.',
            ]);
        }

        if ($secretario->hasAnyRole(['Secretaria', 'Director', 'Subdirector', 'Coordenador', 'SuperAdmin'])) {
            throw ValidationException::withMessages([
                'user_id' => 'Este utilizador já tem permissões institucionais e não pode receber acesso limitado a um curso.',
            ]);
        }

        DB::transaction(function () use ($cursoTutelado, $secretario): void {
            $cursoTutelado->secretarios()->syncWithoutDetaching([$secretario->getKey()]);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back();
    }

    public function destroy(Instituicao $instituicao, CursoTutelado $cursoTutelado, string $secretario): RedirectResponse
    {
        $this->assertCourseBelongsToInstitution($instituicao, $cursoTutelado);
        $this->authorizeSecretaryManagement(request()->user('tenant'), $cursoTutelado);

        $user = $cursoTutelado->secretarios()->whereKey($secretario)->firstOrFail();

        DB::transaction(function () use ($cursoTutelado, $secretario, $user): void {
            $cursoTutelado->secretarios()->detach($secretario);

            if (! $user->cursosSecretariados()->exists()) {
                $user->removeRole('Secretario do Curso');
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back();
    }

    private function assertCourseBelongsToInstitution(Instituicao $instituicao, CursoTutelado $cursoTutelado): void
    {
        abort_unless(
            (string) $cursoTutelado->instituicaoCurso?->instituicao_id === (string) $instituicao->getKey(),
            404,
        );
    }

    private function authorizeSecretaryManagement(User $user, CursoTutelado $cursoTutelado): void
    {
        abort_unless(
            $user->hasRole('Coordenador')
                && $user->hasPermissionTo('curso.secretarios.manage')
                && $user->professor?->cursosTutelados()
                    ->whereKey($cursoTutelado->getKey())
                    ->wherePivot('coordenador', true)
                    ->exists(),
            403,
        );
    }
}
