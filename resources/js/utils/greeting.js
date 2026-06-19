import { format } from 'date-fns';
import { pt } from 'date-fns/locale';

export const getGreeting = () => {
  const hora = new Date().getHours();

  if (hora < 12) {
    return 'Bom dia';
  }

  if (hora < 18) {
    return 'Boa tarde';
  }

  return 'Boa noite';
};

export function getTodayFormatted() {
  return format(new Date(), "EEEE, d 'de' MMMM 'de' yyyy", { locale: pt });
}

// funcao para pegar o dia da semana atual
export function getDiaSemana() {
  const diaSemana = [
    'Domingo',
    'Segunda-feira',
    'Terça-feira',
    'Quarta-feira',
    'Quinta-feira',
    'Sexta-feira',
    'Sábado',
  ];

  const today = new Date();

  return diaSemana[today.getDay()];
}
