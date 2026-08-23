import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { InputGroup } from '@/components/ui/input-group';
import {
  Empty,
  EmptyContent,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty';
import { SearchIcon, AlertCircleIcon } from 'lucide-react';

export default function NotFound() {
  return (
    <Empty>
      <EmptyHeader>
        <EmptyMedia variant="icon" className="text-blue-500">
          <AlertCircleIcon className="h-12 w-12" />
        </EmptyMedia>
        <EmptyTitle>Página Não Encontrada</EmptyTitle>
        <EmptyDescription>
          A página que procura não existe. Tente pesquisar ou voltar ao início.
        </EmptyDescription>
      </EmptyHeader>
      <EmptyContent>
        <div className="w-full space-y-3">
          <InputGroup>
            <SearchIcon className="h-4 w-4" />
            <Input placeholder="Pesquisar..." type="search" />
          </InputGroup>
          <Button asChild className="w-full">
            <a href="/">Voltar ao Início</a>
          </Button>
        </div>
      </EmptyContent>
    </Empty>
  );
}
