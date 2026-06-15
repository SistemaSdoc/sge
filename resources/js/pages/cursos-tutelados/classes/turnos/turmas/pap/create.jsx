import { Form } from '@inertiajs/react';
import { router, usePage } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/GrupoPapController';
import { show } from '@/actions/App/Http/Controllers/ClasseTurnoTurmaController';
import GrupoPapForm from '../pap/components/grupo-pap-form';
import { useState } from 'react';

export default function Create() {
  const {
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
    turma,
    professores,
    alunos,
  } = usePage().props;

  const [professorTutorId, setProfessorTutorId] = useState(undefined);
  const [alunoIds, setAlunoIds] = useState([]);

  return (
    <Form
      action={store({
        instituicao: instituicao.id,
        cursoTutelado: cursoTutelado.id,
        cursoClasse: cursoClasse.id,
        cursoClasseTurno: cursoClasseTurno.id,
        turma: turma.id,
      })}
      transform={(data) => ({
        ...data,
        professor_tutor_id: professorTutorId,
        alunos: alunoIds,
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
        <GrupoPapForm
          title="Criar grupo PAP"
          errors={errors}
          processing={processing}
          professores={professores}
          alunos={alunos}
          professorTutorId={professorTutorId}
          setProfessorTutorId={setProfessorTutorId}
          alunoIds={alunoIds}
          setAlunoIds={setAlunoIds}
        />
      )}
    </Form>
  );
}
