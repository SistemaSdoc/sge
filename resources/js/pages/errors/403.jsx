import { Button } from '@/components/ui/button';
import {
  Empty,
  EmptyContent,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty';
import { LockIcon } from 'lucide-react';

export default function Forbidden() {
  return (
    <Empty>
      <EmptyHeader>
        <EmptyMedia variant="icon" className="text-red-500">
          <LockIcon className="h-12 w-12" />
        </EmptyMedia>
        <EmptyTitle>Acesso Negado</EmptyTitle>
        <EmptyDescription>
          Não tem permissão para aceder a este recurso.
        </EmptyDescription>
      </EmptyHeader>
      <EmptyContent>
        <Button asChild>
          <a href="/">Voltar ao Início</a>
        </Button>
      </EmptyContent>
    </Empty>
  );
}
