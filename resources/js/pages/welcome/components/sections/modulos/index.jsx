import ModuleCard from './module-card';

const MODULES = [
  {
    number: '01',
    title: 'Gestão de Cursos Tutelados',
    description:
      'Criação e configuração de cursos por nível de ensino, com definição de planos curriculares, carga horária e requisitos de progressão.',
    borderRight: true,
  },
  {
    number: '02',
    title: 'Gestão de Alunos',
    description:
      'Inscrição, transferência entre escolas do grupo e distribuição automática por turma e turno, com historial académico completo.',
    borderRight: true,
  },
  {
    number: '03',
    title: 'Gestão Académica',
    description:
      'Disciplinas por classe e curso, com regras de continuidade e progressão validadas automaticamente entre anos letivos.',
    borderRight: true,
  },
  {
    number: '04',
    title: 'Gestão de Docentes',
    description:
      'Registo e gestão de professores, atribuição de disciplinas e turmas, controlo de horários e acompanhamento do desempenho letivo.',
    borderRight: true,
  },
  {
    number: '05',
    title: 'Avaliações e Notas',
    description:
      'Lançamento de notas por período, recuperação, exames e cálculo automático de médias segundo as regras próprias de cada escola.',
    borderRight: true,
  },
  {
    number: '06',
    title: 'Gestão Financeira',
    description:
      'Emissão de propinas, controlo de pagamentos, geração de recibos e acompanhamento de dívidas por aluno ou turma.',
    borderRight: true,
  },
  {
    number: '07',
    title: 'Calendário Escolar',
    description:
      'Definição de períodos letivos, feriados, eventos e datas de avaliação, com sincronização automática para todos os utilizadores da escola.',
    borderRight: true,
  },
  {
    number: '08',
    title: 'Dashboard e Relatórios',
    description:
      'Visão consolidada de todas as escolas do grupo ou detalhe de uma única unidade, com relatórios exportáveis em tempo real.',
    borderRight: true,
  },
  {
    number: '09',
    title: 'Comunicação e Alertas',
    description:
      'Envio de notificações e mensagens para encarregados, professores e funcionários, com alertas automáticos sobre faltas, notas e pagamentos.',
    borderRight: false,
  },
];

export default function Modulos() {
  const remaining = MODULES.slice(1).filter(
    (m, i, arr) => arr.findIndex((x) => x.title === m.title) === i,
  );
  return (
    <section id="modulos" className="border-b border-border py-22.5">
      <div className="mb-15 flex flex-wrap items-end justify-between gap-10 px-12">
        <h2 className="reveal max-w-140 font-display text-[clamp(28px,3.4vw,44px)] font-semibold tracking-[-0.01em]">
          Cada módulo resolve aspectos reais das instituições escolares.
        </h2>
        <p className="max-w-[320px] pb-1 text-[15px] leading-relaxed text-muted-foreground">
          Não é uma lista de funcionalidades — é o fluxo do ano letivo, do
          primeiro dia ao boletim final.
        </p>
      </div>
      <div className="border-t border-border">
        <div className="">
          {/* First module: full-width */}
          <div className="grid grid-cols-1">
            <div className="col-span-full">
              <ModuleCard
                key={MODULES[0].number}
                {...MODULES[0]}
                className="flex justify-center"
              />
            </div>
          </div>

          {/* Remaining modules: responsive grid */}
          <div className="grid grid-cols-1 gap-0 min-[900px]:grid-cols-4">
            {remaining.map((m, idx) => {
              const colIndex = idx % 4;
              const extra = colIndex !== 3 ? 'min-[900px]:border-r' : '';
              return (
                <div key={idx} className="h-full">
                  <ModuleCard {...m} className={extra} />
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </section>
  );
}
