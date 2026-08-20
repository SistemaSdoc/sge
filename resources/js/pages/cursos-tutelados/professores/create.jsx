import { Form } from '@inertiajs/react';
import { router, usePage } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoProfessorController';
import { show } from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoController';
import ProfessorForm from './components/professor-form';
import { useState } from 'react';

export default function Create() {
  const { professores, instituicaoId, cursoTuteladoId } = usePage().props;
  const [professorId, setProfessorId] = useState('');
  const [tipo, setTipo] = useState('principal');
  const [coordenador, setCoordenador] = useState(false);

  return (
    <Form
      {...store.form({
        instituicao: instituicaoId,
        cursoTutelado: cursoTuteladoId,
      })}
      transform={(data) => ({ ...data, professor_id: professorId, tipo, coordenador, })}
      onSuccess={() =>
        router.visit(
          show({
            instituicao: instituicaoId,
            cursoTutelado: cursoTuteladoId,
          }),
        )
      }
    >
      {({ errors, processing }) => (
        <ProfessorForm
          professores={professores}
          professorId={professorId}
          setProfessorId={setProfessorId}
          coordenador={coordenador}
          setCoordenador={setCoordenador}
          tipo={tipo}
          setTipo={setTipo}
          errors={errors}
          processing={processing}
        />
      )}
    </Form>
  );
}
