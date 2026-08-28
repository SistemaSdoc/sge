import { Form } from '@inertiajs/react';
import { router, usePage } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/Tenant/ElementoGrupoPapController';
import { show } from '@/actions/App/Http/Controllers/Tenant/GrupoPapController';
import { useState } from 'react';
import { CreateForm } from './components/create.form';

export default function Create() {
  const {
    instituicao,
    colegio,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
    turma,
    grupoPap,
    alunos,
  } = usePage().props;

  const [alunoIds, setAlunoIds] = useState([]);

  return (
    <Form
      {...store.form({
        instituicao: instituicao.id,
        cursoTutelado: cursoTutelado.id,
        cursoClasse: cursoClasse.id,
        cursoClasseTurno: cursoClasseTurno.id,
        turma: turma.id,
        grupoPap: grupoPap.id,
      })}
      transform={(data) => ({
        ...data,
        alunos: alunoIds,
      })}
      onSuccess={() =>
        router.visit(
          show.url({
            instituicao: instituicao.id,
            cursoTutelado: cursoTutelado.id,
            cursoClasse: cursoClasse.id,
            cursoClasseTurno: cursoClasseTurno.id,
            turma: turma.id,
            grupoPap: grupoPap.id,
          }),
        )
      }
    >
      {({ errors, processing }) => (
        <CreateForm
          errors={errors}
          processing={processing}
          alunos={alunos}
          alunoIds={alunoIds}
          setAlunoIds={setAlunoIds}
        />
      )}
    </Form>
  );
}
