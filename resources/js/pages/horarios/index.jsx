import { Head } from '@inertiajs/react';
import { CalendarClock } from 'lucide-react';

export default function Horarios() {
  return (
    <>
      <Head title="Horários" />
      <div className="flex flex-col items-center justify-center gap-3 px-4 py-16 text-center sm:gap-4 sm:p-6 sm:py-24">
        <CalendarClock className="size-8 text-secondary sm:size-10" />
        <h1 className="text-lg font-semibold sm:text-xl">Horários</h1>
        <p className="max-w-xs text-sm text-muted-foreground sm:max-w-md">
          Esta funcionalidade está em desenvolvimento e ficará disponível em
          breve.
        </p>
      </div>
    </>
  );
}
