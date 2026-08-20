import { Form } from '@inertiajs/react';
import { router, usePage } from '@inertiajs/react';
import { update } from '@/actions/App/Http/Controllers/Tenant/GrupoPapController';
import { show } from '@/actions/App/Http/Controllers/Tenant/ClasseTurnoTurmaController';
import GrupoPapForm from '../pap/components/grupo-pap-form';
import { useState } from 'react';

export default function Edit() {
  const {
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
    turma,
    form,
    grupoPap,
  } = usePage().props;

  const [professorTutorId, setProfessorTutorId] = useState(
    form.grupoPap.professor_tutor_id,
  );

  const [alunoIds, setAlunoIds] = useState(
    form.grupoPap.alunos?.map((id) => String(id)) ?? [],
  );

  return (
    <Form
      action={update({
        instituicao: instituicao.id,
        cursoTutelado: cursoTutelado.id,
        cursoClasse: cursoClasse.id,
        cursoClasseTurno: cursoClasseTurno.id,
        turma: turma.id,
        grupoPap: form.grupoPap.id,
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
          title="Editar grupo PAP"
          errors={errors}
          processing={processing}
          professores={form.professores}
          alunos={form.alunos}
          professorTutorId={professorTutorId}
          setProfessorTutorId={setProfessorTutorId}
          alunoIds={alunoIds}
          setAlunoIds={setAlunoIds}
          grupoPap={form.grupoPap}
        />
      )}
    </Form>
  );
}
