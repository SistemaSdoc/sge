import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty';
import { Bell, BellDot } from 'lucide-react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { NotificationItem } from './notification-item';
import { EmptyState } from '@/components/empty-state';

export function NotificationsList({ notificacoes = [] }) {
  const naoLidas = notificacoes.filter((notificacao) => !notificacao.lida);
  const lidas = notificacoes.filter((notificacao) => notificacao.lida);

  return (
    <Tabs
      defaultValue={naoLidas.length > 0 ? 'nao-lidas' : 'lidas'}
      className="mx-auto w-full max-w-7xl"
    >
      <TabsList className="h-auto w-auto md:w-md">
        <TabsTrigger value="nao-lidas" className="hover:cursor-pointer">
          Não lidas{' '}
          <span className="font-bold text-secondary">{naoLidas.length}</span>
        </TabsTrigger>

        <TabsTrigger value="lidas" className="hover:cursor-pointer">
          Lidas
          <span className="font-bold text-secondary">{lidas.length}</span>
        </TabsTrigger>
      </TabsList>

      <TabsContent value="nao-lidas" className="min-w-0 px-3 sm:px-6">
        {naoLidas.length > 0 ? (
          <div className="divide-y divide-border">
            {naoLidas.map((notificacao) => (
              <NotificationItem
                key={notificacao.id}
                notificacao={notificacao}
              />
            ))}
          </div>
        ) : (
          <EmptyState
            icon={BellDot}
            title="Sem notificações por ler"
            description="As notificações que receberes aparecerão aqui."
            variant="table"
          />
        )}
      </TabsContent>

      <TabsContent value="lidas" className="min-w-0 px-3 sm:px-6">
        {lidas.length > 0 ? (
          <div className="divide-y divide-border">
            {lidas.map((notificacao) => (
              <NotificationItem
                key={notificacao.id}
                notificacao={notificacao}
              />
            ))}
          </div>
        ) : (
          <EmptyState
            icon={BellDot}
            title="Sem notificações por ler"
            description="As notificações que receberes aparecerão aqui."
            variant="table"
          />
        )}
      </TabsContent>
    </Tabs>
  );
}
