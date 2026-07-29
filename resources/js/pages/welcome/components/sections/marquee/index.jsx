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
    <div className="border-line overflow-hidden border-b py-8">
      <div
        id="marquee"
        className="font-display text-muted-dim flex text-[15px] whitespace-nowrap"
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
