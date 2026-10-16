const ESTADOS_PAP = {
    em_elaboracao: 'Em elaboração',
    entregue: 'Entregue',
    defendido: 'Defendido',
    aprovado: 'Aprovado',
};

export const formatarEstado = (s) =>
    ESTADOS_PAP[s] ??
    String(s).replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase());