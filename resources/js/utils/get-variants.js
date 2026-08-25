/**
 * Retorna cor do Badge baseado no status
 */
export const getStatusVariant = (status) => {
  const normalized = status?.toLowerCase?.();

  switch (normalized) {
    case 'apto':
      return 'success';
    case 'n/apto':
      return 'destructive';
    case 'pendente':
      return 'secondary';
    default:
      return 'outline';
  }
};
