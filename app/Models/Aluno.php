<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'user_id',
    'inscricao_id',
    'instituicao_id', // ADICIONADO
    'matricula',
    'numero_processo',  // ADICIONADO
    'situacao',
])]

class Aluno extends Model
{
    use HasUuid;

    protected $table = 'alunos';

    protected $primaryKey = 'id';

    // ============================================
    // GERAÇÃO AUTOMÁTICA DO NÚMERO DE PROCESSO
    // ============================================

    /**
     * Ao criar um aluno:
     * 1. Descobre e preenche a instituição do aluno (se ainda não estiver definida).
     * 2. Gera o número de processo, único dentro dessa instituição, caso não tenha
     *    sido definido manualmente.
     * Formato: SEQUÊNCIA de 5 dígitos (ex: 00001) — sem ano, sem reinício anual.
     */
    protected static function booted(): void
    {
        static::creating(function (Aluno $aluno) {
            // ALTERADO: antes não existia este passo. Agora preenche instituicao_id
            // automaticamente a partir da inscrição, antes de gerar o número.
            if (blank($aluno->instituicao_id)) {
                $aluno->instituicao_id = static::descobrirInstituicaoId($aluno);
            }

            if (blank($aluno->numero_processo)) {
                // ALTERADO: gerarNumeroProcesso() agora recebe só o instituicao_id
                // (string), não o objeto $aluno inteiro — já não precisa de repetir
                // a cadeia de joins lá dentro.
                $aluno->numero_processo = static::gerarNumeroProcesso($aluno->instituicao_id);
            }
        });
    }

    /**
     * NNOVO MÉTODO: descobre a instituição do aluno percorrendo a cadeia de
     * relações a partir da inscrição:
     * inscricao -> curso_classe_turno -> curso_classe -> curso_tutelado
     * -> instituicao_curso -> instituicao_id
     * Isto só corre uma vez, no momento da criação, e o resultado fica guardado
     * na coluna alunos.instituicao_id — os métodos seguintes já não precisam
     * de repetir esta cadeia de joins.
     */
    private static function descobrirInstituicaoId(Aluno $aluno): ?string
    {
        return DB::table('inscricoes')
            ->join('curso_classe_turno', 'curso_classe_turno.id', '=', 'inscricoes.curso_classe_turno_id')
            ->join('curso_classe', 'curso_classe.id', '=', 'curso_classe_turno.curso_classe_id')
            ->join('curso_tutelado', 'curso_tutelado.id', '=', 'curso_classe.curso_tutelado_id')
            ->join('instituicao_curso', 'instituicao_curso.id', '=', 'curso_tutelado.instituicao_curso_id')
            ->where('inscricoes.id', $aluno->inscricao_id)
            ->value('instituicao_curso.instituicao_id');
    }

