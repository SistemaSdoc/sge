import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AlertError from '@/components/alert-error';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/react';
import { emitir, marcarComoPagoAction, marcarComoLevantado } from '@/actions/App/Http/Controllers/SolicitacaoDocumentoController';
import {
  resolveSolicitacaoStatus,
  solicitacaoDocumentoStatusLabels,
  solicitacaoDocumentoStatusClassNames,
} from '@/utils/solicitacao-documento-status';

export default function EmissaoSolicitacoesDocumentosPage() {
  const { solicitacoes = [] } = usePage().props;
  const form = useForm({ numero_registro_tutora: '' });
  const [errors, setErrors] = useState([]);

  const handleEmitir = (solicitacaoId) => {
    router.post(
      emitir(solicitacaoId).url,
      { numero_registro_tutora: form.data.numero_registro_tutora },
      {
        preserveScroll: true,
        onSuccess: () => setErrors([]),
        onError: (err) => {
          const msgs = Object.values(err || {}).flat().map((m) => String(m));
          setErrors(msgs.length ? msgs : ['Erro ao emitir o documento.']);
          window.scrollTo(0, 0);
        },
      },
    );
  };

  return (
    <div className="space-y-6 p-6">
      <div>
        <h1 className="text-2xl font-semibold">Emissão de documentos</h1>
        <p className="text-sm text-muted-foreground">
          Valide o número de registo da tutela e emita os documentos aprovados.
        </p>
      </div>

      <div className="space-y-4">
        {errors.length > 0 && <AlertError errors={errors} title="Erro" />}
        {solicitacoes.length === 0 && (
          <Card className="border-0">
            <CardContent className="py-8 text-sm text-muted-foreground">
              Nenhuma solicitação aprovada para emissão.
            </CardContent>
          </Card>
        )}

        {solicitacoes.map((solicitacao) => {
          const currentStatus = resolveSolicitacaoStatus(solicitacao);
          const statusLabel =
            solicitacaoDocumentoStatusLabels[currentStatus] ??
            currentStatus;

          return (
            <Card key={solicitacao.id} className="border-0">
              <CardHeader>
                <div className="flex items-center justify-between gap-3">
                  <CardTitle className="text-base">
                    {solicitacao.aluno} — {solicitacao.tipo_label}
                  </CardTitle>
                  <Badge
                    className={
                      solicitacaoDocumentoStatusClassNames[
                        currentStatus
                      ] ??
                      'bg-slate-100 text-slate-700'
                    }
                  >
                    {statusLabel}
                  </Badge>
                </div>
                <CardDescription>
                  Processo {solicitacao.numero_processo} •{' '}
                  {solicitacao.created_at}
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <p className="text-sm text-muted-foreground">
                  Motivo: {solicitacao.motivo}
                </p>

                <div className="space-y-2">
                  <Label htmlFor={`registo-${solicitacao.id}`}>
                    Número de registo da tutela
                  </Label>
                  <Input
                    id={`registo-${solicitacao.id}`}
                    value={form.data.numero_registro_tutora}
                    onChange={(event) =>
                      form.setData('numero_registro_tutora', event.target.value)
                    }
                    placeholder="Ex.: REG/2026/0001"
                  />
                </div>

                <div className="flex items-center gap-2">
                  <Button onClick={() => handleEmitir(solicitacao.id)}>
                    Emitir documento
                  </Button>

                  {solicitacao.estado_pagamento !== 'pago' && (
                    <Button
                      variant="secondary"
                      onClick={() => {
                        if (!confirm('Deseja marcar este documento como pago?')) return;
                        router.post(marcarComoPagoAction(solicitacao.id).url, {}, {
                          onSuccess: () => setErrors([]),
                          onError: (err) => {
                            const msgs = Object.values(err || {}).flat().map((m) => String(m));
                            setErrors(msgs.length ? msgs : ['Erro ao marcar como pago.']);
                            window.scrollTo(0, 0);
                          },
                        });
                      }}
                    >
                      Documento Pago
                    </Button>
                  )}

                  {solicitacao.can_marcar_levantado &&
                    solicitacao.documento_gerado &&
                    !solicitacao.data_levantamento && (
                      <Button
                        variant="outline"
                        onClick={() => {
                          if (!confirm('Deseja registar o levantamento do documento?')) return;
                          router.post(marcarComoLevantado(solicitacao.id).url, {}, {
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
                    )}
                </div>
              </CardContent>
            </Card>
          );
        })}
      </div>
    </div>
  );
}