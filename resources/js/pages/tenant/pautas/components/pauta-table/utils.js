export const PERIODOS = [
  { value: '1', label: '1º Trimestre' },
  { value: '2', label: '2º Trimestre' },
  { value: '3', label: '3º Trimestre' },
  { value: 'final', label: 'Pauta Final' },
  { value: 'recurso', label: 'Recurso' },
];

export const ehPautaFinal = (periodo) => periodo === 'final';
export const ehPautaRecurso = (periodo) => periodo === 'recurso';
export const ehPautaEspecial = (periodo) =>
  ehPautaFinal(periodo) || ehPautaRecurso(periodo);

export const formatarNota = (nota) =>
  nota !== null && nota !== undefined
    ? parseFloat(Number(nota).toFixed(1))
    : '—';

export const corNota = (media) => {
  if (media === null || media === undefined) {
    return '';
  }

  return media >= 10 ? 'text-blue-600' : 'text-destructive';
};

export const corFaltas = (faltas) => {
  if (faltas === null || faltas === undefined) {
    return '';
  }

  return faltas > 10 ? 'text-destructive' : 'text-blue-600';
};
