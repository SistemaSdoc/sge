import { router, useForm, usePage } from '@inertiajs/react';
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
import { Textarea } from '@/components/ui/textarea';
import { store } from '@/actions/App/Http/Controllers/SolicitacaoDocumentoController';
import {
  resolveSolicitacaoStatus,
  solicitacaoDocumentoStatusLabels,
  solicitacaoDocumentoStatusClassNames,
} from '@/utils/solicitacao-documento-status';

const getTipoLabel = (tipo) => {
  const map = {
    declaracao: 'Declaração Escolar sem Notas',
    historico: 'Histórico Académico',
    certificado: 'Certificado de Habilitações',
    declaracao_com_notas: 'Declaração Escolar com Notas',
  };

  return map[tipo] ?? tipo;
};

export default function SolicitacoesDocumentosPage() {
  const {
    solicitacoes = [],
    tipos = [],
    classes_disponiveis = [],
    pode_certificado = false,
    curso_atual = null,
    turma_atual = null,
    classe_atual = null,
    ano_lectivo_atual = null,
  } = usePage().props;

  const initialFormData = {
    tipo_documento: '',
    curso_id: curso_atual?.id ?? '',
    turma_id: turma_atual?.id ?? '',
    classe_id: classe_atual?.id ?? '',
    ano_lectivo_id: ano_lectivo_atual?.id ?? '',
    instituicao_emissora_id: '',
    motivo: '',
    observacoes: '',
  };

  const form = useForm(initialFormData);
  const [errors, setErrors] = useState([]);
  const [successMessage, setSuccessMessage] = useState('');

  const submit = (event) => {
    event.preventDefault();
    form.post(store().url, {
      preserveScroll: true,
      onSuccess: () => {
        form.reset(); // sem argumentos: repõe TODOS os campos para os valores iniciais
        setErrors([]);
        setSuccessMessage('Pedido enviado com sucesso. Aguarde a análise da sua solicitação.');
        window.scrollTo(0, 0);
      },
      onError: (err) => {
        const msgs = Object.values(err || {})
          .flat()
          .map((m) => String(m));
        setErrors(
          msgs.length ? msgs : ['Ocorreu um erro ao submeter o pedido.'],
        );
        setSuccessMessage('');
        window.scrollTo(0, 0);
      },
    });
  };

  const handleDelete = (solicitacao) => {
    const currentStatus = resolveSolicitacaoStatus(solicitacao);

    if (!solicitacao?.can_delete || currentStatus !== 'entregue') {
      return;
    }

    const confirmed = window.confirm(
      'Tem a certeza que pretende apagar este pedido? Esta ação não pode ser anulada.',
    );

    if (!confirmed) {
      return;
    }

    router.delete(`/dashboard/solicitacoes-documentos/${solicitacao.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        router.reload({ only: ['solicitacoes'] });
      },
      onError: () => {
        setErrors(['Não foi possível apagar o pedido.']);
      },
    });
  };

  return (
    <div className="space-y-6 p-6">
      <div className="flex items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold">Solicitar Documentos</h1>
          <p className="text-sm text-muted-foreground">
            Registe o pedido e acompanhe o estado do documento solicitado.
          </p>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
        <Card>
          <CardHeader>
            <CardTitle>Novo pedido</CardTitle>
            <CardDescription>
              Seleccione o tipo de documento e o contexto académico solicitado.
            </CardDescription>
          </CardHeader>

          <CardContent>
            {errors.length > 0 && (
              <AlertError errors={errors} title="Erro ao submeter pedido" />
            )}

            {successMessage && (
              <div className="mb-4 border border-green-200 bg-green-50 p-3 text-sm text-green-800">
                {successMessage}
              </div>
            )}

            <form className="space-y-5" onSubmit={submit}>
              <div className="space-y-2">
                <Label htmlFor="tipo-documento">Tipo de documento</Label>
                <select
                  id="tipo-documento"
                  value={form.data.tipo_documento}
                  onChange={(event) =>
                    form.setData('tipo_documento', event.target.value)
                  }
                  className="flex h-10 w-full border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                >
                  <option value="" disabled>
                    Seleccione o documento a solicitar
                  </option>
                  {tipos.map((tipo) => {
                    if (tipo.value === 'certificado' && !pode_certificado) {
                      return null;
                    }

                    return (
                      <option key={tipo.value} value={tipo.value}>
                        {tipo.label}
                      </option>
                    );
                  })}
                </select>
              </div>

              <div className="border bg-muted/30 p-3">
                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                  Curso e turma atuais
                </p>
                <div className="mt-2 space-y-1 text-sm">
                  <p>
                    <span className="font-medium">Curso:</span>{' '}
                    {curso_atual?.nome ?? 'Curso não identificado'}
                  </p>
                  <p>
                    <span className="font-medium">Turma:</span>{' '}
                    {turma_atual?.nome ?? 'Turma não identificada'}
                  </p>
                  <p>
                    <span className="font-medium">Classe:</span>{' '}
                    {classe_atual?.nome ?? 'Classe não identificada'}
                  </p>
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="motivo">Motivo</Label>
                <Input
                  id="motivo"
                  value={form.data.motivo}
                  onChange={(event) =>
                    form.setData('motivo', event.target.value)
                  }
                  placeholder="Ex.: Solicito para matrícula / continuidade / apoio académico"
                />
                {form.errors.motivo && (
                  <p className="text-xs text-red-600">{form.errors.motivo}</p>
                )}
              </div>

              {form.data.tipo_documento === 'declaracao_com_notas' && (
                <div className="space-y-2">
                  <Label htmlFor="classe-declaracao">
                    Classe para declaração
                  </Label>
                  <select
                    id="classe-declaracao"
                    value={form.data.classe_id}
                    onChange={(event) =>
                      form.setData('classe_id', event.target.value)
                    }
                    className="flex h-10 w-full border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                  >
                    <option value="">Seleccionar classe</option>
                    {classes_disponiveis.map((c) => (
                      <option key={c.id} value={c.id}>
                        {c.nome}
                      </option>
                    ))}
                  </select>
                  {form.errors.classe_id && (
                    <p className="text-xs text-red-600">
                      {form.errors.classe_id}
                    </p>
                  )}
                </div>
              )}

              <div className="space-y-2">
                <Label htmlFor="observacoes">Observações</Label>
                <Textarea
                  id="observacoes"
                  value={form.data.observacoes}
                  onChange={(event) =>
                    form.setData('observacoes', event.target.value)
                  }
                  placeholder="Detalhes adicionais do pedido"
                  rows={5}
                />
              </div>

              <Button
                type="submit"
                className="w-full"
                disabled={form.processing || !form.data.tipo_documento}
              >
                {form.processing ? 'A enviar...' : 'Enviar pedido'}
              </Button>
            </form>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Estado dos pedidos</CardTitle>
            <CardDescription>
              Consulta o histórico e o estado atual.
            </CardDescription>
          </CardHeader>

          <CardContent className="space-y-3">
            {solicitacoes.length === 0 && (
              <p className="text-sm text-muted-foreground">
                Ainda não existem pedidos registados.
              </p>
            )}

            {solicitacoes.map((solicitacao) => {
              const currentStatus = resolveSolicitacaoStatus(solicitacao);
              const statusLabel =
                solicitacaoDocumentoStatusLabels[currentStatus] ??
                currentStatus;

              return (
                <div key={solicitacao.id} className="border bg-muted/30 p-3">
                  <div className="flex items-center justify-between gap-3">
                    <span className="text-sm font-medium">
                      Pedido de {getTipoLabel(solicitacao.tipo_documento)}
                    </span>
                    <Badge
                      className={
                        solicitacaoDocumentoStatusClassNames[currentStatus] ??
                        'bg-slate-100 text-slate-700'
                      }
                    >
                      {statusLabel}
                    </Badge>
                  </div>
                  <p className="mt-2 text-sm text-muted-foreground">
                    {getTipoLabel(solicitacao.tipo_documento)} —{' '}
                    {solicitacao.motivo}
                  </p>
                  <p className="mt-1 text-xs text-muted-foreground">
                    Processo {solicitacao.numero_processo ?? 'a gerar'} •
                    Registado em {solicitacao.created_at}
                  </p>
                  {solicitacao.data_emissao && (
                    <p className="mt-1 text-xs text-muted-foreground">
                      Emitido em {solicitacao.data_emissao}
                    </p>
                  )}

                  {solicitacao.rupe_referencia && (
                    <p className="mt-1 text-xs text-muted-foreground">
                      Referência: {solicitacao.rupe_referencia} • Entidade:{' '}
                      {solicitacao.rupe_entidade} • Valor:{' '}
                      {solicitacao.rupe_valor ?? '-'}
                    </p>
                  )}

                  {solicitacao.can_delete && currentStatus === 'entregue' && (
                    <div className="mt-3 flex justify-end">
                      <Button
                        type="button"
                        variant="destructive"
                        size="sm"
                        onClick={() => handleDelete(solicitacao)}
                      >
                        Apagar pedido
                      </Button>
                    </div>
                  )}
                </div>
              );
            })}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}