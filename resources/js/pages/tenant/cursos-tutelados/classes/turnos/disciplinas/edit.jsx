import { Form, usePage } from '@inertiajs/react';
import { update } from '@/actions/App/Http/Controllers/Tenant/ClasseTurnoDisciplinaController';
import DisciplinaForm from './components/disciplina-form';
import { useState } from 'react';

export default function Edit() {
  const {
    disciplina,
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
    instituicaoId,
    cursoId,
    classeId,
    turnoId,
    anosLectivos = [],
    anoLectivoId: initialAnoLectivoId,
  } = usePage().props;
  const [anoLectivoId, setAnoLectivoId] = useState(initialAnoLectivoId ?? '');
  const [disciplinaIds, setDisciplinaIds] = useState(
    disciplina?.disciplina_id ? [disciplina.disciplina_id] : [],
  );

  const params = {
    instituicao: instituicao ?? { id: instituicaoId },
    cursoTutelado: cursoTutelado ?? { id: cursoId },
    cursoClasse: cursoClasse ?? { id: classeId },
    cursoClasseTurno: cursoClasseTurno ?? { id: turnoId },
  };

  return (
    <Form
      {...update.form({
        instituicao: instituicaoId,
        cursoTutelado: cursoId,
        cursoClasse: classeId,
        cursoClasseTurno: turnoId,
        classeTurnoDisciplina: disciplina.id,
      })}
      transform={(data) => ({
        ...data,
        ano_lectivo_id: anoLectivoId,
      })}
    >
      {({ errors, processing }) => (
        <DisciplinaForm
          params={params}
          disciplina={disciplina}
          disciplinas={disciplina?.disciplina ? [disciplina.disciplina] : []}
          disciplinaIds={disciplinaIds}
          setDisciplinaIds={setDisciplinaIds}
          errors={errors}
          processing={processing}
          anosLectivos={anosLectivos}
          anoLectivoId={anoLectivoId}
          setAnoLectivoId={setAnoLectivoId}
          isEdit
        />
      )}
    </Form>
  );
}
