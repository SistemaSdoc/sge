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

function initHorarios(defaultValues) {
  const hasExisting = Array.isArray(defaultValues) && defaultValues.length > 0;

  return DIAS_SEMANA.reduce((acc, { id }) => {
    const existing = defaultValues?.find((h) => h.dia_semana === id);

    acc[id] = {
      ativo: existing ? true : hasExisting ? false : DIAS_UTEIS.includes(id),
      hora_inicio: existing?.hora_inicio ?? DEFAULT_HORARIO.hora_inicio,
      hora_fim: existing?.hora_fim ?? DEFAULT_HORARIO.hora_fim,
    };

    return acc;
  }, {});
}

export function useHorarios(defaultValues = null) {
  const [horarios, setHorarios] = useState(() => initHorarios(defaultValues));

  const toggle = (id) =>
    setHorarios((prev) => ({
      ...prev,
      [id]: { ...prev[id], ativo: !prev[id].ativo },
    }));

  const update = (id, campo, valor) =>
    setHorarios((prev) => ({ ...prev, [id]: { ...prev[id], [campo]: valor } }));

  const algumAtivo = Object.values(horarios).some((h) => h.ativo);

  const getPayload = () =>
    DIAS_SEMANA.filter((d) => horarios[d.id].ativo).map(({ id }) => ({
      dia_semana: id,
      hora_inicio: horarios[id].hora_inicio,
      hora_fim: horarios[id].hora_fim,
    }));

  return { horarios, toggle, update, algumAtivo, getPayload, DIAS_SEMANA };
}
