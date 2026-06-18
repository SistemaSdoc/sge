import { Form } from '@inertiajs/react';
import { router, usePage } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/ClasseTurnoDisciplinaController';
import { show } from '@/actions/App/Http/Controllers/CursoClasseController';
import DisciplinaForm from './components/disciplina-form';
import { useState } from 'react';

export default function Create() {
  const { 
    disciplinas, 
    instituicaoId, 
    cursoId, 
    classeId, 
    turnoId
  } =
    usePage().props;
  const [disciplinaIds, setDisciplinaIds] = useState([]);

  return (
    <Form
      {...store.form({
        instituicao: instituicaoId,
        cursoTutelado: cursoId,
        cursoClasse: classeId,
        cursoClasseTurno: turnoId,
      })}
      transform={(data) => ({ ...data, disciplina_ids: disciplinaIds })}
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
