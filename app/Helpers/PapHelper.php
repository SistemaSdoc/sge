<?php

namespace App\Helpers;

use App\Models\Instituicao;
use App\Models\User;

class PapHelper
{
    /**
     * Retorna o nome do aprovador formatado para exibição.
     * Se o utilizador pertence à instituição tutora, mostra
     * o nome do grupo disciplinar em vez do nome pessoal.
     */
    public static function nomeAprovador(
        ?User $utilizador,
        Instituicao $instituicaoTutora,
        string $nomeCurso,
    ): string {
        if (!$utilizador) {
            return '—';
        }

        if ($utilizador->instituicao_id === $instituicaoTutora->id) {
            return "Grupo disciplinar do curso de {$nomeCurso} do {$instituicaoTutora->sigla}";
        }

        return $utilizador->nome;
    }
}