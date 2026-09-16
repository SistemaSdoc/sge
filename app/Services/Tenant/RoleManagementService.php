<?php

namespace App\Services\Tenant;

use App\Models\Tenant\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleManagementService
{
    /** @return array<int, array{label: string, permissions: array<int, array{value: string, label: string}>}> */
    public function groupedPermissions(?User $actor = null): array
    {
        $groupOrder = [
            'Acessos',
            'Alunos',
            'Avisos',
            'Bancas de júri PAP',
            'Classes',
            'Classes de curso',
            'Coordenador',
            'Colégios',
            'Confirmação de matrícula',
            'Confirmações',
            'Cursos',
            'Cursos tutelados',
            'Disciplinas da turma',
            'Documentos',
            'Elementos do grupo PAP',
            'Grelha curricular',
            'Grupos PAP',
            'Inscrições',
            'Instituições',
            'Itens pagáveis',
            'Notas',
            'Pagamentos',
            'Pautas',
            'Professores',
            'Relatórios',
            'Regras de avaliação',
            'Solicitação de edição de pauta',
            'Turnos',
            'Turnos de classe',
            'Turmas',
            'Usuários',
        ];

        $tenantPermissions = Permission::query()
            ->where('guard_name', 'tenant')
            ->get()
            ->keyBy('name');

        $allowedPermissionNames = $actor?->isSubdirector()
            ? array_flip($this->permissions($actor))
            : null;

        $grouped = collect($this->permissionGroups())
            ->mapWithKeys(fn (array $permissions, string $group) => [
                $group => $permissions,
            ])
            ->map(fn (array $permissionNames, string $group) => [
                'label' => $group,
                'permissions' => collect($permissionNames)
                    ->filter(fn (string $name) => $allowedPermissionNames === null || isset($allowedPermissionNames[$name]))
                    ->map(fn (string $name) => $this->toPermissionOption(
                        $name,
                        $tenantPermissions->get($name)?->label,
                    ))
                    ->all(),
            ])
            ->filter(fn (array $group) => ! empty($group['permissions']))
            ->all();

        $ordered = [];

        foreach ($groupOrder as $group) {
            if (isset($grouped[$group])) {
                $ordered[] = $grouped[$group];
                unset($grouped[$group]);
            }
        }

        foreach ($grouped as $group => $data) {
            $ordered[] = $data;
        }

        return $ordered;
    }

    public function index(): LengthAwarePaginator
    {
        return Role::query()
            ->where('guard_name', 'tenant')
            ->where('name', '!=', 'SuperAdmin')
            ->withCount('users')
            ->with('permissions:id,name,label')
            ->orderBy('name')
            ->paginate(15);
    }

    /** @return array<int, array{value: string, label: string}> */
    public function permissions(?User $actor = null): array
    {
        $query = Permission::query()
            ->where('guard_name', 'tenant');

        if ($actor?->isSubdirector()) {
            $query->whereNotIn('name', $this->directorExclusivePermissionNames());
        }

        return $query
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $permission) => $this->toPermissionOption(
                $permission->name,
                $permission->label
            ))
            ->all();
    }

    /** @return array<int, string> */
    private function directorExclusivePermissionNames(): array
    {
        $directorPermissions = Role::query()
            ->where('name', 'Director')
            ->where('guard_name', 'tenant')
            ->first()?->permissions()->pluck('name')->all() ?? [];

        $subdirectorPermissions = Role::query()
            ->where('name', 'Subdirector')
            ->where('guard_name', 'tenant')
            ->first()?->permissions()->pluck('name')->all() ?? [];

        return array_values(array_diff($directorPermissions, $subdirectorPermissions));
    }

    /** @return array<string, array<int, string>> */
    private function permissionGroups(): array
    {
        return [
            'Instituições' => [
                'instituicoes.view',
                'instituicoes.update',
            ],
            'Confirmação de matrícula' => [
                'confirmacoes.matricula.viewAny',
                'confirmacoes.matricula.confirmar',
            ],
            'Regras de avaliação' => [
                'regra-avaliacao.viewAny',
                'regra-avaliacao.view',
                'regra-avaliacao.create',
                'regra-avaliacao.update',
                'regra-avaliacao.delete',
            ],
            'Solicitação de edição de pauta' => [
                'solicitacao-edicao-pauta.viewAny',
                'solicitacao-edicao-pauta.view',
            ],
            'Colégios' => [
                'colegios.viewAny',
            ],
            'Alunos' => [
                'alunos.viewAny',
                'alunos.view',
                'alunos.create',
                'alunos.update',
            ],
            'Classes de curso' => [
                'cursoclasse.viewAny',
                'cursoclasse.view',
                'cursoclasse.create',
                'cursoclasse.update',
                'cursoclasse.delete',
            ],
            'Turnos de classe' => [
                'cursoclasseturno.viewAny',
                'cursoclasseturno.view',
                'cursoclasseturno.create',
                'cursoclasseturno.update',
                'cursoclasseturno.delete',
            ],
            'Turnos' => [
                'turnos.viewAny',
                'turnos.view',
                'turnos.create',
                'turnos.update',
                'turnos.delete',
            ],
            'Turmas' => [
                'turmas.viewAny',
                'turmas.view',
                'turmas.create',
                'turmas.update',
                'turmas.delete',
            ],
            'Disciplinas da turma' => [
                'classeturnodisciplina.viewAny',
                'classeturnodisciplina.view',
                'classeturnodisciplina.create',
                'classeturnodisciplina.update',
                'classeturnodisciplina.delete',
            ],
            'Pautas' => [
                'pautas.viewAny',
                'pautas.view',
                'pautas.finalizar',
                'pautas.decidirSolicitacaoEdicao',
                'pautas.gerirPrazos',
                'pautas.solicitarEdicao',
            ],
            'Notas' => [
                'notas.create',
                'notas.update',
                'notas.viewAny',
                'notas.export',
            ],
            'Grelha curricular' => [
                'grelha.viewAny',
            ],
            'Professores' => [
                'professores.viewAny',
                'professores.view',
                'professores.create',
                'professores.update',
                'professores.delete',
            ],
            'Avisos' => [
                'avisos.viewAny',
                'avisos.view',
                'avisos.create',
                'avisos.update',
                'avisos.delete',
            ],
            'Inscrições' => [
                'inscricoes.viewAny',
                'inscricoes.view',
                'inscricoes.create',
                'inscricoes.update',
                'inscricoes.delete',
                'inscricoes.cancelar',
                'inscricoes.reativar',
            ],
            'Grupos PAP' => [
                'grupopap.viewAny',
                'grupopap.view',
                'grupopap.create',
                'grupopap.update',
                'grupopap.delete',
                'grupopap.definirData',
                'grupopap.definirTema',
                'grupopap.selecionarInstituicao',
                'grupopap.selecionarAnoLectivo',
                'grupopap.corrigirTema',
                'grupopap.aprovar',
                'grupopap.reprovar',
                'grupopap.solicitarMelhoria',
            ],
            'Elementos do grupo PAP' => [
                'elementogrupopap.viewAny',
                'elementogrupopap.view',
                'elementogrupopap.create',
                'elementogrupopap.update',
                'elementogrupopap.delete',
                'elementogrupopap.atualizarNota',
            ],
            'Bancas de júri PAP' => [
                'bancajuripap.viewAny',
                'bancajuripap.view',
                'bancajuripap.create',
                'bancajuripap.update',
                'bancajuripap.delete',
            ],
            'Cursos' => [
                'cursos.viewAny',
                'cursos.view',
                'cursos.create',
                'cursos.update',
                'cursos.delete',
            ],
            'Classes' => [
                'classes.viewAny',
                'classes.view',
                'classes.create',
                'classes.update',
                'classes.delete',
            ],
            'Cursos tutelados' => [
                'curso-tutelado.viewAny',
                'curso-tutelado.view',
                'curso-tutelado.create',
                'curso-tutelado.update',
                'curso-tutelado.delete',
            ],
            'Utilizadores' => [
                'usuarios.viewAny',
                'usuarios.view',
                'usuarios.create',
                'usuarios.update',
                'usuarios.delete',
            ],
            'Acessos' => [
                'acessos.viewAny',
                'acessos.create',
            ],
            'Relatórios' => [
                'relatorios.view',
            ],
            'Pagamentos' => [
                'pagamentos.viewAny',
                'pagamentos.view',
                'pagamentos.create',
                'pagamentos.update',
                'pagamentos.delete',
            ],
            'Itens pagáveis' => [
                'itemspagaveis.viewAny',
                'itemspagaveis.view',
                'itemspagaveis.create',
                'itemspagaveis.update',
                'itemspagaveis.delete',
            ],
            'Documentos' => [
                'documentos.viewAny',
                'documentos.view',
                'documentos.emitir',
                'documentos.exportar',
            ],
            'Coordenador' => [
                'coordenador.view-curso',
                'coordenador.update-curso',
                'coordenador.manage-professores',
                'coordenador.manage-turmas',
                'coordenador.view-pautas',
                'coordenador.update-pautas',
                'coordenador.create-notas',
                'coordenador.update-notas',
                'coordenador.view-relatorios',
            ],
            'Confirmações' => [
                'confirmacoes.matricula.viewAny',
                'confirmacoes.matricula.confirmar',
            ],
        ];
    }

    /** @return array{value: string, label: string} */
    private function toPermissionOption(string $name, ?string $label = null): array
    {
        return [
            'value' => $name,
            'label' => $label ?: $this->permissionLabel($name),
        ];
    }

    private function permissionLabel(string $name): string
    {
        return Str::of($name)
            ->replace(['-', '_', '.'], ' ')
            ->replaceMatches('/\s+/', ' ')
            ->explode(' ')
            ->map(fn (string $word) => ucfirst($word))
            ->join(' ');
    }
}
