import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import {
  BookOpen,
  CalendarDays,
  CheckCircle2,
  Clock,
  Eye,
  FileText,
  GraduationCap,
  Lock,
  NotebookPen,
  Pencil,
  Send,
  Trash2,
  UserCheck,
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
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
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
import {
  Field,
  FieldLabel,
} from '@/components/ui/field';
import { Spinner } from '@/components/spinner';

interface Aula {
  id: string;
  data: string;
  data_humana: string;
  hora_inicio: string;
  hora_fim: string;
  turma_nome: string;
  disciplina_nome: string;
  professor_nome: string | null;
  professor_original_nome: string | null;
  tipo: 'normal' | 'substituicao' | 'extra' | 'reposicao';
  tipo_label: string;
  titulo: string | null;
  conteudo_previsto: string | null;
  sumario: string | null;
  tpc: string | null;
  observacoes: string | null;
  estado: 'rascunho' | 'publicado' | 'cancelado';
  estado_label: string;
  publicado_em: string | null;
  esta_editavel: boolean;
  horas_restantes_edicao: number | null;
  tem_sumario: boolean;
  urls: {
    ver: string;
    editar: string;
    publicar: string;
    cancelar: string;
    apagar: string;
  };
}

interface Props {
  aula: Aula;
  podeEditar?: boolean;
  url_voltar?: string;
  podePublicar?: boolean;
  podeCancelar?: boolean;
  podeApagar?: boolean;
}

export default function Show({
  aula,
  podeEditar = false,
  url_voltar = '/dashboard/aulas',
  podePublicar = false,
  podeCancelar = false,
  podeApagar = false,
}: Props) {
  const [cancelarDialog, setCancelarDialog] = useState(false);
  const [apagarDialog, setApagarDialog] = useState(false);
  const [motivoCancelamento, setMotivoCancelamento] = useState('');
  const [processando, setProcessando] = useState(false);

  const cancelada = aula.estado === 'cancelado';
  const publicado = aula.estado === 'publicado';
  const rascunho = aula.estado === 'rascunho';

  const publicar = () => {
    setProcessando(true);
    router.post(
      aula.urls.publicar,
      {},
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success('Aula publicada.');
          setProcessando(false);
        },
        onError: () => {
          toast.error('Erro ao publicar.');
          setProcessando(false);
        },
      },
    );
  };

  const confirmarCancelamento = () => {
    setProcessando(true);
    router.post(
      aula.urls.cancelar,
      { observacoes: motivoCancelamento },
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success('Aula cancelada.');
          setCancelarDialog(false);
          setMotivoCancelamento('');
          setProcessando(false);
        },
        onError: () => {
          toast.error('Erro ao cancelar.');
          setProcessando(false);
        },
      },
    );
  };

  const confirmarApagar = () => {
    setProcessando(true);
    router.delete(aula.urls.apagar, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Rascunho removido.');
        setProcessando(false);
      },
      onError: () => {
        toast.error('Erro ao remover.');
        setProcessando(false);
      },
    });
  };

  const temAcoes = podePublicar || podeCancelar || podeApagar;

  return (
    <>
      <Head title={aula.titulo ?? 'Aula'} />

      <div className="mx-auto w-full max-w-5xl space-y-4 p-4 sm:p-6">
        {/* Breadcrumb */}
        <Breadcrumb>
          <BreadcrumbList className="flex-wrap">
            <BreadcrumbItem className="min-w-0">
              <BreadcrumbLink asChild>
                <Link
                  href={url_voltar}
                  className="line-clamp-2 text-sm font-semibold text-primary hover:underline"
                >
                  Aulas
                </Link>
              </BreadcrumbLink>
            </BreadcrumbItem>
            <BreadcrumbSeparator />
            <BreadcrumbItem className="min-w-0">
              <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                {aula.titulo ?? 'Detalhe da aula'}
              </BreadcrumbPage>
            </BreadcrumbItem>
          </BreadcrumbList>
        </Breadcrumb>

        {/* Card principal: Hero + Meta + Acções */}
        <Card className="overflow-hidden pt-0!">
          <div className="relative flex h-56 w-full items-end bg-gradient-to-br from-primary/20 via-primary/10 to-muted">
            <div className="absolute inset-0 bg-black/40" aria-hidden />

            <div className="relative z-10 space-y-2 p-6 text-white">
              <div className="flex flex-wrap items-center gap-2">
                <Badge
                  variant={
                    cancelada
                      ? 'destructive'
                      : publicado
                        ? 'default'
                        : 'secondary'
                  }
                  className={
                    publicado
                      ? 'bg-green-600 text-[10px] text-white hover:bg-green-600'
                      : 'border-white/20 bg-white/10 text-[10px] text-white backdrop-blur-sm'
                  }
                >
                  {cancelada && <XCircle className="mr-1 size-3" />}
                  {publicado && <CheckCircle2 className="mr-1 size-3" />}
                  {aula.estado_label}
                </Badge>

                {aula.tipo !== 'normal' && (
                  <Badge
                    variant="outline"
                    className="border-white/20 bg-white/10 text-[10px] text-white backdrop-blur-sm"
                  >
                    {aula.tipo_label}
                  </Badge>
                )}

                {publicado && aula.horas_restantes_edicao !== null && (
                  <Badge
                    variant="outline"
                    className="gap-1 border-white/20 bg-white/10 text-[10px] text-white backdrop-blur-sm"
                  >
                    <Clock className="size-3" />
                    {aula.horas_restantes_edicao}h para editar
                  </Badge>
                )}

                {publicado && !aula.esta_editavel && (
                  <Badge
                    variant="outline"
                    className="gap-1 border-white/20 bg-white/10 text-[10px] text-white backdrop-blur-sm"
                  >
                    <Lock className="size-3" />
                    Bloqueado
                  </Badge>
                )}
              </div>

              <h1 className="text-2xl font-semibold wrap-break-word md:text-3xl">
                {aula.titulo ?? aula.disciplina_nome}
              </h1>

              <p className="text-sm break-all opacity-90">
                {aula.disciplina_nome} · {aula.turma_nome} · {aula.hora_inicio}
                –{aula.hora_fim}
              </p>
            </div>
          </div>

          <CardContent className="grid grid-cols-1 gap-6 py-6 md:grid-cols-3">
            <div>
              <p className="text-sm text-muted-foreground">Data</p>
              <p className="font-medium capitalize">{aula.data_humana}</p>
            </div>

            <div>
              <p className="text-sm text-muted-foreground">Hora</p>
              <p className="font-mono font-medium">
                {aula.hora_inicio} – {aula.hora_fim}
              </p>
            </div>

            <div>
              <p className="text-sm text-muted-foreground">Professor</p>
              <p className="truncate font-medium">
                {aula.professor_nome ?? '—'}
                {aula.professor_original_nome &&
                  ` (original: ${aula.professor_original_nome})`}
              </p>
            </div>

            <div>
              <p className="text-sm text-muted-foreground">Disciplina</p>
              <p className="font-medium">{aula.disciplina_nome}</p>
            </div>

            <div>
              <p className="text-sm text-muted-foreground">Turma</p>
              <p className="font-medium">{aula.turma_nome}</p>
            </div>

            {aula.publicado_em && (
              <div>
                <p className="text-sm text-muted-foreground">Publicado em</p>
                <p className="font-medium">{aula.publicado_em}</p>
              </div>
            )}
          </CardContent>

          {/* Footer de acções — professor */}
          {temAcoes && (
            <div className="flex flex-wrap items-center justify-end gap-2 border-t px-4 py-3">
              {podeEditar && aula.esta_editavel && !cancelada && (
                <Button
                  asChild
                  variant="outline"
                  className="hover:cursor-pointer"
                >
                  <Link href={aula.urls.editar}>
                    <Pencil className="mr-1.5 size-4" />
                    Editar
                  </Link>
                </Button>
              )}

              {podePublicar && rascunho && (
                <Button
                  onClick={publicar}
                  disabled={processando}
                  className="hover:cursor-pointer"
                >
                  {processando ? (
                    <>
                      <Spinner />
                      A publicar…
                    </>
                  ) : (
                    <>
                      <Send className="mr-1.5 size-4" />
                      Publicar para alunos
                    </>
                  )}
                </Button>
              )}

              {podeCancelar && !cancelada && (
                <Button
                  variant="ghost"
                  onClick={() => setCancelarDialog(true)}
                  disabled={processando}
                  className="text-destructive hover:bg-destructive/10 hover:text-destructive hover:cursor-pointer"
                >
                  <XCircle className="mr-1.5 size-4" />
                  Cancelar aula
                </Button>
              )}

              {podeApagar && rascunho && (
                <Button
                  variant="ghost"
                  onClick={() => setApagarDialog(true)}
                  disabled={processando}
                  className="text-destructive hover:bg-destructive/10 hover:text-destructive hover:cursor-pointer"
                >
                  <Trash2 className="mr-1.5 size-4" />
                  Apagar rascunho
                </Button>
              )}
            </div>
          )}
        </Card>

        {/* Avisos */}
        {cancelada && (
          <Card className="gap-0 border-red-200 pb-0 dark:border-red-800">
            <CardContent className="flex items-start gap-3 p-4">
              <XCircle className="size-5 shrink-0 text-red-600 dark:text-red-400" />
              <div className="text-sm">
                <p className="font-medium text-red-800 dark:text-red-300">
                  Esta aula foi cancelada.
                </p>
                {aula.observacoes && (
                  <p className="mt-1 text-red-700 dark:text-red-400">
                    {aula.observacoes}
                  </p>
                )}
              </div>
            </CardContent>
          </Card>
        )}

        {rascunho && (
          <Card className="gap-0 border-amber-200 pb-0 dark:border-amber-800">
            <CardContent className="flex items-start gap-3 p-4">
              <FileText className="size-5 shrink-0 text-amber-600 dark:text-amber-400" />
              <div className="text-sm">
                <p className="font-medium text-amber-800 dark:text-amber-300">
                  Rascunho — ainda não visível aos alunos.
                </p>
                <p className="mt-1 text-amber-700 dark:text-amber-400">
                  Publica para que os alunos possam consultar.
                </p>
              </div>
            </CardContent>
          </Card>
        )}

        {/* Conteúdo previsto */}
        {aula.conteudo_previsto && (
          <Card className="gap-0 pb-0">
            <CardHeader className="border-b">
              <div className="flex items-center gap-3">
                <div className="flex size-9 items-center justify-center bg-primary/10">
                  <NotebookPen className="size-4 text-primary" />
                </div>
                <div>
                  <CardTitle className="text-base">Conteúdo previsto</CardTitle>
                  <CardDescription>
                    O que estava planeado dar nesta aula.
                  </CardDescription>
                </div>
              </div>
            </CardHeader>
            <CardContent className="pt-4">
              <p className="whitespace-pre-wrap text-sm">
                {aula.conteudo_previsto}
              </p>
            </CardContent>
          </Card>
        )}

        {/* Sumário */}
        {aula.tem_sumario ? (
          <Card className="gap-0 border-primary/30 pb-0">
            <CardHeader className="border-b bg-primary/5">
              <div className="flex items-center gap-3">
                <div className="flex size-9 items-center justify-center bg-primary/15">
                  <FileText className="size-4 text-primary" />
                </div>
                <div>
                  <CardTitle className="text-base">Sumário</CardTitle>
                  <CardDescription>
                    Registo do que foi dado nesta aula.
                  </CardDescription>
                </div>
              </div>
            </CardHeader>
            <CardContent className="pt-4">
              <p className="whitespace-pre-wrap text-sm leading-relaxed">
                {aula.sumario}
              </p>
            </CardContent>
          </Card>
        ) : (
          !cancelada && (
            <Card className="gap-0 border-dashed pb-0">
              <CardContent className="py-8 text-center text-sm text-muted-foreground">
                <FileText className="mx-auto mb-2 size-8 opacity-50" />
                Sumário ainda não escrito.
              </CardContent>
            </Card>
          )
        )}

        {/* TPC */}
        {aula.tpc && (
          <Card className="gap-0 border-amber-300/50 pb-0">
            <CardHeader className="border-b bg-amber-50/50 dark:bg-amber-950/20">
              <div className="flex items-center gap-3">
                <div className="flex size-9 items-center justify-center bg-amber-100 dark:bg-amber-950/40">
                  <BookOpen className="size-4 text-amber-700 dark:text-amber-400" />
                </div>
                <div>
                  <CardTitle className="text-base text-amber-700 dark:text-amber-400">
                    Trabalho para casa
                  </CardTitle>
                  <CardDescription>TPC desta aula.</CardDescription>
                </div>
              </div>
            </CardHeader>
            <CardContent className="pt-4">
              <p className="whitespace-pre-wrap text-sm">{aula.tpc}</p>
            </CardContent>
          </Card>
        )}

        {/* Observações internas */}
        {aula.observacoes && !cancelada && (
          <Card className="gap-0 border-muted pb-0">
            <CardHeader className="border-b">
              <div className="flex items-center gap-3">
                <div className="flex size-9 items-center justify-center bg-muted">
                  <Lock className="size-4 text-muted-foreground" />
                </div>
                <div>
                  <CardTitle className="flex items-center gap-2 text-base text-muted-foreground">
                    Observações internas
                    <Badge variant="outline" className="text-[10px]">
                      não visível ao aluno
                    </Badge>
                  </CardTitle>
                  <CardDescription>
                    Notas internas do professor.
                  </CardDescription>
                </div>
              </div>
            </CardHeader>
            <CardContent className="pt-4">
              <p className="whitespace-pre-wrap text-sm text-muted-foreground">
                {aula.observacoes}
              </p>
            </CardContent>
          </Card>
        )}
      </div>

      {/* Dialog: cancelar aula */}
      <Dialog open={cancelarDialog} onOpenChange={setCancelarDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <XCircle className="size-4 text-destructive" />
              Cancelar esta aula?
            </DialogTitle>
            <DialogDescription>
              A aula será marcada como cancelada. Os alunos não a verão.
            </DialogDescription>
          </DialogHeader>

          <Field>
            <FieldLabel htmlFor="motivo">Motivo (opcional)</FieldLabel>
            <Textarea
              id="motivo"
              rows={3}
              value={motivoCancelamento}
              onChange={(e) => setMotivoCancelamento(e.target.value)}
              placeholder="Ex: Feriado, falta do professor, greve…"
            />
          </Field>

          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setCancelarDialog(false)}
              className="hover:cursor-pointer"
            >
              Voltar
            </Button>
            <Button
              variant="destructive"
              onClick={confirmarCancelamento}
              disabled={processando}
              className="hover:cursor-pointer"
            >
              {processando ? (
                <>
                  <Spinner />
                  A cancelar…
                </>
              ) : (
                'Cancelar aula'
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Dialog: apagar rascunho */}
      <Dialog open={apagarDialog} onOpenChange={setApagarDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <Trash2 className="size-4 text-destructive" />
              Apagar rascunho?
            </DialogTitle>
            <DialogDescription>
              Esta ação não pode ser desfeita.
            </DialogDescription>
          </DialogHeader>

          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setApagarDialog(false)}
              className="hover:cursor-pointer"
            >
              Voltar
            </Button>
            <Button
              variant="destructive"
              onClick={confirmarApagar}
              disabled={processando}
              className="hover:cursor-pointer"
            >
              {processando ? (
                <>
                  <Spinner />
                  A apagar…
                </>
              ) : (
                'Apagar definitivamente'
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}