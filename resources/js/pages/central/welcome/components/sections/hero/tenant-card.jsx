export default function TenantCard({
  id,
  school,
  tenant,
  bars,
  average,
  style,
}) {
  return (
    <div
      id={id}
      className="absolute inset-0 flex flex-col justify-between border border-border bg-card p-5"
      style={style}
    >
      <div className="flex items-center justify-between">
        <span className="font-display text-[15px] font-semibold">{school}</span>
        <span className="rounded-full border border-accent/30 bg-accent/10 px-1.75 py-0.75 font-mono text-[10px] text-accent">
          {tenant}
        </span>
      </div>
      <div className="flex h-20 items-end gap-1.5">
        {bars.map((h, i) => (
          <div
            key={i}
            className="flex-1 bg-muted/30"
            style={{ height: `${h}%` }}
          ></div>
        ))}
      </div>
      <div className="flex justify-between font-mono text-[11px] text-muted-foreground">
        <span>Média geral</span>
        <span>{average}</span>
      </div>
    </div>
  );
}
