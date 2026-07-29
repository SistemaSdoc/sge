export default function ModuleCard({
  number,
  title,
  description,
  borderRight,
}) {
  return (
    <div
      className={`reveal px-8 py-9 ${
        borderRight ? 'min-[900px]:border-r' : ''
      } border-line hover:bg-surface flex min-h-55 flex-col justify-between border-b transition-colors duration-300`}
    >
      <span className="text-muted-dim font-mono text-xs">{number}</span>
      <div>
        <h3 className="font-display mt-6 text-[19px] font-semibold">{title}</h3>
        <p className="mt-2.5 text-sm leading-relaxed text-muted">
          {description}
        </p>
      </div>
    </div>
  );
}
