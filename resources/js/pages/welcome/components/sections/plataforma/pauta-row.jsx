export default function PautaRow({ nome, classe, media, situacao }) {
  const isAprovado = situacao === 'Aprovado';

  return (
    <div className="border-line-soft grid grid-cols-[2fr_1fr_1fr_1fr] items-center gap-3 border-b py-3.5 text-[13px]">
      <span>{nome}</span>
      <span>{classe}</span>
      <span>{media}</span>
      <span
        className={`w-fit border px-2 py-0.75 font-mono text-[10px] ${
          isAprovado ? 'border-ok-line text-ok' : 'border-warn-line text-gold'
        }`}
      >
        {situacao}
      </span>
    </div>
  );
}
