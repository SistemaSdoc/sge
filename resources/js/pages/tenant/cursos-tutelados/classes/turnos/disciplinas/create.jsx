import { Form, usePage } from '@inertiajs/react';
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
    anoLectivoId: initialAnoLectivoId,
  } = usePage().props;
  const [disciplinaIds, setDisciplinaIds] = useState([]);

  const params = {
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
  };

  const [anoLectivoId, setAnoLectivoId] = useState(initialAnoLectivoId ?? '');

  function handleAnoLectivo(val) {
    setAnoLectivoId(val);
  }

  return (
    <Form
      {...store.form(params)}
      transform={(data) => ({
        ...data,
        disciplina_ids: disciplinaIds,
        ano_lectivo_id: anoLectivoId,
      })}
    >
      {({ errors, processing }) => (
        <DisciplinaForm
          params={params}
          disciplinas={disciplinas}
          disciplinaIds={disciplinaIds}
          anoLectivoId={anoLectivoId}
          setAnoLectivoId={handleAnoLectivo}
          anosLectivos={anosLectivos}
          setDisciplinaIds={setDisciplinaIds}
          errors={errors}
          processing={processing}
        />
      )}
    </Form>
  );
}
