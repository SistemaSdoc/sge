<?php

namespace App\Enums\Auth;

enum FacebookAuthError: string
{
    case ACCOUNT = 'account';
    case DENIED = 'denied';
    case STATE = 'state';
    case FAILED = 'failed';

    public function message(): string
    {
        return match ($this) {
            self::ACCOUNT => 'Não foi possível autenticar com esta conta Facebook. Confirme se ela está associada a um utilizador desta instituição.',
            self::DENIED => 'A autenticação com Facebook foi cancelada. Pode tentar novamente.',
            self::STATE => 'A autenticação com Facebook expirou ou não pôde ser validada. Tente novamente.',
            self::FAILED => 'Não foi possível concluir a autenticação com Facebook. Tente novamente.',
        };
    }
}
