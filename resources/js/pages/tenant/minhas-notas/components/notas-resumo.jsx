export function NotasResumo({ data = [], resultadoFinal, periodo = '1' }) {
  const disciplinasFinais = resultadoFinal?.disciplinas ?? [];
  const isTrimestral = ['1', '2', '3'].includes(String(periodo));
  const trimestre = isTrimestral ? Number(periodo) : 3;
  const notasTrimestrais = data
    .map((disciplina) => disciplina.trimestres?.[trimestre]?.media)
    .filter((media) => media !== null && media !== undefined);
  const disciplinasRecurso = disciplinasFinais.filter(
    (disciplina) => disciplina.situacao === 'recurso',
  );
  const disciplinasResumo = isTrimestral
    ? data.map((disciplina) => ({
        media: disciplina.trimestres?.[trimestre]?.media,
        faltas: disciplina.trimestres?.[trimestre]?.faltas,
      }))
    : periodo === 'final'
      ? disciplinasFinais.map((disciplina) => ({
          media: disciplina.mfd,
          faltas: disciplina.total_faltas,
        }))
      : disciplinasRecurso.map((disciplina) => ({
          media: disciplina.nota_recurso,
          faltas: disciplina.total_faltas,
          situacaoRecurso: disciplina.situacao_recurso,
        }));
  const medias = isTrimestral
    ? notasTrimestrais
    : disciplinasResumo
        .map((disciplina) => disciplina.media)
        .filter((media) => media !== null && media !== undefined);
  const stats = {
    total: disciplinasResumo.length,
    aprovados:
      isTrimestral || periodo === 'final'
        ? medias.filter((media) => media >= 10).length
        : disciplinasResumo.filter(
            (disciplina) => disciplina.situacaoRecurso === 'aprovado_recurso',
          ).length,
    reprovados:
      isTrimestral || periodo === 'final'
        ? medias.filter((media) => media < 10).length
        : disciplinasResumo.filter(
            (disciplina) => disciplina.situacaoRecurso === 'reprovado_recurso',
          ).length,
    mediaGeral:
      medias.length > 0
        ? Number(
            (
              medias.reduce((soma, media) => soma + media, 0) / medias.length
            ).toFixed(1),
          )
        : null,
    faltas: disciplinasResumo.reduce(
      (total, disciplina) => total + (disciplina.faltas ?? 0),
      0,
    ),
  };

  return (
    <div className="grid grid-cols-2 gap-3 lg:grid-cols-5">
      <div className="flex flex-col gap-1 border bg-card p-3">
        <span className="text-xs text-muted-foreground">Disciplinas</span>
        <span className="text-2xl font-bold">{stats.total}</span>
      </div>

      <div className="flex flex-col items-start gap-1 border bg-card p-3">
        <span className="text-xs text-muted-foreground">Aprovados</span>
        <span className="text-2xl font-bold">{stats.aprovados}</span>
      </div>

      <div className="flex flex-col items-start gap-1 border bg-card p-3">
        <span className="text-xs text-muted-foreground">Reprovados</span>
        <span className="text-2xl font-bold">{stats.reprovados}</span>
      </div>

      <div className="flex flex-col items-start gap-1 border bg-card p-3">
        <span className="text-xs text-muted-foreground">Faltas</span>
        <span className="text-2xl font-bold">{stats?.faltas}</span>
      </div>

      <div className="col-span-2 flex flex-col gap-1 border bg-card p-3 md:col-span-1">
        <span className="text-xs text-muted-foreground">Média Geral</span>
        <span className="text-2xl font-bold">{stats.mediaGeral ?? '—'}</span>
      </div>
    </div>
  );
}
