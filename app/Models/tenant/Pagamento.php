<?php

namespace App\Models\tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Pagamento extends Model
{
    use HasUuid, SoftDeletes;

    protected $table = 'pagamentos';

    protected $fillable = [
        'aluno_id', 'instituicao_id', 'registado_por',
        'data_pagamento', 'valor_total', 'metodo', 'referencia', 'observacoes',
        'recibo_path', 'numero_recibo',
    ];

    protected $casts = [
        'data_pagamento' => 'date',
        'valor_total' => 'decimal:2',
    ];

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Aluno::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(PagamentoItem::class);
    }

    public function registadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registado_por');
    }

    public function gerarRecibo(bool $forcar = false): void
{
    if ($this->recibo_path && !$forcar) {
        return;
    }

    DB::transaction(function () {
        $ano = $this->data_pagamento->year;

        $ultimo = self::where('numero_recibo', 'like', "REC-{$ano}-%")
            ->where('instituicao_id', $this->instituicao_id)
            ->lockForUpdate()
            ->orderByDesc('numero_recibo')
            ->first();

        $proximoNumero = $ultimo
            ? ((int) substr($ultimo->numero_recibo, -6)) + 1
            : 1;

        $numeroRecibo = sprintf('REC-%d-%06d', $ano, $proximoNumero);

        $this->load('aluno.user', 'itens.itemPagavel', 'registadoPor','aluno.turmaActual.cursoClasseTurno.cursoClasse.classe',
    'aluno.turmaActual.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
    'aluno.turmaActual.cursoClasseTurno.turno');

        $pdf = Pdf::loadView('pdf.recibo', [
            'pagamento' => $this,
            'instituicao' => $this->aluno->instituicao ?? Instituicao::find($this->instituicao_id),
            'numeroRecibo' => $numeroRecibo,
        ]);

        $caminho = "recibos/{$this->instituicao_id}/{$numeroRecibo}.pdf";

        Storage::disk('local')->put($caminho, $pdf->output());

        $this->updateQuietly([
            'numero_recibo' => $numeroRecibo,
            'recibo_path' => $caminho,
        ]);
    });
}
}
