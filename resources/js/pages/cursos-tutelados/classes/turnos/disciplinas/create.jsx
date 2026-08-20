import { Form, router, usePage } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/Tenant/ClasseTurnoDisciplinaController';
import DisciplinaForm from './components/disciplina-form';
import { useState } from 'react';

export default function Create() {
  const {
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
    disciplinas,
    anosLectivos,
  } = usePage().props;
  const [disciplinaIds, setDisciplinaIds] = useState([]);

  const params = {
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
  };

  return (
    <Form
      {...store.form(params)}
      transform={(data) => ({
        ...data,
        disciplina_ids: disciplinaIds,
      })}
    >
      {({ errors, processing }) => (
        <DisciplinaForm
          params={params}
          disciplinas={disciplinas}
          disciplinaIds={disciplinaIds}
          setDisciplinaIds={setDisciplinaIds}
          errors={errors}
          processing={processing}
        />
      )}
    </Form>
  );
}
