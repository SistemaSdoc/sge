const ESCOLAS = ['Colégio Aurora', 'Instituto Meridiano', 'Escola Vale Verde'];

export default function Clientes() {
  return (
    <section id="clientes">
      <div className="border-border grid grid-cols-1 border-b px-4 sm:px-12 min-[820px]:grid-cols-2">
        <div className="reveal border-border border-b px-4 py-10 sm:px-12 sm:py-15 min-[820px]:border-r min-[820px]:border-b-0">
          <p className="font-display max-w-115 text-xl leading-relaxed font-medium sm:text-2xl">
            "Deixámos de gerir três escolas em três sistemas diferentes. Hoje é
            uma direção, um painel."
          </p>
          <div className="mt-6 text-xs uppercase tracking-[0.24em] text-muted-foreground">
            Diretora Pedagógica — Grupo Escolar
          </div>
        </div>
        <div className="reveal flex flex-col justify-center gap-5 px-4 py-10 sm:px-12 sm:py-15">
          {ESCOLAS.map((nome) => (
            <div
              key={nome}
              className="border-border/10 font-display flex justify-between gap-3 border-b pb-3.5 text-sm text-muted-foreground sm:text-base"
            >
              {nome}{' '}
              <span className="text-[10px] uppercase tracking-[0.24em] text-muted-foreground sm:text-xs">
                Angola
              </span>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
