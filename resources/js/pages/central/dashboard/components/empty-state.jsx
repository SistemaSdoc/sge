import { Clock } from 'lucide-react';

export function EmptyState({ text }) {
  return (
    <div className="flex h-full min-h-64 items-center justify-center gap-2 text-sm text-muted-foreground">
      <Clock className="size-4" />
      {text}
    </div>
  );
}