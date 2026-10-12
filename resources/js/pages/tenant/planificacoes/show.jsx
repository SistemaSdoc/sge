import { Head, Link, router } from '@inertiajs/react';
import {
  ArrowLeft,
  Download,
  Eye,
  FileText,
  Calendar,
  BookOpen,
  GraduationCap,
  Clock,
  User,
  Trash2,
  History,
} from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Card,
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
import { useState } from 'react';

export default function Show({ planificacao, can = {} }) {
  const [previewVersao, setPreviewVersao] = useState(null);
  const [deleteDialog, setDeleteDialog] = useState(false);
  const [deleting, setDeleting] = useState(false);

  const versaoAtual =
    planificacao.versoes.find((v) => v.is_atual) ?? planificacao.versoes[0];

  const handleDelete = () => {
    setDeleting(true);
    router.delete(`/dashboard/planificacoes/${planificacao.id}`, {
      onFinish: () => setDeleting(false),
    });
  };

  return (
    <>
      <Head title={planificacao.titulo ?? 'Planificação'} />

      <div className="mx-auto w-full max-w-5xl space-y-6 p-4 sm:p-6">
        {/* Voltar */}
        <Button variant="ghost" size="sm" asChild>
          <Link href="/dashboard/planificacoes">
            <ArrowLeft className="mr-1 size-4" />
            Voltar às planificações
          </Link>
        </Button>

        {/* Header */}
        <Card>
          <CardHeader className="border-b">
            <div className="flex flex-wrap items-start justify-between gap-3">
              <div className="min-w-0">
                <div className="mb-2 flex flex-wrap items-center gap-2">
                  <PeriodoBadge periodo={planificacao.periodo} />
                  <Badge variant="outline">v{planificacao.versao_atual}</Badge>
                </div>
                <CardTitle className="text-xl">
                  {planificacao.titulo ?? 'Planificação'}
                </CardTitle>
                {planificacao.descricao && (
                  <CardDescription className="mt-2">
                    {planificacao.descricao}
                  </CardDescription>
                )}
              </div>

              {can.delete && (
                <Button
                  variant="destructive"
                  size="sm"
                  onClick={() => setDeleteDialog(true)}
                >
                  <Trash2 className="mr-1 size-4" />
                  Apagar
                </Button>
              )}
            </div>
          </CardHeader>

          <CardContent className="grid grid-cols-1 gap-4 pt-6 sm:grid-cols-2">
            <div className="flex items-center gap-2 text-sm">
              <BookOpen className="size-4 text-muted-foreground" />
              <span className="font-medium">Disciplina:</span>
              <span>{planificacao.disciplina?.nome ?? '—'}</span>
            </div>

            <div className="flex items-center gap-2 text-sm">
              <GraduationCap className="size-4 text-muted-foreground" />
              <span className="font-medium">Classe:</span>
              <span>{planificacao.classe?.nome ?? '—'}</span>
            </div>

            <div className="flex items-center gap-2 text-sm">
              <Calendar className="size-4 text-muted-foreground" />
              <span className="font-medium">Ano letivo:</span>
              <span>{planificacao.ano_letivo?.nome ?? '—'}</span>
            </div>

            <div className="flex items-center gap-2 text-sm">
              <User className="size-4 text-muted-foreground" />
              <span className="font-medium">Publicada por:</span>
              <span>{planificacao.criada_por ?? '—'}</span>
            </div>

            <div className="flex items-center gap-2 text-sm">
              <Clock className="size-4 text-muted-foreground" />
              <span className="font-medium">Atualizada em:</span>
              <span>{planificacao.atualizada_em}</span>
            </div>
          </CardContent>
        </Card>

        {/* Versão atual */}
        {versaoAtual && (
          <Card>
            <CardHeader>
              <CardTitle className="text-base">
                Versão atual (v{versaoAtual.versao})
              </CardTitle>
              <CardDescription>{versaoAtual.nome_original}</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="flex flex-wrap gap-2">
                {versaoAtual.eh_pdf && (
                  <Button
                    variant="outline"
                    onClick={() => setPreviewVersao(versaoAtual)}
                  >
                    <Eye className="mr-1.5 size-4" />
                    Pré-visualizar
                  </Button>
                )}
                <Button asChild>
                  <a
                    href={`/dashboard/planificacoes/versao/${versaoAtual.id}/download`}
                  >
                    <Download className="mr-1.5 size-4" />
                    Descarregar
                  </a>
                </Button>
              </div>
            </CardContent>
          </Card>
        )}

        {/* Histórico de versões */}
        {planificacao.versoes.length > 1 && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-base">
                <History className="size-4" />
                Histórico de versões
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
              {planificacao.versoes.map((v) => (
                <div
                  key={v.id}
                  className="flex flex-wrap items-center justify-between gap-3 rounded border p-3"
                >
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                      <Badge
                        variant={v.is_atual ? 'default' : 'outline'}
                        className="text-[10px]"
                      >
                        v{v.versao}
                      </Badge>
                      {v.is_atual && (
                        <span className="text-xs font-medium text-primary">
                          Atual
                        </span>
                      )}
                    </div>
                    <p className="mt-1 truncate text-sm font-medium">
                      {v.nome_original}
                    </p>
                    <p className="text-xs text-muted-foreground">
                      {v.tamanho} • {v.uploaded_by ?? '—'} • {v.uploaded_em}
                    </p>
                  </div>

                  <div className="flex gap-1">
                    {v.eh_pdf && (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => setPreviewVersao(v)}
                      >
                        <Eye className="size-4" />
                      </Button>
                    )}
                    <Button variant="ghost" size="sm" asChild>
                      <a href={`/dashboard/planificacoes/versao/${v.id}/download`}>
                        <Download className="size-4" />
                      </a>
                    </Button>
                  </div>
                </div>
              ))}
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
            <DialogTitle>{previewVersao?.nome_original}</DialogTitle>
            <DialogDescription>Pré-visualização do documento</DialogDescription>
          </DialogHeader>
          {previewVersao && (
            <div className="h-[70vh] w-full overflow-hidden rounded border">
              <iframe
                src={`/dashboard/planificacoes/versao/${previewVersao.id}/preview`}
                className="h-full w-full"
                title="Pré-visualização"
              />
            </div>
          )}
          <DialogFooter>
            <Button variant="outline" onClick={() => setPreviewVersao(null)}>
              Fechar
            </Button>
            {previewVersao && (
              <Button asChild>
                <a
                  href={`/dashboard/planificacoes/versao/${previewVersao.id}/download`}
                >
                  <Download className="mr-1.5 size-4" />
                  Descarregar
                </a>
              </Button>
            )}
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Dialog apagar */}
      <Dialog open={deleteDialog} onOpenChange={setDeleteDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Apagar planificação?</DialogTitle>
            <DialogDescription>
              Todas as versões serão removidas permanentemente. Esta ação não pode
              ser desfeita.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleteDialog(false)}>
              Cancelar
            </Button>
            <Button
              variant="destructive"
              onClick={handleDelete}
              disabled={deleting}
            >
              {deleting ? 'A apagar...' : 'Apagar definitivamente'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}