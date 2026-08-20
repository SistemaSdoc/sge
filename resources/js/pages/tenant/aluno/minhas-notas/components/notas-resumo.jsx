/**
 * Componente de resumo com estatísticas por trimestre
 *
 * @component
 * @param {Array} data - Array de notas com { id, disciplina, trim1, trim2, trim3, media, falta, status }
 * @param {number} trimestre - Trimestre selecionado (1, 2 ou 3)
 * @returns {JSX.Element}
 */
export function NotasResumo({ data = [], trimestre = 1 }) {
  if (!data || data.length === 0) return null;

  /**
   * Calcula estatísticas do trimestre selecionado
   */
  const stats = {
    total: data.length,
    aprovados: data.filter((d) => {
      const media = d.trimestres[trimestre].media;
      return media >= 10;
    }).length,
    reprovados: data.filter((d) => {
      const media = d.trimestres[trimestre].media;
      return media < 10;
    }).length,
    mediaGeral: parseFloat(
      (
        data.reduce((sum, d) => sum + d.trimestres[trimestre].media, 0) /
        data.length
      ).toFixed(1),
    ),
    faltas: data.reduce((sum, d) => sum + d.trimestres[trimestre].faltas, 0),
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

      <div className="flex flex-col gap-1 border bg-card p-3">
        <span className="text-xs text-muted-foreground">Média Geral</span>
        <span className="text-2xl font-bold">{stats.mediaGeral}</span>
      </div>
    </div>
  );
}
