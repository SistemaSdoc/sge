export function GreetingHeader({ greeting, userName, todayFormatted }) {
  return (
    <div className="space-y-1">
      <h1 className="text-base leading-snug font-medium text-foreground sm:text-lg">
        {greeting},{' '}
        <span className="text-secondary">{userName || 'Utilizador'}</span>
      </h1>

      <p className="text-sm font-light text-muted-foreground/70">
        {todayFormatted}
      </p>
    </div>
  );
}
