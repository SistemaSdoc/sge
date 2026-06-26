/**
 * Utilitário para determinar a categoria de dia de uma aula
 *
 * @param {string} dia - Data da aula em formato YYYY-MM-DD
 * @returns {string} Categoria do dia: 'today', 'tomorrow', 'day-after-tomorrow', 'future'
 *
 * @description
 * Compara a data da aula com hoje para determinar em qual categoria ela se encaixa:
 * - 'today': Aula é hoje
 * - 'tomorrow': Aula é amanhã
 * - 'day-after-tomorrow': Aula é depois de amanhã
 * - 'future': Aula é em mais de 2 dias
 */
export function getDayCategory(dia) {
  if (!dia) {
    return 'future';
  }

  const today = new Date();
  const aluaDate = new Date(dia);

  // Normalizar para comparação (sem tempo)
  today.setHours(0, 0, 0, 0);
  aluaDate.setHours(0, 0, 0, 0);

  const diffTime = aluaDate - today;
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

  if (diffDays === 0) {
    return 'today';
  }

  if (diffDays === 1) {
    return 'tomorrow';
  }

  if (diffDays === 2) {
    return 'day-after-tomorrow';
  }

  return 'future';
}

/**
 * Mapeia categoria de dia para variante de badge com cores semânticas
 *
 * @param {string} category - Categoria retornada por getDayCategory()
 * @returns {string} Variante do badge tailwindCSS
 *
 * @description
 * Cada categoria tem uma cor visual:
 * - 'today': Verde (default) - acontece agora
 * - 'tomorrow': Amarelo (secondary) - próximo
 * - 'day-after-tomorrow': Laranja (outline) - em 2 dias
 * - 'future': Cinza (ghost) - distante
 */
export function getBadgeVariantForDay(category) {
  const variants = {
    today:
      'border-emerald-200 dark:border-emerald-900/30 bg-emerald-50 dark:bg-emerald-950/20 text-emerald-500 dark:text-emerald-400 p-3', // Verde
    tomorrow:
      'border-yellow-200 dark:border-yellow-900/30 bg-yellow-50 dark:bg-yellow-950/20 text-yellow-500 dark:text-yellow-400 p-3', // Amarelo
    'day-after-tomorrow':
      'border-orange-200 dark:border-orange-900/30 bg-orange-50 dark:bg-orange-950/20 text-orange-500 dark:text-orange-400 p-3', // Laranja/Cinza
    future:
      'border-gray-200 dark:border-gray-900/30 bg-gray-50 dark:bg-gray-950/20 text-gray-500 dark:text-gray-400 p-3', // Cinza claro
  };

  return variants[category] || 'ghost';
}

/**
 * Determina se uma aula está a decorrer neste momento
 *
 * @param {string} dia - Data da aula em formato YYYY-MM-DD
 * @param {Object} horario - Objeto com hora_inicio e hora_fim (formato HH:mm)
 * @returns {boolean} true se a aula está a decorrer agora
 */
export function isAulaHappening(dia, horario) {
  if (!dia || !horario) {
    return false;
  }

  const today = new Date().toISOString().split('T')[0];

  if (dia !== today) {
    return false;
  }

  const now = new Date();
  const [horaInicio, minutoInicio] = horario.hora_inicio.split(':').map(Number);
  const [horaFim, minutoFim] = horario.hora_fim.split(':').map(Number);

  const aulaInicio = new Date();
  aulaInicio.setHours(horaInicio, minutoInicio, 0);

  const aulaFim = new Date();
  aulaFim.setHours(horaFim, minutoFim, 0);

  return now >= aulaInicio && now <= aulaFim;
}
