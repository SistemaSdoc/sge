import { useForm } from '@inertiajs/react';
import LancamentosTable from './components/lancamentos-table';
import { store } from '@/actions/App/Http/Controllers/NotaDisciplinaController';
import { usePagination } from '@/hooks/use-pagination';
import { usePage } from '@inertiajs/react';

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
  const form = useForm({});

  const handleSubmit = (accao, formData) => {
    form.transform(() => ({ ...formData, accao }));
    form.post(
      store(
        {
          instituicao,
          cursoTutelado,
          cursoClasse,
          cursoClasseTurno,
          turma,
          classeTurnoDisciplina,
        },
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

  return (
    <div className="mx-auto w-full max-w-7xl space-y-6 p-6">
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
