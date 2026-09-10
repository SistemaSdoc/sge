export const resolveSolicitacaoStatus = (solicitacao) => {
  if (!solicitacao) {
    return 'pendente';
  }

  if (solicitacao.data_levantamento || solicitacao.status === 'entregue') {
    return 'entregue';
  }

  if (solicitacao.data_emissao || solicitacao.status === 'pronto') {
    return 'pronto';
  }

  if (solicitacao.estado_pagamento === 'pago' || solicitacao.status === 'pago') {
    return 'pago';
  }

  return solicitacao.status ?? 'pendente';
};

export const solicitacaoDocumentoStatusLabels = {
  pendente: 'Pendente',
  aprovado: 'Aprovado',
  pago: 'Pago / em preparação',
  pronto: 'Pronto para levantamento',
  entregue: 'Entregue',
  rejeitado: 'Rejeitado',
};

export const solicitacaoDocumentoStatusClassNames = {
  pendente: 'bg-amber-100 text-amber-800',
  aprovado: 'bg-green-100 text-green-800',
  pago: 'bg-blue-100 text-blue-800',
  pronto: 'bg-violet-100 text-violet-800',
  entregue: 'bg-emerald-100 text-emerald-800',
  rejeitado: 'bg-red-100 text-red-800',
};
