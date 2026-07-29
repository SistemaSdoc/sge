import PautaRow from './pauta-row';

const SIDEBAR_LINKS = [
  { label: 'Painel', active: false },
  { label: 'Pautas', active: true },
  { label: 'Matrículas', active: false },
  { label: 'Currículo', active: false },
  { label: 'Relatórios', active: false },
  { label: 'Configurações', active: false },
];

const PAUTAS = [
  { nome: 'Beatriz N.', classe: '9ª', media: '17.2', situacao: 'Aprovado' },
  { nome: 'Carlos M.', classe: '9ª', media: '13.8', situacao: 'Aprovado' },
  { nome: 'Denise A.', classe: '9ª', media: '9.6', situacao: 'Recuperação' },
  { nome: 'Eduardo F.', classe: '9ª', media: '15.1', situacao: 'Aprovado' },
  { nome: 'Fátima R.', classe: '9ª', media: '11.4', situacao: 'Recuperação' },
];

export default function Plataforma() {
  return (
    <section id="plataforma" className="border-line border-b px-12 py-22.5">
      <div className="mb-15 flex flex-wrap items-end justify-between gap-10">
        <h2 className="reveal font-display max-w-140 text-[clamp(28px,3.4vw,44px)] font-semibold tracking-[-0.01em]">
          Um painel, todas as escolas do grupo.
        </h2>
        <p className="max-w-[320px] pb-1 text-[15px] leading-relaxed text-muted">
          Troca de escola sem sair da sessão. O que muda é o contexto — nunca a
          confiança nos dados.
        </p>
      </div>
      <div className="reveal border-line bg-surface mt-15 overflow-hidden border">
        <div className="border-line flex items-center gap-2 border-b px-4.5 py-3.5">
          <span className="bg-line h-2 w-2 rounded-full"></span>
          <span className="bg-line h-2 w-2 rounded-full"></span>
          <span className="bg-line h-2 w-2 rounded-full"></span>
          <span className="text-muted-dim ml-3 font-mono text-xs">
            sge.escola.ao/colegio-aurora/pautas
          </span>
        </div>
        <div className="grid min-h-85 grid-cols-[200px_1fr]">
          <div className="border-line border-r py-6">
            {SIDEBAR_LINKS.map((link) => (
              <a
                key={link.label}
                href="#"
                className={`block border-l-2 px-6 py-2.5 text-[13px] ${
                  link.active
                    ? 'text-text border-accent bg-accent/6'
                    : 'border-transparent text-muted'
                }`}
              >
                {link.label}
              </a>
            ))}
          </div>
          <div className="p-7">
            <div className="border-line-soft text-muted-dim grid grid-cols-[2fr_1fr_1fr_1fr] items-center gap-3 border-b py-3.5 font-mono text-[11px] uppercase">
              <span>Aluno</span>
              <span>Classe</span>
              <span>Média</span>
              <span>Situação</span>
            </div>
            {PAUTAS.map((p) => (
              <PautaRow key={p.nome} {...p} />
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
