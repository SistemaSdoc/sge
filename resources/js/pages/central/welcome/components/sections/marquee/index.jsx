const ITEMS = [
  'Matrículas',
  'Currículo académico',
  'Avaliações',
  'Pautas',
  'Relatórios',
  'Multi-tenant',
];

export default function Marquee() {
  const items = [...ITEMS, ...ITEMS];

  return (
    <div className="overflow-hidden border-b border-border py-8">
      <div
        id="marquee"
        className="flex font-display text-[15px] whitespace-nowrap text-muted-foreground"
      >
        {items.map((item, i) => (
          <span key={i} className="marquee-item">
            {item}
          </span>
        ))}
      </div>
    </div>
  );
}
