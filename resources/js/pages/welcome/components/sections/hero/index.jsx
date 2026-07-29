import TenantCard from './tenant-card';

const TENANTS = [
  {
    id: 'tc-1',
    school: 'Colégio Aurora',
    tenant: 'TENANT_01',
    bars: [40, 70, 55, 90, 65, 80],
    average: '16.4',
    style: { transform: 'translate(28px, 28px) rotate(0deg)' },
  },
  {
    id: 'tc-2',
    school: 'Instituto Meridiano',
    tenant: 'TENANT_02',
    bars: [60, 45, 85, 50, 70, 60],
    average: '14.9',
    style: { transform: 'translate(14px, 14px) rotate(0deg)' },
  },
  {
    id: 'tc-3',
    school: 'Escola Vale Verde',
    tenant: 'TENANT_03',
    bars: [75, 60, 40, 65, 85, 50],
    average: '15.7',
    style: { transform: 'translate(0, 0) rotate(0deg)' },
  },
];

export default function Hero() {
  return (
    <section
      id="produto"
      className="border-line relative border-b px-12 pt-45 pb-25"
    >
      <div className="mono mb-7 flex items-center gap-2.5">
        <span className="h-1.5 w-1.5 rounded-full bg-accent"></span> Gestão
        escolar multi-tenant
      </div>
      <h1
        id="hero-title"
        className="reveal font-display max-w-225 text-[clamp(40px,6.4vw,84px)] leading-[1.02] font-semibold tracking-[-0.02em]"
      >
        Uma plataforma. <span className="text-muted-dim">Todas as escolas</span>
        <br />
        do teu grupo.
      </h1>
      <p
        id="hero-sub"
        className="reveal mt-7 max-w-120 text-[17px] leading-relaxed text-muted"
      >
        Matrículas, currículo académico, avaliações e relatórios de cada escola,
        isolados por tenant, geridos a partir de um único painel.
      </p>
      <div id="hero-cta" className="reveal mt-10 flex items-center gap-4">
        <a
          href="#cta"
          className="bg-text inline-flex items-center gap-2 px-6 py-3.25 text-sm font-medium text-[#0a0a0c] transition-[transform,background-color] duration-300 ease-[cubic-bezier(0.2,0.8,0.2,1)] hover:-translate-y-0.5 hover:bg-accent hover:text-white"
        >
          Agendar demonstração →
        </a>
        <a
          href="#modulos"
          className="border-line hover:text-text hover:border-text border-b pb-0.5 text-sm text-muted transition-colors duration-250"
        >
          Ver módulos
        </a>
      </div>

      <div
        id="hero-meta"
        className="reveal border-line mt-22.5 grid grid-cols-3 border-t"
      >
        <div className="border-line border-r pt-5">
          <div className="font-display text-[28px] font-semibold">99.9%</div>
          <div className="mono mt-1.5">Disponibilidade</div>
        </div>
        <div className="border-line border-r pt-5">
          <div className="font-display text-[28px] font-semibold">
            &lt;150ms
          </div>
          <div className="mono mt-1.5">Tempo de resposta / tenant</div>
        </div>
        <div className="pt-5">
          <div className="font-display text-[28px] font-semibold">100%</div>
          <div className="mono mt-1.5">Dados isolados por escola</div>
        </div>
      </div>

      <div className="absolute top-42.5 right-12 hidden h-100 w-85 min-[1100px]:block">
        {TENANTS.map((t) => (
          <TenantCard key={t.id} {...t} />
        ))}
      </div>
    </section>
  );
}
