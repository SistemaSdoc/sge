import { Form } from '@inertiajs/react';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AlunoForm from './components/aluno-form';
import {
  update,
  index,
} from '@/actions/App/Http/Controllers/Tenant/AlunoController';

export default function Edit() {
  const { aluno, cursos = [], classes = [], turnos = [], turmas = [] } = usePage().props;

  const [cursoId, setCursoId] = useState(
    aluno.curso_tutelado_id ? String(aluno.curso_tutelado_id) : undefined,
  );

  const [classeId, setClasseId] = useState(
    aluno.curso_classe_id ? String(aluno.curso_classe_id) : undefined,
  );
  const [cursoClasseTurnoId, setCursoClasseTurnoId] = useState(
    aluno.curso_classe_turno_id ? String(aluno.curso_classe_turno_id) : undefined,
  );
  const [turmaId, setTurmaId] = useState(
    aluno.turma_id ? String(aluno.turma_id) : undefined,
  );

  const classesFiltradas = cursoId
    ? classes.filter(
        (cursoClasse) =>
          String(cursoClasse.curso_tutelado_id) === String(cursoId),
      )
    : [];

  const turnosFiltrados = classeId
    ? turnos.filter(
        (turno) => String(turno.curso_classe_id) === String(classeId),
      )
    : [];

  const turmasFiltradas = cursoClasseTurnoId
    ? turmas.filter(
        (turma) =>
          String(turma.curso_classe_turno_id) === String(cursoClasseTurnoId),
      )
    : [];

  const handleClasseChange = (value) => {
    setClasseId(value);
    setCursoClasseTurnoId(undefined);
    setTurmaId(undefined);
  };

  const handleCursoChange = (value) => {
    setCursoId(value);
    setClasseId(undefined);
    setCursoClasseTurnoId(undefined);
    setTurmaId(undefined);
  };

  const handleTurnoChange = (value) => {
    setCursoClasseTurnoId(value);
    setTurmaId(undefined);
  };

  return (
    <Form
      action={update({ id: aluno.id })}
      method="patch"
      transform={(data) => ({ ...data, turma_id: turmaId })}
      onSuccess={() => router.visit(index({}))}
    >
      {({ errors, processing }) => (
        <AlunoForm
          errors={errors}
          processing={processing}
          cursos={cursos}
          cursoId={cursoId}
          setCursoId={handleCursoChange}
          classes={classesFiltradas}
          turnos={turnosFiltrados}
          turmas={turmasFiltradas}
          classeId={classeId}
          setClasseId={handleClasseChange}
          cursoClasseTurnoId={cursoClasseTurnoId}
          setCursoClasseTurnoId={handleTurnoChange}
          turmaId={turmaId}
          setTurmaId={setTurmaId}
          defaultValues={{
            nome: aluno.nome,
            bi: aluno.bi,
            email: aluno.email,
            matricula: aluno.matricula,
          }}
        />
      )}
    </Form>
  );
}
