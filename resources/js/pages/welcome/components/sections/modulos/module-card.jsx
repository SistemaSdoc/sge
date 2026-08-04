export default function ModuleCard({
  number,
  title,
  description,
  className = '',
}) {
  return (
    <div
      className={`reveal flex h-full min-h-55 flex-col justify-between border-b border-border px-8 py-9 transition-colors duration-300 hover:bg-muted/10 ${className}`}
    >
      <div>
        <h3 className="mt-6 font-display text-[19px] font-semibold">{title}</h3>
        <p className="mt-2.5 text-sm leading-relaxed text-muted-foreground">
          {description}
        </p>
      </div>
    </div>
  );
}
