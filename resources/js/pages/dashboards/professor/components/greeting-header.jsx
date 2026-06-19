import { Skeleton } from '@/components/ui/skeleton';

export function GreetingHeader({ greeting, userName, todayFormatted }) {
  return (
    <div className="space-y-1">
      <h1 className="flex items-center gap-2 text-lg font-light tracking-wide text-foreground">
        {greeting},{' '}
        <span className="text-primary">
          {userName || <Skeleton className="h-4 w-52" />}
        </span>
      </h1>

      <p className="text-xs font-light text-muted-foreground/70">
        {todayFormatted}
      </p>
    </div>
  );
}
