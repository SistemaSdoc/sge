import { Head, Link } from '@inertiajs/react';
import { CalendarClock } from 'lucide-react';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Empty,
  EmptyContent,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty';

export default function Horarioss() {
  return (
    <div className="flex flex-col items-center justify-center gap-3 px-4 py-16 text-center sm:gap-4 sm:p-6 sm:py-24">
      <Head title="Horários" />
      <Empty>
        <EmptyHeader>
          <EmptyMedia variant="icon">
            <CalendarClock className="size- text-secondary" />
          </EmptyMedia>
          <EmptyTitle>Horários</EmptyTitle>
          <EmptyDescription>
            Esta funcionalidade está em desenvolvimento e ficará disponível em
            breve.
          </EmptyDescription>
        </EmptyHeader>
        <EmptyContent>
          <EmptyDescription>
            <Button variant="link" className="group">
              <ArrowLeft className="transition-all duration-150 group-hover:transform-[rotate(45deg)] group-hover:text-secondary" />
              <Link href="/dashboard">Voltar ao Dashboard</Link>
            </Button>
          </EmptyDescription>
        </EmptyContent>
      </Empty>
    </div>
  );
}
