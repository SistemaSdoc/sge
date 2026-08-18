import { useForm } from '@inertiajs/react';
import LancamentosTable from './components/create/lancamentos-table';
import { store } from '@/actions/App/Http/Controllers/NotaDisciplinaController';
import { usePagination } from '@/hooks/use-pagination';
import { usePage } from '@inertiajs/react';
import { Header } from './components/create/lancamentos-header';

export default function Create({
  instituicao,
  cursoTutelado,
  cursoClasse,
  cursoClasseTurno,
  turma,
  classeTurnoDisciplina,
  can,
}) {
  const { data } = usePage().props;
  const alunosPagination = usePagination('alunos');

  const params = {
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
    turma,
    classeTurnoDisciplina,
  };

  const form = useForm({});

  const handleSubmit = (accao, formData) => {
    form.transform(() => ({ ...formData, accao }));
    form.post(
      store(
        { ...params },
        {
          query: {
            ano_lectivo_id:
              new URLSearchParams(window.location.search).get(
                'ano_lectivo_id',
              ) ?? undefined,
          },
        },
      ).url,
      { preserveScroll: true },
    );
  };

  if (!data?.alunos || data.alunos.data.length === 0) {
    return (
      <div className="flex justify-center py-20">
        <span className="text-sm text-muted-foreground">
          Sem dados disponíveis.
        </span>
      </div>
    );
  }

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      <Header can={can} turma={turma} params={params} />

      <LancamentosTable
        data={data}
        isPending={form.processing}
        errors={form.errors}
        instituicaoId={instituicao}
        cursoId={cursoTutelado}
        classeId={cursoClasse}
        turnoId={cursoClasseTurno}
        turmaId={turma}
        disciplinaId={classeTurnoDisciplina}
        can={can}
        periodosLancados={data?.periodos_lancados ?? {}}
        periodosDisponiveis={data?.periodos_disponiveis ?? {}}
        pagination={{
          current_page: data.alunos.current_page,
          last_page: data.alunos.last_page,
        }}
        pautaStatus={data?.pauta_status ?? {}}
        dentroDoPrazo={data?.dentro_do_prazo ?? {}}
        onSubmit={handleSubmit}
        autorizacaoAte={data?.autorizacao_ate ?? {}}
        temSolicitacaoPendente={data?.tem_solicitacao_pendente ?? {}}
      />
    </div>
  );
}
