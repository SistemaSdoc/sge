<?php

use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use App\Notifications\Professor\ProfessorCriadoNotification;
use Illuminate\Support\Facades\Storage;

it('includes the institution logo as base64 in the professor created email', function () {
    Storage::fake('public');

    $logoPath = 'logos/instituicao.png';
    Storage::disk('public')->put($logoPath, 'fake-image-data');

    $instituicao = new Instituicao([
        'nome' => 'Instituição Teste',
        'tipo' => 'instituto',
        'logo' => $logoPath,
    ]);

    $user = new User([
        'nome' => 'Professor Teste',
        'email' => 'professor@teste.com',
    ]);
    $user->setRelation('instituicao', $instituicao);

    $mail = (new ProfessorCriadoNotification($user, 'secret123'))->toMail($user);

    expect($mail->viewData['logoBase64'])
        ->toStartWith('data:image/png;base64,')
        ->not->toBeNull();
});
