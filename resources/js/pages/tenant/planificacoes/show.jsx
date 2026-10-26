import { Head, Link, router } from '@inertiajs/react';
import { useState, useMemo } from 'react';
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
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { PeriodoBadge } from '@/components/periodo-badge';
import { Spinner } from '@/components/spinner';

export default function Show({ planificacao, can = {} }) {
  const [previewVersao, setPreviewVersao] = useState(null);
  const [deleteDialog, setDeleteDialog] = useState(false);
  const [deleting, setDeleting] = useState(false);

  const versaoAtual =
    planificacao.versoes.find((v) => v.is_atual) ?? planificacao.versoes[0];

  const versoesOrdenadas = useMemo(
    () => [...planificacao.versoes].sort((a, b) => b.versao - a.versao),
    [planificacao.versoes],
  );

  const handleDelete = () => {
    setDeleting(true);
    router.delete(`/dashboard/planificacoes/${planificacao.id}`, {
      onFinish: () => setDeleting(false),
      onSuccess: () => {
        toast.success('Planificação apagada', {
          description: 'Todas as versões foram removidas.',
        });
      },
      onError: () => {
        toast.error('Não foi possível remover', {
          description: 'Tente novamente em alguns instantes.',
        });
      },
    });
  };

  const metaInfo = [
    { label: 'Disciplina', value: planificacao.disciplina?.nome ?? '—' },
    { label: 'Classe', value: planificacao.classe?.nome ?? '—' },
    { label: 'Ano letivo', value: planificacao.ano_letivo?.nome ?? '—' },
    { label: 'Publicada por', value: planificacao.criada_por ?? '—' },
    { label: 'Atualizada em', value: planificacao.atualizada_em },
    { label: 'Versões', value: `${planificacao.versoes.length}` },
  ];

  return (
    <>
      <Head title={planificacao.titulo ?? 'Planificação'} />

      <div className="mx-auto w-full max-w-5xl space-y-4 p-4 sm:p-6">
        {/* Header */}
        <Card className="gap-0 pb-0">
          <CardHeader className="flex flex-col gap-3 border-b border-foreground/10 md:flex-row md:items-start">
            <div className="min-w-0 md:flex-1">
              <Breadcrumb>
                <BreadcrumbList className="flex-wrap">
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbLink asChild>
                      <Link
                        href="/dashboard/planificacoes"
                        className="line-clamp-2 text-sm font-semibold text-primary"
                      >
                        Planificações
                      </Link>
                    </BreadcrumbLink>
                  </BreadcrumbItem>
                  <BreadcrumbSeparator />
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                      {planificacao.titulo ?? 'Planificação'}
                    </BreadcrumbPage>
                  </BreadcrumbItem>
                </BreadcrumbList>
              </Breadcrumb>

              <div className="mt-2 flex flex-wrap items-center gap-2">
                <PeriodoBadge periodo={planificacao.periodo} />
                <Badge variant="outline" className="font-mono text-[11px]">
                  v{planificacao.versao_atual}
                </Badge>
                {planificacao.versoes.length > 1 && (
                  <Badge variant="outline" className="gap-1 font-normal">
                    {planificacao.versoes.length} versões
                  </Badge>
                )}
              </div>

              <CardDescription className="mt-2">
                {planificacao.descricao ??
                  'Sem descrição adicional para esta planificação.'}
              </CardDescription>
            </div>

            <CardAction className="flex flex-wrap items-center gap-2 self-stretch md:self-start">
              <Button variant="outline" size="sm" asChild>
                <Link href="/dashboard/planificacoes">Voltar</Link>
              </Button>
              {can.delete && (
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setDeleteDialog(true)}
                  className="hover:cursor-pointer"
                >
                  Remover
                </Button>
              )}
            </CardAction>
          </CardHeader>

          {/* Meta grid — sem ícones */}
          <CardContent className="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 md:grid-cols-3">
            {metaInfo.map(({ label, value }) => (
              <div key={label} className="min-w-0">
                <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                  {label}
                </p>
                <p className="truncate font-medium" title={value}>
                  {value}
                </p>
              </div>
            ))}
          </CardContent>
        </Card>

        {/* Versão atual */}
        {versaoAtual && (
          <Card className="gap-0 border pb-0">
            <CardHeader className="border-b bg-muted/30">
              <div className="flex items-center gap-3">
                <div className="min-w-0 flex-1">
                  <div className="flex items-center gap-2">
                    <CardTitle className="text-base">Versão atual</CardTitle>
                    <Badge
                      variant="outline"
                      className="font-mono text-[10px]"
                    >
                      v{versaoAtual.versao}
                    </Badge>
                  </div>
                  <CardDescription className="mt-0.5 truncate">
                    {versaoAtual.nome_original}
                  </CardDescription>
                </div>
              </div>
            </CardHeader>
            <CardContent className="p-4">
              <div className="flex flex-wrap gap-2">
                {versaoAtual.eh_pdf && (
                  <Button
                    variant="outline"
                    onClick={() => setPreviewVersao(versaoAtual)}
                    className="hover:cursor-pointer"
                  >
                    Pré-visualizar
                  </Button>
                )}
                <Button asChild className="hover:cursor-pointer">
                  <a
                    href={`/dashboard/planificacoes/versao/${versaoAtual.id}/download`}
                  >
                    Descarregar
                  </a>
                </Button>
              </div>
            </CardContent>
          </Card>
        )}

        {/* Histórico */}
        {versoesOrdenadas.length > 1 && (
          <Card className="gap-0 pb-0">
            <CardHeader className="flex flex-col gap-3 border-b md:flex-row md:items-center">
              <div className="min-w-0 md:flex-1">
                <CardTitle className="text-base">
                  Histórico de versões
                </CardTitle>
                <CardDescription>
                  Todas as versões submetidas para esta planificação.
                </CardDescription>
              </div>
              <CardAction>
                <Badge variant="outline" className="font-mono text-[10px]">
                  {versoesOrdenadas.length}
                </Badge>
              </CardAction>
            </CardHeader>

            <CardContent className="p-0!">
              <div className="divide-y">
                {versoesOrdenadas.map((v) => (
                  <div
                    key={v.id}
                    className={`flex flex-wrap items-center justify-between gap-3 p-3 transition-colors ${
                      v.is_atual ? 'bg-muted/30' : 'hover:bg-muted/40'
                    }`}
                  >
                    <div className="flex min-w-0 flex-1 items-center gap-3">
                      <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                          <Badge
                            variant="outline"
                            className="font-mono text-[10px]"
                          >
                            v{v.versao}
                          </Badge>
                          {v.is_atual && (
                            <span className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">
                              Atual
                            </span>
                          )}
                        </div>
                        <p
                          className="mt-1 truncate text-sm font-medium"
                          title={v.nome_original}
                        >
                          {v.nome_original}
                        </p>
                        <div className="mt-0.5 flex flex-wrap items-center gap-2 text-[11px] text-muted-foreground">
                          <span>{v.tamanho}</span>
                          <span aria-hidden>•</span>
                          <span>{v.uploaded_by ?? '—'}</span>
                          <span aria-hidden>•</span>
                          <span>{v.uploaded_em}</span>
                        </div>
                      </div>
                    </div>

                    <div className="flex shrink-0 gap-2">
                      {v.eh_pdf && (
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => setPreviewVersao(v)}
                          aria-label="Pré-visualizar"
                          className="hover:cursor-pointer"
                        >
                          Pré-visualizar
                        </Button>
                      )}
                      <Button
                        variant="outline"
                        size="sm"
                        asChild
                        className="hover:cursor-pointer"
                      >
                        <a
                          href={`/dashboard/planificacoes/versao/${v.id}/download`}
                          aria-label="Descarregar"
                        >
                          Descarregar
                        </a>
                      </Button>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        )}
      </div>

      {/* Dialog preview PDF */}
      <Dialog
        open={!!previewVersao}
        onOpenChange={(o) => !o && setPreviewVersao(null)}
      >
        <DialogContent className="max-w-4xl">
          <DialogHeader>
            <DialogTitle className="truncate">
              {previewVersao?.nome_original}
            </DialogTitle>
            <DialogDescription>
              Pré-visualização do documento
            </DialogDescription>
          </DialogHeader>
          {previewVersao && (
            <div className="h-[70vh] w-full overflow-hidden border bg-muted/30">
              <iframe
                src={`/dashboard/planificacoes/versao/${previewVersao.id}/preview`}
                className="h-full w-full"
                title="Pré-visualização"
              />
            </div>
          )}
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setPreviewVersao(null)}
              className="hover:cursor-pointer"
            >
              Fechar
            </Button>
            {previewVersao && (
              <Button asChild className="hover:cursor-pointer">
                <a
                  href={`/dashboard/planificacoes/versao/${previewVersao.id}/download`}
                >
                  Descarregar
                </a>
              </Button>
            )}
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Dialog Remover */}
      <Dialog open={deleteDialog} onOpenChange={setDeleteDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Remover planificação?</DialogTitle>
            <DialogDescription>
              Todas as versões serão removidas permanentemente. Esta ação não
              pode ser desfeita.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setDeleteDialog(false)}
              className="hover:cursor-pointer"
            >
              Cancelar
            </Button>
            <Button
              variant="outline"
              onClick={handleDelete}
              disabled={deleting}
              className="hover:cursor-pointer"
            >
              {deleting ? (
                <>
                  <Spinner />
                  Apagando...
                </>
              ) : (
                'Remover definitivamente'
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}