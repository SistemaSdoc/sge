import { Button } from '@/components/ui/button';

const CONTACT_EMAIL = 'geral@sdoca.it.ao';

export default function Cta() {
  const contactHref = `mailto:${CONTACT_EMAIL}`;

  return (
    <section id="cta" className="px-4 py-16 text-center sm:px-12 sm:py-27.5">
      <div className="mb-4 flex justify-center mono text-sm sm:mb-5">Comece hoje</div>
      <h2 className="reveal mx-auto max-w-3xl font-display text-[clamp(28px,6vw,56px)] font-semibold tracking-[-0.01em]">
        Centralize a gestão de todas as tuas escolas.
      </h2>
      <p className="mx-auto mt-4 text-sm leading-relaxed text-muted-foreground sm:mt-4.5 sm:text-base">
        Uma demonstração de 20 minutos, com os seus próprios dados de exemplo.
      </p>
      <div className="mt-8 flex flex-col justify-center gap-3 sm:mt-10 sm:flex-row">
        <Button asChild size={'lg'} className="w-full sm:w-auto">
          <a href={contactHref}>Agendar demonstração</a>
        </Button>

        <Button asChild variant="outline" size={'lg'} className="w-full sm:w-auto">
          <a href={contactHref}>Falar com a equipa</a>
        </Button>
      </div>
    </section>
  );
}
