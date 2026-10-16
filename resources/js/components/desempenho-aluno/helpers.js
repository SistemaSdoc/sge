export const fmt = (v) =>
  v === null || v === undefined || v === ''
    ? '—'
    : Number(v).toFixed(1).replace('.', ',');