import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { store } from '@/actions/App/Http/Controllers/Tenant/PreencherHistoricoController';
import LancamentosHistoricoTable from './components/lancamentos-table';

export default function Create({ aluno, turmaAluno, turma, can }) {
  const { disciplinas } = usePage().props;
  const [isPending, setIsPending] = useState(false);
  const [errors, setErrors] = useState({});

  const handleSubmit = (accao, formData) => {
    setIsPending(true);
    router.post(
      store({ aluno: aluno.id }).url,
      { ...formData, accao },
      {
        preserveScroll: true,
        onFinish: () => setIsPending(false),
        onError: (errs) => setErrors(errs),
      },
    );
  };

  return (
    <div className="mx-auto w-full max-w-7xl space-y-6 p-6">
      <LancamentosHistoricoTable
        aluno={aluno}
        turmaAluno={turmaAluno}
        turma={turma}
        disciplinas={disciplinas}
        isPending={isPending}
        errors={errors}
        can={can}
        onSubmit={handleSubmit}
      />
    </div>
  );
}
