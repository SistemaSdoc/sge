import { Head, router, Link } from '@inertiajs/react';
import { useState } from 'react';
import {
  ArrowLeft,
  Send,
  AlertCircle,
  CheckCircle,
  XCircle,
} from 'lucide-react';
import { toast } from 'sonner';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from '@/components/ui/field';
import { Spinner } from '@/components/spinner';

export default function Create({ prazo, turma, justificativa }) {
  const [motivo, setMotivo] = useState(justificativa?.motivo || '');
  const [loading, setLoading] = useState(false);

  const isBlocked =
    justificativa?.status === 'aceita' ||
    justificativa?.status === 'recusada';
  const prazoEncerrado = prazo.status !== 'aberto';

  const statusBadge = (status) => {
    if (status === 'aceita') {
      return (
        <Badge variant="outline">
          <CheckCircle className="mr-1 size-3" />
          Aceita
        </Badge>
      );
    }
    if (status === 'recusada') {
      return (
        <Badge variant="outline">
          <XCircle className="mr-1 size-3" />
          Recusada
        </Badge>
      );
    }
    return (
      <Badge variant="outline">
        <AlertCircle className="mr-1 size-3" />
        Pendente
      </Badge>
    );
  };

  const handleSubmit = (e) => {
    e.preventDefault();

    if (!motivo.trim()) {
      toast.error('Por favor, informe o motivo da não submissão.');
      return;
    }
    if (motivo.trim().length < 10) {
      toast.warning('O motivo deve ter pelo menos 10 caracteres.');
      return;
    }

    setLoading(true);

    router.post(
      `/dashboard/professor/prazos/${prazo.id}/turmas/${turma.id}/justificar`,
      { motivo: motivo.trim() },
      {
        onSuccess: () => {
          toast.success(
            `Justificativa enviada para a turma ${turma.nome}. Aguarde a avaliação.`,
          );
          router.visit('/dashboard/professor/provas');
        },
        onError: (errors) => {
          const msg =
            errors?.motivo || 'Erro ao enviar justificativa. Tente novamente.';
          toast.error(msg);
        },
        onFinish: () => setLoading(false),
      },
    );
  };

  return (
    <>
      <Head title="Justificar não submissão" />

      <div className="mx-auto w-full max-w-3xl space-y-4 p-4 sm:p-6">
        <Card className="gap-0 pb-0">
          {/* Header */}
          <CardHeader className="flex flex-col gap-3 border-b border-foreground/10 md:flex-row md:items-center">
            <div className="min-w-0 md:flex-1">
              <Breadcrumb>
                <BreadcrumbList className="flex-wrap">
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbLink asChild>
                      <Link
                        href="/dashboard/professor/provas"
                        className="line-clamp-2 text-sm font-semibold text-primary"
                      >
                        Provas
                      </Link>
                    </BreadcrumbLink>
                  </BreadcrumbItem>
                  <BreadcrumbSeparator />
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                      Justificar não submissão
                    </BreadcrumbPage>
                  </BreadcrumbItem>
                </BreadcrumbList>
              </Breadcrumb>
              <CardDescription>
                Preencha o formulário abaixo para justificar a não submissão da
                prova da turma <strong>{turma.nome}</strong>.
              </CardDescription>
            </div>
            <CardAction className="flex flex-wrap items-center gap-2">
              {justificativa && statusBadge(justificativa.status)}
              <Button variant="outline" size="sm" asChild>
                <Link href="/dashboard/professor/provas">
                  <ArrowLeft className="mr-1.5 size-4" />
                  Voltar
                </Link>
              </Button>
            </CardAction>
          </CardHeader>

          {/* Meta info — sem ícones */}
          <CardContent className="grid grid-cols-1 gap-4 border-b p-4 sm:grid-cols-2">
            <div className="min-w-0">
              <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                Disciplina
              </p>
              <p className="truncate text-sm font-medium">
                {prazo.disciplina || 'Todas'}
              </p>
            </div>
            <div className="min-w-0">
              <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                Classe
              </p>
              <p className="truncate text-sm font-medium">
                {prazo.classe || 'Todas'}
              </p>
            </div>
            <div className="min-w-0">
              <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                Turma
              </p>
              <p className="truncate text-sm font-semibold">{turma.nome}</p>
            </div>
            <div className="min-w-0">
              <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                Data limite
              </p>
              <p className="truncate text-sm font-medium whitespace-nowrap">
                {prazo.data_limite}
              </p>
            </div>
            <div className="col-span-1 sm:col-span-2">
              <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                Estado do prazo
              </p>
              <Badge variant="outline" className="mt-0.5 text-[10px]">
                {prazoEncerrado ? 'Encerrado' : 'Aberto'}
              </Badge>
            </div>
          </CardContent>

          {/* Form */}
          <CardContent className="p-4 sm:p-6">
            <form onSubmit={handleSubmit}>
              <FieldGroup>
                <FieldSet>
                  {prazoEncerrado && (
                    <div className="flex items-start gap-2 border bg-muted/50 p-3">
                      <AlertCircle className="mt-0.5 size-5 shrink-0 text-muted-foreground" />
                      <div>
                        <p className="text-sm font-medium">
                          Prazo encerrado
                        </p>
                        <p className="text-xs text-muted-foreground">
                          Este prazo já foi encerrado. A justificativa ainda pode
                          ser enviada para análise do diretor.
                        </p>
                      </div>
                    </div>
                  )}

                  <Field>
                    <FieldLabel htmlFor="motivo">
                      Motivo da não submissão *
                    </FieldLabel>
                    <Textarea
                      id="motivo"
                      value={motivo}
                      onChange={(e) => setMotivo(e.target.value)}
                      placeholder="Descreva detalhadamente o motivo pelo qual não pôde submeter a prova (mínimo 10 caracteres)..."
                      rows={6}
                      disabled={loading || isBlocked}
                      className="resize-none"
                    />
                    <p className="text-xs text-muted-foreground">
                      {motivo.trim().length === 0
                        ? 'Campo obrigatório'
                        : `${motivo.trim().length} caracteres (mínimo 10)`}
                    </p>
                    <FieldError>{null}</FieldError>
                  </Field>

                  {justificativa && (
                    <div className="border bg-muted/40 p-4">
                      <div className="flex flex-wrap items-center gap-2">
                        <p className="text-sm font-medium">
                          Justificativa enviada
                        </p>
                        {statusBadge(justificativa.status)}
                      </div>
                      {isBlocked && (
                        <p className="mt-2 flex items-center gap-1 text-xs text-muted-foreground">
                          <AlertCircle className="size-3" />
                          Esta justificativa já foi avaliada e não pode mais ser
                          alterada.
                        </p>
                      )}
                      {justificativa.motivo && (
                        <div className="mt-2 border bg-background p-2">
                          <p className="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">
                            Motivo enviado
                          </p>
                          <p className="mt-0.5 text-sm">
                            {justificativa.motivo}
                          </p>
                        </div>
                      )}
                    </div>
                  )}
                </FieldSet>
              </FieldGroup>

              {/* Botões */}
              <div className="mt-6 flex flex-col-reverse gap-2 border-t pt-4 sm:flex-row sm:justify-end">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => router.visit('/dashboard/professor/provas')}
                  disabled={loading}
                  className="w-full hover:cursor-pointer sm:w-auto"
                >
                  Cancelar
                </Button>
                <Button
                  type="submit"
                  disabled={loading || isBlocked}
                  className="w-full hover:cursor-pointer sm:w-auto"
                >
                  {loading ? (
                    <>
                      <Spinner />
                      Enviando...
                    </>
                  ) : (
                    <>
                      <Send className="mr-2 size-4" />
                      Enviar justificativa
                    </>
                  )}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </>
  );
}