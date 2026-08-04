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
    <section id="plataforma" className="border-border border-b px-4 py-16 sm:px-12 sm:py-22.5">
      <div className="mb-8 flex flex-col items-start gap-6 sm:mb-15 sm:flex-wrap sm:items-end sm:justify-between lg:flex-row">
        <h2 className="reveal font-display max-w-140 text-[clamp(24px,4.2vw,44px)] font-semibold tracking-[-0.01em]">
          Um painel, todas as escolas do grupo.
        </h2>
        <p className="max-w-[320px] pb-1 text-sm leading-relaxed text-muted-foreground sm:text-[15px]">
          Troca de escola sem sair da sessão. O que muda é o contexto — nunca a
          confiança nos dados.
        </p>
      </div>
      <div className="reveal border-border bg-card mt-8 overflow-hidden border sm:mt-15">
        <div className="border-border flex flex-wrap items-center gap-2 border-b px-3 py-3.5 sm:px-4.5">
          <span className="bg-muted/30 h-2 w-2 rounded-full"></span>
          <span className="bg-muted/30 h-2 w-2 rounded-full"></span>
          <span className="bg-muted/30 h-2 w-2 rounded-full"></span>
          <span className="text-muted-foreground ml-0 font-mono text-[10px] sm:ml-3 sm:text-xs">
            sge.escola.ao/colegio-aurora/pautas
          </span>
        </div>
        <div className="grid min-h-85 grid-cols-1 md:grid-cols-[200px_1fr]">
          <div className="border-border border-b py-4 md:border-r md:border-b-0 md:py-6">
            {SIDEBAR_LINKS.map((link) => (
              <a
                key={link.label}
                href="#"
                className={`block border-l-2 px-6 py-2.5 text-[13px] ${
                  link.active
                    ? 'text-foreground border-accent bg-accent/10'
                    : 'border-transparent text-muted-foreground'
                }`}
              >
                {link.label}
              </a>
            ))}
          </div>
          <div className="p-3 sm:p-7">
            <div className="border-border/10 text-muted-foreground hidden grid-cols-[2fr_1fr_1fr_1fr] items-center gap-3 border-b py-3.5 font-mono text-[11px] uppercase sm:grid">
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
