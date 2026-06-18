import { Form } from '@inertiajs/react';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AlunoForm from './components/aluno-form';
import {update, index} from '@/actions/App/Http/Controllers/AlunoController'

export default function Edit() {
  const { aluno, turmas } = usePage().props;
  const [turmaId, setTurmaId] = useState(
    aluno.turma_id ? String(aluno.turma_id) : undefined,
  );

  return (
    <Form
      action={update({id: aluno.id})}
      method="patch"
      transform={(data) => ({ ...data, turma_id: turmaId })}
      onSuccess={() => router.visit(index({}))}
    >
      {({ errors, processing }) => (
        <AlunoForm
          errors={errors}
          processing={processing}
          turmas={turmas}
          turmaId={turmaId}
          setTurmaId={setTurmaId}
          defaultValues={{
            nome: aluno.nome,
            bi: aluno.bi,
            matricula: aluno.matricula,
          }}
        />
      )}
    </Form>
  );
}
