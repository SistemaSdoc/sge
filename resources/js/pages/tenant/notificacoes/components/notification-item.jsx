import { Link, router } from '@inertiajs/react';
import { ArrowUpRight, Check, Dot } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  markAsRead,
  show,
} from '@/actions/App/Http/Controllers/Tenant/NotificacaoController';

export function NotificationItem({ notificacao }) {
  const handleMarcarLida = () => {
    router.post(markAsRead(notificacao.id).url, {}, { preserveScroll: true });
  };

  return (
    <article className="flex min-w-0 flex-col gap-3 py-4 sm:flex-row sm:items-start sm:justify-between">
      <div className="min-w-0 flex-1">
        <div className="mb-2 flex min-w-0 flex-wrap items-center">
          <h3 className="text-sm font-semibold wrap-break-word text-foreground">
            {notificacao.titulo}{' '}
          </h3>

          <Dot className="text-secondary" />

          <time className="text-xs text-muted-foreground">
            {notificacao.criada_em}
          </time>
        </div>

        <p className="text-sm leading-relaxed wrap-anywhere text-muted-foreground">
          {notificacao.mensagem}
        </p>
      </div>

      <div className="flex shrink-0 flex-wrap items-center gap-x-2 gap-y-1">
        {!notificacao.lida && (
          <Button
            type="button"
            variant="outline"
            size="xs"
            className="justify-start hover:cursor-pointer"
            onClick={handleMarcarLida}
          >
            <Check data-icon="inline-start" />
            Marcar como lida
          </Button>
        )}

        <Button asChild variant="outline" size="xs" className="justify-start">
          <Link href={show(notificacao.id).url}>
            Ver detalhes
            <ArrowUpRight data-icon="inline-end" />
          </Link>
        </Button>
      </div>
    </article>
  );
}
