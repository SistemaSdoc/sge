// resources/js/utils/format-date.ts

/**
 * Formata uma data ISO ("2026-10-16") em PT-BR humano.
 * Ex.: "sexta-feira, 16 de outubro"
 */
export function formatarDataHumana(
  dataISO: string | null | undefined,
): string {
  if (!dataISO) return '—';

  // Evita o problema de fuso: fixa meio-dia local
  const d = new Date(dataISO + 'T12:00:00');

  if (Number.isNaN(d.getTime())) return '—';

  return d.toLocaleDateString('pt-BR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  });
}