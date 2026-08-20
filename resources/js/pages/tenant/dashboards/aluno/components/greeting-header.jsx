export function GreetingHeader({ greeting, userName, todayFormatted }) {
  return (
    <div className="space-y-1">
      <h1 className="text-lg font-medium text-foreground">
        {greeting},{' '}
        <span className="text-secondary">{userName || 'Utilizador'}</span>
      </h1>
      <p className="text-xs font-light text-muted-foreground/70">
        {todayFormatted}
      </p>
    </div>
  );
}
