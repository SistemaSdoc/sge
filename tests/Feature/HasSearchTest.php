<?php

use App\Models\Tenant\Classe;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('search scope filters a table by its configured searchable fields', function (): void {
    Classe::create(['nome' => '10ª Classe', 'nivel_ensino' => 'Secundário']);
    Classe::create(['nome' => '1ª Classe', 'nivel_ensino' => 'Primário']);

    $classes = Classe::query()->search('Secundário')->pluck('nome')->all();

    expect($classes)->toBe(['10ª Classe']);
});
