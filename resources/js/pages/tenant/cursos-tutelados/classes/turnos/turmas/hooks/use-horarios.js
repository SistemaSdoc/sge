import { useState } from 'react';

const DIAS_SEMANA = [
  { id: 1, nome: 'Segunda' },
  { id: 2, nome: 'Terça' },
  { id: 3, nome: 'Quarta' },
  { id: 4, nome: 'Quinta' },
  { id: 5, nome: 'Sexta' },
  { id: 6, nome: 'Sábado' },
  { id: 7, nome: 'Domingo' },
];

const DIAS_UTEIS = [1, 2, 3, 4, 5];
const DEFAULT_HORARIO = { hora_inicio: '08:00', hora_fim: '09:30' };
const DURACAO_PADRAO = 90; // minutos, igual ao DEFAULT_HORARIO

// "18:00:00" ou "18:00" -> "18:00"
const hora = (valor) => String(valor ?? '').match(/\d{2}:\d{2}/)?.[0] ?? '';

const somarMinutos = (hhmm, minutos) => {
  const [h, m] = hhmm.split(':').map(Number);
  const total = Math.min(h * 60 + m + minutos, 23 * 60 + 59);

  return `${String(Math.floor(total / 60)).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
};

function initHorarios(defaultValues) {
  const hasExisting = Array.isArray(defaultValues) && defaultValues.length > 0;

  return DIAS_SEMANA.reduce((acc, { id }) => {
    const existentes = (hasExisting ? defaultValues : [])
      .filter((h) => Number(h.dia_semana) === id)
      .map((h) => ({
        hora_inicio: hora(h.hora_inicio),
        hora_fim: hora(h.hora_fim),
      }))
      .sort((a, b) => a.hora_inicio.localeCompare(b.hora_inicio));

    if (existentes.length > 0) {
      acc[id] = existentes;
    } else if (!hasExisting && DIAS_UTEIS.includes(id)) {
      acc[id] = [{ ...DEFAULT_HORARIO }];
    } else {
      acc[id] = [];
    }

    return acc;
  }, {});
}

const temSobreposicao = (slots) => {
  const ordenados = slots
    .filter((s) => s.hora_inicio && s.hora_fim)
    .sort((a, b) => a.hora_inicio.localeCompare(b.hora_inicio));

  return ordenados.some(
    (s, i) => i > 0 && s.hora_inicio < ordenados[i - 1].hora_fim,
  );
};

export function useHorarios(defaultValues = null) {
  const [horarios, setHorarios] = useState(() => initHorarios(defaultValues));

  // Dia activo = tem pelo menos um tempo
  const toggle = (id) =>
    setHorarios((prev) => ({
      ...prev,
      [id]: prev[id].length ? [] : [{ ...DEFAULT_HORARIO }],
    }));

  // O novo tempo começa quando o anterior termina
  const addSlot = (id) =>
    setHorarios((prev) => {
      const inicio = prev[id].at(-1)?.hora_fim || DEFAULT_HORARIO.hora_inicio;

      return {
        ...prev,
        [id]: [
          ...prev[id],
          {
            hora_inicio: inicio,
            hora_fim: somarMinutos(inicio, DURACAO_PADRAO),
          },
        ],
      };
    });

  // Remover o último tempo desactiva o dia
  const removeSlot = (id, index) =>
    setHorarios((prev) => ({
      ...prev,
      [id]: prev[id].filter((_, i) => i !== index),
    }));

  const update = (id, index, campo, valor) =>
    setHorarios((prev) => ({
      ...prev,
      [id]: prev[id].map((s, i) =>
        i === index ? { ...s, [campo]: valor } : s,
      ),
    }));

  const algumAtivo = Object.values(horarios).some((s) => s.length > 0);

  const incompleto = Object.values(horarios)
    .flat()
    .some((s) => !s.hora_inicio || !s.hora_fim || s.hora_fim <= s.hora_inicio);

  const erros = Object.fromEntries(
    Object.entries(horarios).map(([id, slots]) => [
      id,
      temSobreposicao(slots) ? 'Há tempos sobrepostos neste dia.' : null,
    ]),
  );

  const valido =
    algumAtivo && !incompleto && Object.values(erros).every((e) => !e);

  // Mesmo formato de antes: array de { dia_semana, hora_inicio, hora_fim }
  const getPayload = () =>
    DIAS_SEMANA.flatMap(({ id }) =>
      horarios[id].map((s) => ({
        dia_semana: id,
        hora_inicio: s.hora_inicio,
        hora_fim: s.hora_fim,
      })),
    );

  return {
    horarios,
    toggle,
    addSlot,
    removeSlot,
    update,
    algumAtivo,
    valido,
    erros,
    getPayload,
    DIAS_SEMANA,
  };
}
