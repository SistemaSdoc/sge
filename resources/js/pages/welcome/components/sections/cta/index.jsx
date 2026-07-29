export default function Cta() {
  return (
    <section id="cta" className="px-12 py-27.5 text-center">
      <div className="mono mb-5 flex justify-center">Comece hoje</div>
      <h2 className="reveal font-display text-[clamp(32px,5vw,56px)] font-semibold tracking-[-0.01em]">
        Centralize a gestão de todas as tuas escolas.
      </h2>
      <p className="mt-4.5 text-muted">
        Uma demonstração de 20 minutos, com os teus próprios dados de exemplo.
      </p>
      <div className="mt-10 flex justify-center gap-4">
        <a
          href="#"
          className="bg-text inline-flex items-center gap-2 px-6 py-3.25 text-sm font-medium text-[#0a0a0c] transition-[transform,background-color] duration-300 ease-[cubic-bezier(0.2,0.8,0.2,1)] hover:-translate-y-0.5 hover:bg-accent hover:text-white"
        >
          Agendar demonstração →
        </a>
        <a
          href="#"
          className="border-line hover:text-text hover:border-text border-b pb-0.5 text-sm text-muted transition-colors duration-250"
        >
          Falar com a equipa
        </a>
      </div>
    </section>
  );
}
