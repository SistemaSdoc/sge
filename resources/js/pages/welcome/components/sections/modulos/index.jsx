import ModuleCard from './module-card';

const MODULES = [
  {
    number: '01',
    title: 'Matrículas e turmas',
    description:
      'Inscrição, transferência entre escolas do grupo e distribuição automática por turma e turno.',
    borderRight: true,
  },
  {
    number: '02',
    title: 'Currículo académico',
    description:
      'Disciplinas por classe e curso, com regras de continuidade validadas automaticamente entre anos.',
    borderRight: true,
  },
  {
    number: '03',
    title: 'Avaliações e pautas',
    description:
      'Lançamento de notas, recuperação e cálculo de médias segundo as regras próprias de cada escola.',
    borderRight: false,
  },
  {
    number: '04',
    title: 'Multi-tenant nativo',
    description:
      'Cada escola opera isolada — dados, utilizadores e configurações próprias — sob uma administração central.',
    borderRight: true,
  },
  {
    number: '05',
    title: 'Relatórios de direção',
    description:
      'Visão consolidada de todas as escolas do grupo, ou detalhe imediato de uma única unidade.',
    borderRight: true,
  },
  {
    number: '06',
    title: 'Acessos por perfil',
    description:
      'Direção, secretaria, professores e encarregados — cada um vê exatamente o que precisa.',
    borderRight: false,
  },
];

export default function Modulos() {
  return (
    <section id="modulos" className="border-b border-line py-22.5">
      <div className="mb-15 flex flex-wrap items-end justify-between gap-10 px-12">
        <h2 className="reveal max-w-140 font-display text-[clamp(28px,3.4vw,44px)] font-semibold tracking-[-0.01em]">
          Cada módulo resolve uma decisão real da secretaria.
        </h2>
        <p className="max-w-[320px] pb-1 text-[15px] leading-relaxed text-muted">
          Não é uma lista de funcionalidades — é o fluxo do ano letivo, do
          primeiro dia ao boletim final.
        </p>
      </div>
      <div className="grid grid-cols-1 border-t border-line min-[900px]:grid-cols-3">
        {MODULES.map((m) => (
          <ModuleCard key={m.number} {...m} />
        ))}
      </div>
    </section>
  );
}
