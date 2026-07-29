const ESCOLAS = ['Colégio Aurora', 'Instituto Meridiano', 'Escola Vale Verde'];

export default function Clientes() {
  return (
    <section id="clientes">
      <div className="border-line grid grid-cols-1 border-b px-12 min-[820px]:grid-cols-2">
        <div className="reveal border-line border-b px-12 py-15 min-[820px]:border-r min-[820px]:border-b-0">
          <p className="font-display max-w-115 text-2xl leading-relaxed font-medium">
            "Deixámos de gerir três escolas em três sistemas diferentes. Hoje é
            uma direção, um painel."
          </p>
          <div className="mono mt-6">Diretora Pedagógica — Grupo Escolar</div>
        </div>
        <div className="reveal flex flex-col justify-center gap-5 px-12 py-15">
          {ESCOLAS.map((nome) => (
            <div
              key={nome}
              className="border-line-soft font-display flex justify-between border-b pb-3.5 text-base text-muted"
            >
              {nome} <span className="mono">Angola</span>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
