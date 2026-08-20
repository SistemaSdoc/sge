import { useForm, usePage } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/Tenant/PreencherHistoricoController';
import LancamentosHistoricoTable from './components/lancamentos-table';

export default function Create({ aluno, turmaAluno, turma, can }) {
  const { disciplinas } = usePage().props;
  const form = useForm({});

  const handleSubmit = (accao, formData) => {
    form.transform(() => ({ ...formData, accao }));
    form.post(
      store({ aluno: aluno.id }).url,
      { preserveScroll: true },
    );
  };

  return (
    <div className="mx-auto w-full max-w-7xl space-y-6 p-6">
      <LancamentosHistoricoTable
        aluno={aluno}
        turmaAluno={turmaAluno}
        turma={turma}
        disciplinas={disciplinas}
        isPending={form.processing}
        errors={form.errors}
        can={can}
        onSubmit={handleSubmit}
      />
    </div>
  );
}