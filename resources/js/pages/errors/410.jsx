import { Button } from '@/components/ui/button';
import {
  Empty,
  EmptyContent,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty';
import { TrashIcon } from 'lucide-react';

export default function Gone() {
  return (
    <Empty>
      <EmptyHeader>
        <EmptyMedia variant="icon" className="text-gray-500">
          <TrashIcon className="h-12 w-12" />
        </EmptyMedia>
        <EmptyTitle>Recurso Eliminado</EmptyTitle>
        <EmptyDescription>
          Esta instituição foi permanentemente encerrada e não pode ser
          recuperada.
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
