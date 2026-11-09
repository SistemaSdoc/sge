import { Link, useForm } from '@inertiajs/react';
import {
  ArrowLeft,
  ArrowUpLeft,
  CheckCircle,
  MessageSquareText,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

const CONFIG = {
  coordenacao: {
    titulo: 'Enviar tema para a coordenação',
    descricao:
      'Revise a proposta do grupo antes de a encaminhar para análise da coordenação.',
    campo: 'comentario',
    campoLabel: 'Comentário',
    placeholder: 'Adicione um comentário opcional para a coordenação...',
    confirmacao: 'Enviar para coordenação',
    icon: CheckCircle,
  },
  melhoria: {
    titulo: 'Solicitar melhoria do tema',
    descricao:
      'Descreva com detalhe as alterações que o grupo deverá realizar antes de reenviar o tema.',
    campo: 'recomendacao',
    campoLabel: 'Recomendações de melhoria',
    placeholder: 'Descreva as recomendações de melhoria...',
    confirmacao: 'Solicitar melhoria',
    icon: MessageSquareText,
  },
};

function CampoTema({ label, value }) {
  return (
    <div className="space-y-2">
      <p className="text-xs font-bold uppercase text-muted-foreground">
        {label}
      </p>
      <div className="mt-1 text-sm font-medium">{value || '—'}</div>
    </div>
  );
}

export default function FormularioDecisaoTutor({
  grupoPap,
  turma,
  submitUrl,
  backUrl,
  tipo,
}) {
  const config = CONFIG[tipo];
  const Icon = config.icon;
  const { data, setData, post, processing, errors } = useForm({
    [config.campo]: '',
  });
  const comentario = data[config.campo];

  const submeter = (event) => {
    event.preventDefault();
    post(submitUrl);
  };

  return (
    <main className="w-full max-w-5xl p-4 mx-auto space-y-6 md:p-8">
      <Card className="gap-0 pb-0 overflow-visible">
        <CardHeader className="pb-3">
          <div className="min-w-0 space-y-2">
            <Breadcrumb>
              <BreadcrumbList className="flex-wrap">
                <BreadcrumbItem className="min-w-0">
                  <BreadcrumbLink asChild>
                    <Link
                      href={backUrl}
                      className="text-sm font-semibold line-clamp-2 text-primary"
                    >
                      {grupoPap.nome_grupo}
                    </Link>
                  </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator />
                <BreadcrumbItem className="min-w-0">
                  <BreadcrumbPage className="text-sm font-semibold line-clamp-2 text-secondary">
                    {config.titulo}
                  </BreadcrumbPage>
                </BreadcrumbItem>
              </BreadcrumbList>
            </Breadcrumb>

            <CardDescription>
              {turma?.nome && (
                <>
                  Turma <span className="font-bold">{turma.nome}</span>
                  {' · '}
                </>
              )}
              {config.descricao}
            </CardDescription>
          </div>
          <CardAction>
            <Button asChild variant="outline" size="sm">
              <Link href={backUrl}>
                <ArrowUpLeft className="size-4" />
                Voltar ao grupo PAP
              </Link>
            </Button>
          </CardAction>
        </CardHeader>
      </Card>

      <Card>
        <CardHeader className="border-b">
          <CardTitle>Proposta do grupo</CardTitle>
          <CardDescription>
            Consulte o tema, o problema e os objectivos antes de decidir.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          <CampoTema label="Tema" value={grupoPap.tema_grupo} />
          <CampoTema label="Problema" value={grupoPap.problema} />
          <CampoTema label="Objectivos" value={grupoPap.objectivos} />
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="border-b">
          <CardTitle>{config.campoLabel}</CardTitle>
          <CardDescription>
            {tipo === 'melhoria'
              ? 'A recomendação será partilhada com o grupo e ficará registada no histórico.'
              : 'O comentário é opcional e ficará registado no histórico da aprovação.'}
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={submeter} className="space-y-5">
            <div className="space-y-2">
              <Label htmlFor={config.campo}>
                {config.campoLabel}
                {tipo === 'melhoria' ? ' *' : ' (opcional)'}
              </Label>
              <Textarea
                id={config.campo}
                rows={14}
                maxLength={2000}
                className="text-base leading-7 resize-y min-h-72"
                value={comentario}
                onChange={(event) => setData(config.campo, event.target.value)}
                placeholder={config.placeholder}
                disabled={processing}
                required={tipo === 'melhoria'}
                aria-invalid={Boolean(errors[config.campo])}
              />
              <div className="flex flex-wrap justify-between gap-2 text-sm">
                {errors[config.campo] && (
                  <p className="text-sm text-destructive">
                    {errors[config.campo]}
                  </p>
                )}
              </div>
            </div>

            <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
              <Button asChild variant="outline" disabled={processing}>
                <Link href={backUrl}>Cancelar</Link>
              </Button>

              <Button
                type="submit"
                disabled={
                  processing ||
                  (tipo === 'melhoria' && comentario.trim().length < 10)
                }
              >
                {processing ? 'A processar...' : config.confirmacao}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </main>
  );
}
