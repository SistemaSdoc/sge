import { Button } from '@/components/ui/button';
import {
  Empty,
  EmptyContent,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty';
import { AlertTriangleIcon } from 'lucide-react';

export default function InternalServerError() {
  return (
    <Empty>
      <EmptyHeader>
        <EmptyMedia variant="icon" className="text-red-600">
          <AlertTriangleIcon className="h-12 w-12" />
        </EmptyMedia>
        <EmptyTitle>Erro Interno do Servidor</EmptyTitle>
        <EmptyDescription>
          Ocorreu um erro inesperado. Os nossos engenheiros foram notificados e
          estão a resolver o problema.
        </EmptyDescription>
      </EmptyHeader>
      <EmptyContent>
        <div className="w-full space-y-3">
          <div className="rounded-md border border-red-200 bg-red-50 p-3">
            <p className="font-mono text-xs text-red-600">Error Code: 500</p>
          </div>
          <Button asChild>
            <a href="/">Voltar ao Início</a>
          </Button>
        </div>
      </EmptyContent>
    </Empty>
  );
}
