import { Head, Link, router } from '@inertiajs/react';
import { ArrowUpRight, Bell, CheckCheck, Info } from 'lucide-react';
import {
  Alert,
  AlertAction,
  AlertDescription,
  AlertTitle,
} from '@/components/ui/alert';
import { Frame, FramePanel } from '@/components/ui/frame';
import { Button } from '@/components/ui/button';
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty';
import {
  index,
  marcarTodasLidas,
  show,
} from '@/actions/App/Http/Controllers/Tenant/NotificacaoController';

export default function Index({ notificacoes, naoLidas = 0 }) {
  const marcarTodas = () => {
    if (naoLidas === 0) {
      return;
    }

    router.post(marcarTodasLidas().url, {}, { preserveScroll: true });
  };

  return (
    <div className="mx-auto w-full max-w-4xl p-6">
      <Head title="Notificações" />

      <div className="mb-6 flex items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">
            Notificações
          </h1>
          <p className="text-sm text-muted-foreground">
            Consulte as novidades e solicitações da instituição.
          </p>
        </div>
        {naoLidas > 0 && (
          <Button type="button" variant="outline" onClick={marcarTodas}>
            <CheckCheck data-icon="inline-start" />
            Marcar todas como lidas
          </Button>
        )}
      </div>

      {!notificacoes?.data?.length ? (
        <Frame>
          <FramePanel className="p-0!">
            <Empty>
              <EmptyHeader>
                <EmptyMedia variant="icon">
                  <Bell />
                </EmptyMedia>
                <EmptyTitle>Sem notificações</EmptyTitle>
                <EmptyDescription>
                  Quando houver novidades, elas aparecerão aqui.
                </EmptyDescription>
              </EmptyHeader>
            </Empty>
          </FramePanel>
        </Frame>
      ) : (
        <div className="flex flex-col gap-1">
          {notificacoes.data.map((notificacao) => (
            <Alert
              key={notificacao.id}
              variant={notificacao.lida ? 'default' : 'info'}
              className={notificacao.lida ? 'opacity-820' : ''}
            >
              <AlertTitle>{notificacao.titulo}</AlertTitle>
              <AlertAction>
                <Button asChild size="xs">
                  <Link href={show(notificacao.id).url}>
                    Ver detalhes
                    <ArrowUpRight />
                  </Link>
                </Button>
              </AlertAction>
              <AlertDescription>
                <p>{notificacao.mensagem}</p>
                <p className="text-xs">{notificacao.criada_em}</p>
              </AlertDescription>
            </Alert>
          ))}
        </div>
      )}

      {notificacoes?.last_page > 1 && (
        <div className="mt-4 flex justify-center gap-2">
          {Array.from(
            { length: notificacoes.last_page },
            (_, page) => page + 1,
          ).map((page) => (
            <Button
              key={page}
              type="button"
              variant={
                page === notificacoes.current_page ? 'default' : 'outline'
              }
              size="sm"
              onClick={() =>
                router.get(index().url, { page }, { preserveScroll: true })
              }
            >
              {page}
            </Button>
          ))}
        </div>
      )}
    </div>
  );
}
