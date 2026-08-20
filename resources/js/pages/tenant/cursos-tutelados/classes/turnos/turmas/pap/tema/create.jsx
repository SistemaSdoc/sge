import { Form } from '@inertiajs/react';
import { router, usePage } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/Tenant/GrupoPapTemaController';
import { TemaForm } from './components/tema-form';

export default function Create() {
  const {
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
    grupoPap,
    turma,
  } = usePage().props;

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
          }),
        )
      }
    >
      {({ errors, processing }) => (
        <TemaForm
          title="Definir Tema do Grupo"
          errors={errors}
          processing={processing}
        />
      )}
    </Form>
  );
}
