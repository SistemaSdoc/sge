import { Form, useForm } from '@inertiajs/react';
import { router, usePage } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/Tenant/BancaJuriPapController';
import { show } from '@/actions/App/Http/Controllers/Tenant/GrupoPapController';
import { CreateForm } from './components/create.form';

export default function Create() {
  const {
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
    turma,
    grupoPap,
    professores,
    funcoes,
  } = usePage().props;

  const { data, setData, errors, processing } = useForm({
    professor_id: '',
    funcao: '',
  });

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
      transform={(formData) => ({
        ...formData,
        professor_id: data.professor_id,
        funcao: data.funcao,
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
      {({ errors: formErrors, processing: formProcessing }) => (
        <CreateForm
          data={data}
          setData={setData}
          errors={{ ...formErrors, ...errors }}
          processing={formProcessing || processing}
          professores={professores}
          funcoes={funcoes}
        />
      )}
    </Form>
  );
}
