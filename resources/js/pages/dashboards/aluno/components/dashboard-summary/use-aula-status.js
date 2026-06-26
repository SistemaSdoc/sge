import {
  parse,
  isBefore,
  isWithinInterval,
  intervalToDuration,
  formatDuration,
} from 'date-fns';
import { pt } from 'date-fns/locale';
import { useEffect, useState, useMemo } from 'react';

function getAulasHoje(aulas) {
  const hoje = new Date().toISOString().split('T')[0];

  return aulas.filter((a) => a.dia === hoje);
}

function getProximaAula(aulas) {
  const agora = new Date();

  for (const aula of getAulasHoje(aulas)) {
    const fim = parse(aula.horario.hora_fim, 'HH:mm', new Date());

    if (isBefore(agora, fim)) {
      return aula;
    }
  }

  return null;
}

export function useAulaStatus(aulas) {
  const [status, setStatus] = useState('loading');
  const [timeLeft, setTimeLeft] = useState('');

  const proximaAula = useMemo(
    () => (aulas?.length ? getProximaAula(aulas) : null),
    [aulas],
  );

  useEffect(() => {
    const calculate = () => {
      if (!proximaAula) {
        setStatus('empty');

        setTimeLeft('');

        return;
      }

      const agora = new Date();

      const inicio = parse(
        proximaAula.horario.hora_inicio,
        'HH:mm',
        new Date(),
      );

      const fim = parse(proximaAula.horario.hora_fim, 'HH:mm', new Date());

      if (isWithinInterval(agora, { start: inicio, end: fim })) {
        setStatus('happening');

        setTimeLeft('');

        return;
      }

      if (isBefore(agora, inicio)) {
        const duration = intervalToDuration({ start: agora, end: inicio });

        setStatus('today');

        setTimeLeft(
          formatDuration(duration, {
            format: ['hours', 'minutes'],
            delimiter: ' ',
            locale: pt,
          }),
        );

        return;
      }

      setStatus('empty');

      setTimeLeft('');
    };

    calculate();

    const interval = setInterval(calculate, 60000);

    return () => clearInterval(interval);
  }, [proximaAula]);

  return { status, proximaAula, timeLeft };
}
