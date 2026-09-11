import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
  processarDecisao,
  emitir,
  marcarComoPagoAction,
  marcarComoLevantado,
} from '@/actions/App/Http/Controllers/SolicitacaoDocumentoController';
import AlertError from '@/components/alert-error';
import {
  resolveSolicitacaoStatus,
  solicitacaoDocumentoStatusLabels,
  solicitacaoDocumentoStatusClassNames,
} from '@/utils/solicitacao-documento-status';
import RequestHistoryDrawer from '@/components/RequestHistoryDrawer';

export default function TutelaSolicitacoesDocumentosPage() {
  const { solicitacoes_locais = [], solicitacoes_tuteladas = [], auth = {}, ver = null } =
    usePage().props;
  const [errors, setErrors] = useState([]);

  const handleDecision = (solicitacaoId, decisao) => {
    router.post(
      processarDecisao(solicitacaoId).url,
      { decisao },
      {
        preserveScroll: true,
        onSuccess: () => setErrors([]),
        onError: (err) => {
          const msgs = Object.values(err || {}).flat().map((m) => String(m));
          setErrors(msgs.length ? msgs : ['Ocorreu um erro ao processar a decisão.']);
          window.scrollTo(0, 0);
        },
      }
    );
  };

  const getResponsibleLabel = (solicitacao) => {
    if (solicitacao.responsavel_instituicao_id === solicitacao.instituicao_tutora_id) {
      return 'A cargo do instituto';
    }
    return 'A cargo do colégio';
  };

  const renderSolicitacoes = (solicitacoes, titulo, descricao) => (
    <Card className="h-full">
      <CardHeader>
        <CardTitle className="text-lg">{titulo}</CardTitle>
        <CardDescription>{descricao}</CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        {errors.length > 0 && <AlertError errors={errors} title="Erro ao processar decisão" />}
        {solicitacoes.length === 0 && (
          <p className="text-sm text-muted-foreground">
            Nenhuma solicitação pendente nesta categoria.
          </p>
        )}

        {solicitacoes.map((solicitacao) => {
          const currentStatus = resolveSolicitacaoStatus(solicitacao);
          const statusLabel = solicitacaoDocumentoStatusLabels[currentStatus] ?? currentStatus;
          const isResponsible = Boolean(solicitacao.can_marcar_pago || solicitacao.can_marcar_pronto);
          const showPendingDecision = currentStatus === 'pendente' && Boolean(solicitacao.can_decidir);
          const showPaymentAction =
            currentStatus === 'aprovado' &&
            solicitacao.estado_pagamento !== 'pago' &&
            Boolean(solicitacao.can_marcar_pago);
          const showReadyAction =
            currentStatus === 'pago' &&
            !solicitacao.data_emissao &&
            Boolean(solicitacao.can_marcar_pronto);
          const showResponsibleLabel = currentStatus === 'aprovado' && !isResponsible;

          const showLevantamentoAction =
            solicitacao.documento_gerado &&
            !solicitacao.data_levantamento &&
            Boolean(solicitacao.can_marcar_levantado);

          return (
            <div key={solicitacao.id} className="border bg-muted/30 p-4">
              <div className="flex items-center justify-between gap-3">
                <p className="text-sm font-medium">
                  {solicitacao.aluno} - {solicitacao.tipo_label}
                </p>
                <Badge
                  className={
                    solicitacaoDocumentoStatusClassNames[currentStatus] ??
                    'bg-slate-100 text-slate-700'
                  }
                >
                  {statusLabel}
                </Badge>
              </div>

              <div className="mt-2 space-y-1 text-sm text-muted-foreground">
                <p>Origem: {solicitacao.origem}</p>
                <p>Processo: {solicitacao.numero_processo ?? 'a gerar'}</p>
                <p>{solicitacao.created_at}</p>
              </div>

              <p className="mt-3 text-sm text-muted-foreground">
                Motivo: {solicitacao.motivo}
              </p>

              {solicitacao.observacoes && (
                <p className="mt-2 text-sm text-muted-foreground">
                  Observações: {solicitacao.observacoes}
                </p>
              )}

              {['pendente', 'aprovado', 'pago', 'pronto', 'entregue'].includes(currentStatus) && (
                <div className="mt-4 flex flex-wrap items-center gap-2 text-[10px] font-medium uppercase tracking-wide text-muted-foreground">
                  {[
                    { label: 'Pendente', complete: currentStatus !== 'pendente' },
                    {
                      label: 'Aprovado',
                      complete: ['aprovado', 'pago', 'pronto', 'entregue'].includes(currentStatus),
                    },
                    { label: 'Pago', complete: ['pago', 'pronto', 'entregue'].includes(currentStatus) },
                    { label: 'Pronto', complete: ['pronto', 'entregue'].includes(currentStatus) },
                    { label: 'Levantado', complete: currentStatus === 'entregue' },
                  ].map((step, index) => (
                    <div key={step.label} className="flex items-center gap-2">
                      <span
                        className={
                          step.complete
                            ? 'rounded-full bg-green-100 px-2 py-1 text-green-800'
                            : 'rounded-full bg-slate-100 px-2 py-1 text-slate-500'
                        }
                      >
                        {step.label}
                      </span>
                      {index < 3 && <span className="text-slate-400">→</span>}
                    </div>
                  ))}
                </div>
              )}

              {showPendingDecision && (
                <div className="mt-4 flex flex-wrap gap-2">
                  <Button onClick={() => handleDecision(solicitacao.id, 'aprovado')}>
                    Aprovar
                  </Button>
                  <Button
                    variant="destructive"
                    onClick={() => handleDecision(solicitacao.id, 'rejeitado')}
                  >
                    Rejeitar
                  </Button>
                </div>
              )}

              {!showPendingDecision && currentStatus === 'pendente' && (
                <p className="mt-4 text-xs text-muted-foreground">A cargo do instituto</p>
              )}

              {showPaymentAction && (
                <div className="mt-4 flex flex-wrap gap-2">
                  <Button
                    onClick={() => {
                      router.post(marcarComoPagoAction(solicitacao.id).url, {}, {
                        preserveScroll: true,
                        onSuccess: () => setErrors([]),
                        onError: (err) => {
                          const msgs = Object.values(err || {}).flat().map((m) => String(m));
                          setErrors(msgs.length ? msgs : ['Erro ao marcar como pago.']);
                          window.scrollTo(0, 0);
                        },
                      });
                    }}
                  >
                    Marcar como pago
                  </Button>
                </div>
              )}

              {showReadyAction && (
                <div className="mt-4 flex flex-wrap gap-2">
                  <Button
                    onClick={() => {
                      router.post(
                        emitir(solicitacao.id).url,
                        {
                          numero_registro_tutora:
                            solicitacao.numero_registro_tutora || 'REGISTO MANUAL',
                        },
                        {
                          preserveScroll: true,
                          onSuccess: () => setErrors([]),
                          onError: (err) => {
                            const msgs = Object.values(err || {}).flat().map((m) => String(m));
                            setErrors(msgs.length ? msgs : ['Erro ao marcar documento como pronto.']);
                            window.scrollTo(0, 0);
                          },
                        }
                      );
                    }}
                  >
                    Marcar documento pronto
                  </Button>
                </div>
              )}

              {showLevantamentoAction && (
                <div className="mt-4 flex flex-wrap gap-2">
                  <Button
                    variant="outline"
                    onClick={() => {
                      if (!confirm('Deseja registar o levantamento do documento?')) return;
                      router.post(marcarComoLevantado(solicitacao.id).url, {}, {
                        preserveScroll: true,
                        onSuccess: () => setErrors([]),
                        onError: (err) => {
                          const msgs = Object.values(err || {}).flat().map((m) => String(m));
                          setErrors(msgs.length ? msgs : ['Erro ao registar levantamento.']);
                          window.scrollTo(0, 0);
                        },
                      });
                    }}
                  >
                    Marcar como levantado
                  </Button>
                </div>
              )}

              {showResponsibleLabel && (
                <p className="mt-4 text-xs text-muted-foreground">
                  {getResponsibleLabel(solicitacao)}
                </p>
              )}
            </div>
          );
        })}
      </CardContent>
    </Card>
  );

  return (
    <div className="space-y-6 p-6">
      <div className="flex items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold">Solicitações de Documentos</h1>
          <p className="text-sm text-muted-foreground">
            Acompanhe e decida os pedidos locais e os pedidos enviados pelas
            instituições tuteladas.
          </p>
        </div>

        <div>
          <RequestHistoryDrawer viewType="received" items={ver === 'historico' ? [...solicitacoes_locais, ...solicitacoes_tuteladas] : undefined} />
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        {renderSolicitacoes(
          solicitacoes_locais,
          'Documentos Solicitados Locais',
          'Pedidos feitos diretamente por alunos da própria instituição.'
        )}
        {renderSolicitacoes(
          solicitacoes_tuteladas,
          'Documentos solicitados por instituição tutelada',
          'Pedidos enviados pelas instituoções tuteladas já aprovados.'
        )}
      </div>
    </div>
  );
}