export default function PautaRow({ nome, classe, media, situacao }) {
  const isAprovado = situacao === 'Aprovado';

  return (
    <div className="flex flex-col gap-2 border-b border-border/10 py-3.5 text-[13px] sm:grid sm:grid-cols-[2fr_1fr_1fr_1fr] sm:items-center sm:gap-3">
      <span className="font-medium sm:font-normal">{nome}</span>
      <span className="text-muted-foreground sm:text-inherit">{classe}</span>
      <span className="text-muted-foreground sm:text-inherit">{media}</span>
      <span
        className={`w-fit rounded-full border px-2 py-0.75 font-mono text-[10px] ${
          isAprovado
            ? 'border-emerald-200 text-emerald-700'
            : 'border-amber-200 text-amber-700'
        }`}
      >
        {situacao}
      </span>
    </div>
  );
}
