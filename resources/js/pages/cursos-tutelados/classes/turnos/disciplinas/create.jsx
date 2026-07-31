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
    anoLectivoId: initialAnoLectivoId,
    anosLectivos,
  } = usePage().props;
  const [disciplinaIds, setDisciplinaIds] = useState([]);
  const [anoLectivoSelecionado, setAnoLectivoSelecionado] = useState(
    initialAnoLectivoId ?? '',
  );

  const redirectTo =
    new URLSearchParams(window.location.search).get('redirect_to') ?? '';

  const handleAnoLectivoChange = (value) => {
    setAnoLectivoSelecionado(value);

    router.visit(window.location.pathname, {
      data: {
        ano_lectivo_id: value,
        redirect_to: redirectTo,
      },
      preserveScroll: true,
    });
  };

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
        ano_lectivo_id: anoLectivoSelecionado,
      })}
    >
      {({ errors, processing }) => (
        <DisciplinaForm
          disciplinas={disciplinas}
          disciplinaIds={disciplinaIds}
          setDisciplinaIds={setDisciplinaIds}
          errors={errors}
          processing={processing}
          anosLectivos={anosLectivos}
          anoLectivoSelecionado={anoLectivoSelecionado}
          onAnoLectivoChange={handleAnoLectivoChange}
        />
      )}
    </Form>
  );
}
