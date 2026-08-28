import { Form } from '@inertiajs/react';
import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/Tenant/GrupoPapTemaController';
import { show } from '@/actions/App/Http/Controllers/Tenant/GrupoPapController';
import { TemaForm } from './components/tema-form';

export default function Create() {
  const {
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
    grupoPap,
    turma,
    form,
  } = usePage().props;

  const [professorTutorId, setProfessorTutorId] = useState(undefined);

  return (
    <Form
      action={store({
        instituicao: instituicao.id,
        cursoTutelado: cursoTutelado.id,
        cursoClasse: cursoClasse.id,
        cursoClasseTurno: cursoClasseTurno.id,
        turma: turma.id,
        grupoPap: grupoPap.id,
      })}
      transform={(data) => ({
        ...data,
      })}
      onSuccess={() =>
        router.visit(
          show({
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
        <TemaForm
          title="Definir Tema do Grupo"
          errors={errors}
          processing={processing}
          professores={form.professores}
          professorTutorId={professorTutorId}
          setProfessorTutorId={setProfessorTutorId}

        />
      )}
    </Form>
  );
}
