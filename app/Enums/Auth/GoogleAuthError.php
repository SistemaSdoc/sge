<?php

namespace App\Enums\Auth;

/** Códigos públicos allowlisted para o redirect de erro cross-domain do OAuth Google. */
enum GoogleAuthError: string
{
    case ACCOUNT = 'account';
    case DENIED = 'denied';
    case STATE = 'state';
    case FAILED = 'failed';

    /** Devolve o texto local apresentado no Alert tenant; nunca usar texto de excepção nesta mensagem. */
    public function message(): string
    {
        return match ($this) {
            self::ACCOUNT => 'Não foi possível autenticar com esta conta Google. Confirme se o email está verificado e associado a um utilizador ativo desta instituição.',
            self::DENIED => 'A autenticação com Google foi cancelada. Pode tentar novamente.',
            self::STATE => 'A autenticação com Google expirou ou não pôde ser validada. Tente novamente.',
            self::FAILED => 'Não foi possível concluir a autenticação com Google. Tente novamente.',
        };
    }
}
