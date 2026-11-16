<?php

use App\Http\Resources\Tenant\Pap\Grupo\GrupoPapDetalhePageResource;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

it('returns the complete group detail page props contract', function (): void {
    $user = new class
    {
        public string $instituicao_id = 'instituicao-1';

        public function getKey(): int
        {
            return 7;
        }

        public function hasRole(string $role): bool
        {
            return false;
        }

        public function can(string $ability, mixed $arguments = []): bool
        {
            return false;
        }
    };

    $grupoPap = (new GrupoPap)->forceFill([
        'id' => 12,
        'nome_grupo' => 'Grupo PAP',
        'tema_grupo' => 'Tema de teste',
        'status_aprovacao' => 'rascunho',
    ]);
    $grupoPap->setRelation('turma', null);
    $grupoPap->setRelation('professor', null);
    $grupoPap->setRelation('aprovadoPor', null);

    $request = Request::create('/');
    $props = (new GrupoPapDetalhePageResource([
        'instituicao' => (new Instituicao)->forceFill(['id' => 'instituicao-1', 'nome' => 'Instituição']),
        'cursoTutelado' => (new CursoTutelado)->forceFill(['id' => 'curso-1']),
        'cursoClasse' => (new CursoClasse)->forceFill(['id' => 'classe-1']),
        'cursoClasseTurno' => (new CursoClasseTurno)->forceFill(['id' => 'turno-1']),
        'turma' => (new Turma)->forceFill(['id' => 'turma-1', 'nome' => 'Turma 1']),
        'anosLectivos' => collect(),
        'grupoPap' => $grupoPap,
        'user' => $user,
        'instituicaoTutora' => null,
        'historico' => collect(),
        'trabalho' => null,
        'detalhes' => [
            'banca' => new LengthAwarePaginator(collect(), 0, 10, 1, ['path' => '/']),
            'elementos' => new LengthAwarePaginator(collect(), 0, 10, 1, ['path' => '/']),
        ],
        'canUpdateElementNotes' => false,
    ]))->resolve($request);

    expect(array_keys($props))->toBe([
        'instituicao',
        'cursoTutelado',
        'cursoClasse',
        'cursoClasseTurno',
        'turma',
        'anoLectivoId',
        'anosLectivos',
        'grupoPap',
        'currentUserId',
        'historico',
        'trabalho',
        'banca',
        'elementos',
        'can',
    ])->and($props['grupoPap'])->toMatchArray([
        'id' => 12,
        'nome_grupo' => 'Grupo PAP',
        'tema_grupo' => 'Tema de teste',
    ])->and($props['banca']->resource)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($props['banca']->resource->total())->toBe(0)
        ->and($props['elementos']->resource)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($props['elementos']->resource->total())->toBe(0)
        ->and(array_keys($props['can']))->toBe([
            'update',
            'definirData',
            'delete',
            'corrigirTema',
            'aprovar',
            'reprovar',
            'solicitarMelhoria',
            'definirTema',
            'aprovarComoTutor',
            'submeter',
            'aprovarTrabalhoComoTutor',
            'solicitarCorrecaoComoTutor',
            'aprovarComoCoordenacao',
            'solicitarCorrecaoComoCoordenacao',
            'downloadVersao',
            'elementos',
            'verBanca',
            'banca',
        ]);
});
