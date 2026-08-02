import { Form, router, usePage } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/ClasseTurnoDisciplinaController';
import DisciplinaForm from './components/disciplina-form';
import { useState } from 'react';

export default function Create() {
  const {
    disciplinas,
    instituicaoId,
    cursoId,
    classeId,
    turnoId,
    anosLectivos,
  } = usePage().props;
  const [disciplinaIds, setDisciplinaIds] = useState([]);

  const redirectTo =
    new URLSearchParams(window.location.search).get('redirect_to') ?? '';

  return (
    <Form
      {...store.form({
        instituicao: instituicaoId,
        cursoTutelado: cursoId,
        cursoClasse: classeId,
        cursoClasseTurno: turnoId,
      })}
      transform={(data) => ({
        ...data,
        disciplina_ids: disciplinaIds,
        redirect_to: redirectTo,
      })}
    >
      {({ errors, processing }) => (
        <DisciplinaForm
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
