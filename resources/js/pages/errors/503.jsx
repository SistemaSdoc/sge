import { Button } from '@/components/ui/button';
import {
  Empty,
  EmptyContent,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty';
import { ServerOffIcon } from 'lucide-react';

export default function ServiceUnavailable() {
  return (
    <Empty>
      <EmptyHeader>
        <EmptyMedia variant="icon" className="text-orange-500">
          <ServerOffIcon className="h-12 w-12 animate-pulse" />
        </EmptyMedia>
        <EmptyTitle>Serviço Indisponível</EmptyTitle>
        <EmptyDescription>
          O sistema está temporariamente indisponível. Tente novamente em alguns
          minutos.
        </EmptyDescription>
      </EmptyHeader>
      <EmptyContent>
        <div className="space-y-2 text-sm text-gray-500">
          <p>Status: 503 Service Unavailable</p>
          <Button asChild variant="outline" className="w-full">
            <a href="/">Voltar ao Início</a>
          </Button>
        </div>
      </EmptyContent>
    </Empty>
  );
}
