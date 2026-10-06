import { Head, Link, router } from '@inertiajs/react';
import { ArrowUpRight, Bell, CheckCheck } from 'lucide-react';
import {
  Alert,
  AlertAction,
  AlertDescription,
  AlertTitle,
} from '@/components/ui/alert';
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
    <div className="mx-auto w-full max-w-4xl px-4 py-4 sm:px-6 sm:py-6">
      <Head title="Notificações" />

      <div className="mb-5 flex flex-col items-stretch gap-3 sm:mb-6 sm:flex-row sm:items-center sm:justify-between">
        <div className="min-w-0">
          <h1 className="text-xl font-semibold tracking-tight sm:text-2xl">
            Notificações
          </h1>
          <p className="mt-1 text-xs leading-relaxed text-muted-foreground sm:text-sm">
            Consulte as novidades e solicitações da instituição.
          </p>
        </div>
        {naoLidas > 0 && (
          <Button
            type="button"
            variant="outline"
            className="w-full sm:w-auto"
            onClick={marcarTodas}
          >
            <CheckCheck data-icon="inline-start" />
            Marcar todas como lidas
          </Button>
        )}
      </div>

      {!notificacoes?.data?.length ? (
        <Empty className="p-4 sm:p-6">
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
      ) : (
        <div className="flex min-w-0 flex-col gap-2 sm:gap-1">
          {notificacoes.data.map((notificacao) => (
            <Alert
              key={notificacao.id}
              variant={notificacao.lida ? 'default' : 'info'}
              className={`min-w-0 grid-cols-1 gap-x-0 sm:grid-cols-[minmax(0,1fr)_auto] sm:gap-x-4 ${notificacao.lida ? 'opacity-820' : ''}`}
            >
              <AlertTitle className="col-start-1 line-clamp-none min-w-0 wrap-break-word">
                {notificacao.titulo}
              </AlertTitle>
              <AlertAction className="max-sm:col-start-1 max-sm:mt-1 sm:col-start-2 sm:justify-self-end">
                <Button asChild size="xs">
                  <Link href={show(notificacao.id).url}>
                    Ver detalhes
                    <ArrowUpRight />
                  </Link>
                </Button>
              </AlertAction>
              <AlertDescription className="col-start-1 min-w-0 wrap-anywhere">
                <p>{notificacao.mensagem}</p>
                <p className="text-xs">{notificacao.criada_em}</p>
              </AlertDescription>
            </Alert>
          ))}
        </div>
      )}

      {notificacoes?.last_page > 1 && (
        <nav
          className="mt-4 flex max-w-full flex-wrap justify-center gap-1"
          aria-label="Paginação das notificações"
        >
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
              aria-current={
                page === notificacoes.current_page ? 'page' : undefined
              }
              onClick={() =>
                router.get(index().url, { page }, { preserveScroll: true })
              }
            >
              {page}
            </Button>
          ))}
        </nav>
      )}
    </div>
  );
}
