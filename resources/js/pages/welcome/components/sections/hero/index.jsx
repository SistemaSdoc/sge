import { ArrowUpRight } from 'lucide-react';
import TenantCard from './tenant-card';
import { Button } from '@/components/ui/button';

const CONTACT_EMAIL = 'geral@sdoca.it.ao';

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
  const contactHref = `mailto:${CONTACT_EMAIL}`;

  return (
    <section
      id="produto"
      className="border-b border-border pt-24 sm:pt-45 sm:pb-22.5"
    >
      {/*<div className="mb-7 flex items-center gap-2.5 text-xs uppercase tracking-[0.24em] text-muted-foreground">
        <span className="h-1.5 w-1.5 rounded-full bg-accent"></span> Gestão
        escolar multi-tenant
      </div>*/}
      <div className="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
        <h1
          id="hero-title"
          className="reveal mx-auto max-w-4xl font-display text-[clamp(30px,7vw,84px)] leading-[1.02] font-semibold tracking-[-0.02em]"
        >
          Uma plataforma.{' '}
          <span className="text-secondary">Todas as escolas</span>
          <br />
          do seu grupo.
        </h1>
        <p
          id="hero-sub"
          className="reveal mx-auto mt-5 max-w-2xl text-sm leading-relaxed text-muted-foreground sm:mt-7 sm:text-[17px]"
        >
          Matrículas, currículo académico, avaliações e relatórios de cada
          escola, isolados por tenant, geridos a partir de um único painel.
        </p>
        <div
          id="hero-cta"
          className="reveal mx-auto mt-8 flex flex-col items-stretch justify-center gap-3 sm:mt-10 sm:flex-row sm:items-center"
        >
          <Button size={'lg'} className="w-full sm:w-auto">
            <a href={contactHref}>Agendar demonstração</a>
            <ArrowUpRight size={20} />
          </Button>

          <Button asChild variant="outline" className="w-full sm:w-auto">
            <a href="#modulos">Ver módulos</a>
          </Button>
        </div>
      </div>

      <div
        id="hero-meta"
        className="reveal mt-12 grid grid-cols-1 border-y border-border px-4 sm:mt-22.5 sm:grid-cols-2 sm:px-12"
      >
        <div className="flex w-full flex-col items-center border-b border-border p-6 sm:border-r sm:border-b-0">
          <div className="font-display text-[24px] font-semibold sm:text-[28px]">
            99.9%<span className="text-secondary">%</span>
          </div>
          <div className="mt-1.5 text-xs tracking-[0.24em] text-muted-foreground uppercase">
            Disponibilidade
          </div>
        </div>

        {/*<div className="border-r border-border p-6">
          <div className="font-display text-[28px] font-semibold">
            &lt;150ms
          </div>
          <div className="mt-1.5 text-xs tracking-[0.24em] text-muted-foreground uppercase">
            Tempo de resposta / tenant
          </div>
        </div>*/}

        <div className="flex flex-col items-center p-6">
          <div className="font-display text-[24px] font-semibold sm:text-[28px]">
            100<span className="text-secondary">%</span>
          </div>
          <div className="mt-1.5 text-xs tracking-[0.24em] text-muted-foreground uppercase">
            Dados isolados por escola
          </div>
        </div>
      </div>

      <div className="absolute top-42.5 right-12 hidden h-100 w-85 min-[1100px]:block">
        {/*{TENANTS.map((t) => (
          <TenantCard key={t.id} {...t} />
        ))}*/}
      </div>
    </section>
  );
}
