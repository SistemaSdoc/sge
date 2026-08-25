export function ChartSection({ title, description, children, className = '' }) {
  return (
    <div className={`flex flex-col p-5 ${className}`}>
      <p className="text-sm font-medium">{title}</p>
      <p className="mt-0.5 text-xs text-muted-foreground">{description}</p>
      {children}
    </div>
  );
}
