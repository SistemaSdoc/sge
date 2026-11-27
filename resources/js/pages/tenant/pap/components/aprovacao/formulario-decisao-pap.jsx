import { Link, useForm } from '@inertiajs/react';
import {
  ArrowUpLeft,
  CheckCircle,
  MessageSquareText,
  XCircle,
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
    campoDescricao:
      'O comentário é opcional e ficará registado no histórico da aprovação.',
    confirmacao: 'Enviar para coordenação',
    minimumLength: 0,
    required: false,
    icon: CheckCircle,
  },
  melhoria: {
    titulo: 'Solicitar melhoria do tema',
    descricao:
      'Descreva com detalhe as alterações que o grupo deverá realizar antes de reenviar o tema.',
    campo: 'recomendacao',
    campoLabel: 'Recomendações de melhoria',
    placeholder: 'Descreva as recomendações de melhoria...',
    campoDescricao:
      'A recomendação será partilhada com o grupo e ficará registada no histórico.',
    confirmacao: 'Solicitar melhoria',
    minimumLength: 10,
    required: true,
    icon: MessageSquareText,
  },
  melhoriaCoordenacao: {
    titulo: 'Solicitar melhoria do tema',
    descricao:
      'Descreva com detalhe as alterações que o grupo deverá realizar antes de reenviar o tema.',
    campo: 'recomendacao',
    campoLabel: 'Recomendações de melhoria',
    placeholder: 'Descreva as recomendações de melhoria...',
    campoDescricao:
      'A recomendação será partilhada com o grupo e ficará registada no histórico.',
    confirmacao: 'Solicitar melhoria',
    minimumLength: 10,
    required: true,
    icon: MessageSquareText,
  },
  aprovarCoordenacao: {
    titulo: 'Aprovar tema PAP',
    descricao:
      'O tema será aprovado e o grupo poderá avançar para a próxima fase da PAP.',
    campo: 'comentario',
    campoLabel: 'Comentário',
    placeholder: 'Comentário opcional...',
    campoDescricao:
      'O comentário é opcional e ficará registado no histórico da aprovação.',
    confirmacao: 'Aprovar tema',
    minimumLength: 0,
    required: false,
    icon: CheckCircle,
  },
  reprovarCoordenacao: {
    titulo: 'Reprovar tema PAP',
    descricao: 'Informe o motivo pelo qual o tema não foi aprovado.',
    campo: 'motivo',
    campoLabel: 'Motivo da reprovação',
    placeholder: 'Informe o motivo da reprovação...',
    campoDescricao:
      'O motivo será partilhado com o grupo e ficará registado no histórico.',
    confirmacao: 'Reprovar tema',
    minimumLength: 10,
    required: true,
    icon: XCircle,
  },
};

function CampoTema({ label, value }) {
  return (
    <div className="space-y-2">
      <p className="text-xs font-bold text-muted-foreground uppercase">
        {label}
      </p>
      <div className="mt-1 text-sm font-medium">{value || '—'}</div>
    </div>
  );
}

export default function FormularioDecisaoPap({
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
    <main className="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-8">
      <Card className="gap-0 overflow-visible pb-0">
        <CardHeader className="pb-3">
          <div className="min-w-0 space-y-2">
            <Breadcrumb>
              <BreadcrumbList className="flex-wrap">
                <BreadcrumbItem className="min-w-0">
                  <BreadcrumbLink asChild>
                    <Link
                      href={backUrl}
                      className="line-clamp-2 text-sm font-semibold text-primary"
                    >
                      {grupoPap.nome_grupo}
                    </Link>
                  </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator />
                <BreadcrumbItem className="min-w-0">
                  <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
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
          <CampoTema label="Objectivo Geral" value={grupoPap.objectivo_geral} />
          <CampoTema
            label="Objectivos Específicos"
            value={grupoPap.objectivos_especificos}
          />
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="border-b">
          <CardTitle>{config.campoLabel}</CardTitle>
          <CardDescription>{config.campoDescricao}</CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={submeter} className="space-y-5">
            <div className="space-y-2">
              <Label htmlFor={config.campo}>
                {config.campoLabel}
                {config.required ? ' *' : ' (opcional)'}
              </Label>
              <Textarea
                id={config.campo}
                rows={14}
                maxLength={2000}
                className="min-h-72 resize-y text-base leading-7"
                value={comentario}
                onChange={(event) => setData(config.campo, event.target.value)}
                placeholder={config.placeholder}
                disabled={processing}
                required={config.required}
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
                  (config.required &&
                    comentario.trim().length < config.minimumLength)
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
