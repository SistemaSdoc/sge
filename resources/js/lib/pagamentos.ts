export type Aluno = {
  id: string;
  nome: string;
};

export type Frequencia = 'mensal' | 'anual' | 'unico';

export type ItemPagavel = {
  id: string;
  nome: string;
  descricao?: string;
  valor: number;
  frequencia: Frequencia;
  curso_classe_id?: string | null;
};

export type PaymentStatus = 'pendente' | 'parcial' | 'pago';

export type SaldoInfo = {
  pago: number;
  esperado: number;
  saldo: number;
  status: PaymentStatus;
};

/**
 * Saldo por item/período para o aluno actualmente seleccionado.
 * O nível de studentId foi removido — o backend já filtra por aluno via Inertia::lazy.
 *
 * Chave externa: item_pagavel_id (UUID).
 * Chave interna: mes (1-12, ou "0" para itens anuais/únicos) — vem como string
 * porque o backend serializa um array associativo PHP para objecto JSON.
 *
 * Ex: { [itemPagavelId]: { "1": { pago: 6000, esperado: 12000, saldo: 6000, status: 'parcial' }, ... } }
 *
 * Ausência de uma entrada para um mes/item = nunca houve nenhuma parcela (pendente).
 */
export type PaidRecord = Record<string, Record<string, SaldoInfo>>;

export type CartEntry = {
  item_pagavel_id: string;
  ano: number;
  meses: number[]; // 1-12. Vazio/[0] para itens anuais/únicos
  valor: number;
};

export const MONTH_LABELS = [
  'Jan',
  'Fev',
  'Mar',
  'Abr',
  'Mai',
  'Jun',
  'Jul',
  'Ago',
  'Set',
  'Out',
  'Nov',
  'Dez',
] as const;

export function formatMoney(value: number | string | undefined | null): string {
  const numeric = typeof value === 'string' ? parseFloat(value) : value;
  const safe =
    typeof numeric === 'number' && !Number.isNaN(numeric) ? numeric : 0;

  return `${safe.toLocaleString('pt', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })} AOA`;
}

export function frequencyLabel(frequencia: Frequencia): string {
  switch (frequencia) {
    case 'mensal':
      return 'Mensal';
    case 'anual':
      return 'Anual';
    case 'unico':
      return 'Pagamento único';
  }
}

/**
 * Resolve o saldo de um item num período específico a partir do PaidRecord.
 * Se nunca houve parcela, devolve status 'pendente' com esperado = valor cheio do catálogo
 * (passado como fallback, já que o backend só grava valor_esperado a partir da 1ª parcela).
 */
export function getSaldo(
  paidRecord: PaidRecord,
  itemPagavelId: string,
  mes: number,
  valorCatalogo: number,
): SaldoInfo {
  const info = paidRecord[itemPagavelId]?.[String(mes)];

  if (!info) {
    return {
      pago: 0,
      esperado: valorCatalogo,
      saldo: valorCatalogo,
      status: 'pendente',
    };
  }

  return info;
}
