import { Form } from '@inertiajs/react';
import { router, usePage } from '@inertiajs/react';
import { update } from '@/actions/App/Http/Controllers/GrupoPapController';
import { show } from '@/actions/App/Http/Controllers/ClasseTurnoTurmaController';
import GrupoPapForm from '../pap/components/grupo-pap-form';
import { useState } from 'react';

export default function Edit() {
  const {
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
    turma,
    professores,
    alunos,
    grupoPap,
  } = usePage().props;

  const [professorTutorId, setProfessorTutorId] = useState(
    grupoPap.professor_tutor_id,
  );

  const [alunoIds, setAlunoIds] = useState(
    grupoPap.alunos?.map((id) => String(id)) ?? [],
  );

  return (
    <Form
      action={update({
        instituicao: instituicao.id,
        cursoTutelado: cursoTutelado.id,
        cursoClasse: cursoClasse.id,
        cursoClasseTurno: cursoClasseTurno.id,
        turma: turma.id,
        grupoPap: grupoPap.id,
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
          errors={errors}
          processing={processing}
          professores={professores}
          alunos={alunos}
          professorTutorId={professorTutorId}
          setProfessorTutorId={setProfessorTutorId}
          alunoIds={alunoIds}
          setAlunoIds={setAlunoIds}
          grupoPap={grupoPap}
        />
      )}
    </Form>
  );
}