    /**
     * Gera o número de processo sequencial, único por instituição (colégio
     * ou instituto), sem reinício anual — cada instituição tem a sua própria
     * sequência a começar em 00001.
     *
     * ALTERADO: a query já não precisa dos 5 joins (curso_classe_turno,
     * curso_classe, curso_tutelado, instituicao_curso, instituicoes), porque
     * agora filtra diretamente pela coluna alunos.instituicao_id.
     *
     * Usa MAX+1 (não count) para não reaproveitar números de registos apagados,
     * e lockForUpdate + transação para evitar duplicados quando dois alunos
     * são criados em simultâneo na mesma instituição.
     */
    private static function gerarNumeroProcesso(?string $instituicaoId): string
    {
        return DB::transaction(function () use ($instituicaoId) {
            $ultimo = static::where('instituicao_id', $instituicaoId)
                ->whereNotNull('numero_processo')
                ->lockForUpdate()
                ->orderByRaw('CAST(numero_processo AS UNSIGNED) DESC')
                ->value('numero_processo');

            $proximaSequencia = $ultimo ? ((int) $ultimo + 1) : 1;

            // ALTERADO: %05d (5 dígitos, ex: 00001) em vez de "%d/%04d" (ano/sequência).
            return sprintf('%05d', $proximaSequencia);
        });
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeActivos($query)
    {
        return $query->where('situacao', 'activo');
    }

    public function scopeFinalistas($query)
    {
        return $query->where('situacao', 'finalista');
    }

    public function scopeConcluidos($query)
    {
        return $query->where('situacao', 'concluido');
    }

    public function scopeDoAnoLectivo($query, $anoLectivoId)
    {
        return $query->where(function ($q) use ($anoLectivoId) {
            $q->whereHas('turmas', function ($q2) use ($anoLectivoId) {
                $q2->where('turmas.ano_lectivo_id', $anoLectivoId);
            })
                ->orWhere(function ($q2) use ($anoLectivoId) {
                    $q2->whereDoesntHave('turmas')
                        ->whereHas('inscricao', function ($q3) use ($anoLectivoId) {
                            $q3->where('ano_lectivo_id', $anoLectivoId);
                        });
                });
        });
    }

    public function scopeDoAnoLectivoActivo($query)
    {
        return $query->where(function ($q) {
            $q->whereHas('turmas', function ($q2) {
                $q2->whereHas('anoLectivo', fn ($q3) => $q3->ativo());
            })
                ->orWhere(function ($q2) {
                    $q2->whereDoesntHave('turmas')
                        ->whereHas('inscricao.anoLectivo', fn ($q3) => $q3->ativo());
                });
        });
    }

    // ============================================
    // RELACIONAMENTOS
    // ============================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inscricao()
    {
        return $this->belongsTo(Inscricao::class);
    }

    // NOVO RELACIONAMENTO: útil agora que instituicao_id existe diretamente
    // na tabela alunos — permite fazer $aluno->instituicao->nome, por exemplo,
    // sem ter de percorrer a cadeia de joins.
    public function instituicao()
    {
        return $this->belongsTo(Instituicao::class);
    }

    public function turmas()
    {
        return $this->belongsToMany(Turma::class, 'turma_aluno', 'aluno_id', 'turma_id')
            ->using(TurmaAluno::class)
            ->withTimestamps();
    }

    public function turmaActual()
    {
        return $this->belongsToMany(Turma::class, 'turma_aluno', 'aluno_id', 'turma_id')
            ->withPivot('activo', 'situacao')
            ->wherePivotIn('situacao', ['activo', 'pap_concluido']);
    }

    public function turmaAlunoActual(): ?TurmaAluno
    {
        $turma = $this->turmaActual()->first();

        if (! $turma) {
            return null;
        }

        return TurmaAluno::where('aluno_id', $this->id)
            ->where('turma_id', $turma->id)
            ->where('activo', true)
            ->first();
    }

    public function historicoTurmas()
    {
        return $this->turmas()
            ->withPivot('ano_lectivo', 'activo', 'situacao')
            ->orderByPivot('ano_lectivo', 'asc');
    }

    public function anoLectivoAtual(): ?AnoLectivo
    {
        $turma = $this->turmaActual()->first();

        return $turma ? AnoLectivo::find($turma->pivot->ano_lectivo_id) : null;
    }

    // ============================================
    // RELACIONAMENTO COM PROPINAS
    // ============================================

    public function propinas(): HasMany
    {
        return $this->hasMany(Propina::class);
    }

    // ============================================
    // MÉTODOS DE VERIFICAÇÃO DE DÉBITOS
    // ============================================

    /**
     * Verifica se o aluno tem débitos pendentes (propinas em atraso)
     *
     * @return bool True se tiver débitos, False se estiver em dia
     */
    public function temDebitosPendentes(): bool
    {
        return $this->propinas()
            ->where('estado', 'atrasado')
            ->exists();
    }

    /**
     * Verifica se o aluno está em dia com as propinas
     *
     * @return bool True se estiver em dia, False se tiver débitos
     */
    public function estaEmDia(): bool
    {
        return ! $this->temDebitosPendentes();
    }

    public function grupoPap()
    {
        return $this->hasOneThrough(
            GrupoPap::class,
            ElementoGrupoPap::class,
            'aluno_id',
            'id',
            'id',
            'grupo_pap_id'
        );
    }

    public function confirmacoesMatricula()
    {
        return $this->hasMany(ConfirmacaoMatricula::class);
    }
}
