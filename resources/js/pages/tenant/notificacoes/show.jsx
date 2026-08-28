import { Head, router } from '@inertiajs/react';
import { ArrowLeft, Check, X } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  index,
  aprovarTutela,
  rejeitarTutela,
} from '@/actions/App/Http/Controllers/Tenant/NotificacaoController';

export default function Show({ notificacao }) {
  const isSolicitacaoTutela = notificacao.tipo === 'solicitacao_tutela';
  const isPendente = notificacao.dados?.status === 'pendente';

  return (
    <div className="mx-auto w-full max-w-3xl p-6">
      <Head title={notificacao.titulo || 'Notificação'} />

      <Link
        href={index().url}
        className="mb-6 inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
      >
        <ArrowLeft data-icon="inline-start" />
        Voltar às notificações
      </Link>

      <Card>
        <CardHeader>
          <div className="flex items-start justify-between gap-4">
            <div>
              <Badge variant="secondary">{notificacao.tipo}</Badge>
              <CardTitle className="mt-3">{notificacao.titulo}</CardTitle>
              <CardDescription className="mt-2">
                {notificacao.criada_em}
              </CardDescription>
            </div>
            {notificacao.lida && <Badge variant="outline">Lida</Badge>}
          </div>
        </CardHeader>
        <CardContent className="flex flex-col gap-6">
          <p className="text-sm leading-6">{notificacao.mensagem}</p>

          {Object.entries(notificacao.dados ?? {})
            .filter(([key]) => !['tipo', 'titulo', 'mensagem'].includes(key))
            .map(([key, value]) => (
              <div key={key} className="border-t pt-4">
                <p className="text-xs font-medium text-muted-foreground uppercase">
                  {key.replaceAll('_', ' ')}
                </p>
                <p className="mt-1 text-sm">{String(value)}</p>
              </div>
            ))}

          {isSolicitacaoTutela && isPendente && (
            <div className="flex flex-wrap gap-3 border-t pt-6">
              <Button
                type="button"
                onClick={() => router.post(aprovarTutela(notificacao.id).url)}
              >
                <Check data-icon="inline-start" />
                Aprovar tutela
              </Button>
              <Button
                type="button"
                variant="outline"
                onClick={() => router.post(rejeitarTutela(notificacao.id).url)}
              >
                <X data-icon="inline-start" />
                Rejeitar solicitação
              </Button>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
