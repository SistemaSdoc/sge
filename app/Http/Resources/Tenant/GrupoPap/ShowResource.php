<?php

namespace App\Http\Resources\Tenant\GrupoPap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ShowResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        // Resolver documentos PAP uma vez só (herda do tutor se tipo_tutela = externa)
        $cursoTutelado = $this->turma
            ?->cursoClasseTurno
            ?->cursoClasse
            ?->cursoTutelado;

        $docs = $cursoTutelado?->resolverDocumentosPap() ?? [
            'criterios_pap_path'          => null,
            'manual_pt_path'              => null,
            'estrutura_trabalho_pap_path' => null,
        ];

        return [
            'id'                    => $this->id,
            'nome_grupo'            => $this->nome_grupo,
            'tema_grupo'            => $this->tema_grupo,
            'estudo_caso'           => $this->estudo_caso,
            'status'                => $this->status,
            'objectivos'            => $this->objectivos,
            'problema'              => $this->problema,
            'status_aprovacao'      => $this->status_aprovacao,
            'comentario_aprovacao'  => $this->comentario_aprovacao,
            'nota_final'            => $this->nota_final,
            'data_defesa'           => $this->data_defesa?->toIso8601String(),
            'local_defesa'          => $this->local_defesa,
            'professor' => $this->professor ? [
                'id'    => $this->professor->id,
                'nome'  => $this->professor->user->nome,
                'email' => $this->professor->user->email,
            ] : null,
            'turma' => $this->turma ? [
                'nome' => $this->turma->nome,
            ] : null,

            // ← substituição dos 3 closures
            'criterios_pap_url' => $docs['criterios_pap_path']
                ? Storage::url($docs['criterios_pap_path'])
                : null,
            'manual_pt_url' => $docs['manual_pt_path']
                ? Storage::url($docs['manual_pt_path'])
                : null,
            'estrutura_trabalho_pap_url' => $docs['estrutura_trabalho_pap_path']
                ? Storage::url($docs['estrutura_trabalho_pap_path'])
                : null,

            'aprovado_por' => $this->aprovadoPor ? [
                'id'   => $this->aprovadoPor->id,
                'nome' => $this->aprovadoPor->nome ?? null,
            ] : null,
        ];
    }
}