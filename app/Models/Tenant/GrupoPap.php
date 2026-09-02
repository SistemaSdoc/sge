<?php

namespace App\Models\Tenant;

use App\Traits\HasUuid;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'professor_tutor_id',
    'professor_tutor_externo_id',
    'professor_tutor_externo_tenant_id',
    'turma_id',
    'nome_grupo',
    'tema_grupo',
    'status_aprovacao',
    'aprovado_por_id',
    'aprovado_por_externo_id',
    'aprovado_por_externo_tenant_id',
    'aprovado_por_nome',
    'data_aprovacao',
    'comentario_aprovacao',
    'estudo_caso',
    'objectivos',
    'problema',
    'trabalho_grupo',
    'status',
    'nota_final',
    'data_defesa',
    'local_defesa',
    'encerrado_em',
])]

class GrupoPap extends Model
{
    use HasUuid;

    protected $table = 'grupo_pap';

    protected $primaryKey = 'id';

    const APROVACAO_RASCUNHO = 'rascunho';

    const APROVACAO_SUBMETIDO = 'submetido';

    const APROVACAO_PENDENTE = 'pendente';

    const APROVACAO_APROVADO = 'aprovado';

    const APROVACAO_REPROVADO = 'reprovado';

    const APROVACAO_MELHORIA_TUTOR = 'melhoria-solicitada-tutor';

    const APROVACAO_MELHORIA_COORDENACAO = 'melhoria-solicitada-coordenacao';

    protected function casts(): array
    {
        return [
            'data_defesa' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'encerrado_em' => 'datetime',
        ];
    }

    public function tutelaEncerrada(): bool
    {
        return $this->encerrado_em !== null || $this->status_aprovacao === 'arquivado';
    }

    public function assertTutelaActiva(): void
    {
        if ($this->tutelaEncerrada()) {
            throw new AuthorizationException('Este grupo PAP pertence a uma tutela encerrada.');
        }
    }

    public function professor()
    {
        return $this->belongsTo(Professor::class, 'professor_tutor_id');
    }

    public function trabalhoPap()
    {
        return $this->hasOne(TrabalhoPap::class, 'grupo_pap_id');
    }

    public function jurados()
    {
        return $this->hasMany(BancaJuriPap::class);
    }

    public function turma()
    {
        return $this->belongsTo(Turma::class, 'turma_id');
    }

    public function elementos()
    {
        return $this->hasMany(ElementoGrupoPap::class, 'grupo_pap_id');
    }

    public function alunos()
    {
        return $this->belongsToMany(Aluno::class, 'elementos_grupo_pap', 'grupo_pap_id', 'aluno_id');
    }

    public function instituicao()
    {
        return $this->turma
            ?->cursoClasseTurno
            ?->cursoClasse
            ?->cursoTutelado
            ?->instituicaoCurso
            ?->instituicao;
    }

    // para pegar a instituicao tutora do curso tutelado  (tutela externa)
    public function instituicaoTutora(): ?Instituicao
    {
        return $this->turma
            ?->cursoClasseTurno
            ?->cursoClasse
            ?->cursoTutelado
            ?->instituicaoTutora;
    }

    public function historicoAprovacao()
    {
        return $this->hasMany(
            HistoricoAprovacaoPap::class,
            'grupo_pap_id'
        )->latest();
    }

    public function aprovadoPor()
    {
        return $this->belongsTo(User::class, 'aprovado_por_id');
    }

    // Scopes
    public function scopePendentes($query)
    {
        return $query->where('status_aprovacao', 'pendente');
    }

    public function scopeAprovados($query)
    {
        return $query->where('status_aprovacao', 'aprovado');
    }

    public function scopeReprovados($query)
    {
        return $query->where('status_aprovacao', 'reprovado');
    }

    public function scopeMelhoriaSolicitada($query)
    {
        return $query->whereIn('status_aprovacao', [
            'melhoria-solicitada-tutor',
            'melhoria-solicitada-coordenacao',
        ]);
    }

    /*public function podeSerAprovado(): bool
    {
        // Pode aprovar se ainda não foi finalizado (aprovado ou reprovado)
        return in_array($this->status_aprovacao, ['pendente', 'melhoria-solicitada']);
    }*/

    public function podeSerReenviado(): bool
    {
        return in_array($this->status_aprovacao, [
            'reprovado',
            'melhoria-solicitada',
            'melhoria-solicitada-tutor',
            'melhoria-solicitada-coordenacao',
        ]);
    }

    public function podeSerEditado(): bool
    {
        return in_array($this->status_aprovacao, [
            'reprovado',
            'melhoria-solicitada',
            'melhoria-solicitada-tutor',
            'melhoria-solicitada-coordenacao',
        ]);
    }

    public function podeDefinirTema(): bool
    {
        return in_array($this->status_aprovacao, [
            'rascunho',
            'melhoria-solicitada',
            'melhoria-solicitada-tutor',
            'melhoria-solicitada-coordenacao',
        ]);
    }

    public function podeSermitidoAoTutor(): bool
    {
        return ! $this->tutelaEncerrada()
            && $this->status_aprovacao === 'rascunho'
            && ! is_null($this->tema_grupo);
    }

    public function podeSerAprovadoPeloTutor(): bool
    {
        return ! $this->tutelaEncerrada() && $this->status_aprovacao === 'submetido';
    }

    public function podeSerAprovado(): bool  // pela coordenação
    {
        return ! $this->tutelaEncerrada() && $this->status_aprovacao === 'pendente';
    }
}
