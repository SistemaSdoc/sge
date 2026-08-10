const ESCOLAS = ['Colégio Aurora', 'Instituto Meridiano', 'Escola Vale Verde'];

export default function Clientes() {
  return (
    <section id="clientes" className="border-b pb-22.5">
      <div className="grid grid-cols-1 border-b border-border px-4 min-[820px]:grid-cols-2 sm:px-12">
        <div className="reveal border-b border-border px-4 py-10 min-[820px]:border-r min-[820px]:border-b-0 sm:px-12 sm:py-15">
          <p className="max-w-115 font-display text-xl leading-relaxed font-medium sm:text-2xl">
            "Deixámos de gerir três escolas em três sistemas diferentes. Hoje é
            uma direção, um painel."
          </p>
          <div className="mt-6 text-xs tracking-[0.24em] text-muted-foreground uppercase">
            Diretora Pedagógica — Grupo Escolar
          </div>
        </div>
        <div className="reveal flex flex-col justify-center gap-5 px-4 py-10 sm:px-12 sm:py-15">
          {ESCOLAS.map((nome) => (
            <div
              key={nome}
              className="flex justify-between gap-3 border-b border-border/10 pb-3.5 font-display text-sm text-muted-foreground sm:text-base"
            >
              {nome}{' '}
              <span className="text-[10px] tracking-[0.24em] text-muted-foreground uppercase sm:text-xs">
                Angola
              </span>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
