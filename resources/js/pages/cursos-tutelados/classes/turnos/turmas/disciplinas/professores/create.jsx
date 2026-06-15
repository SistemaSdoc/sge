import { usePage, useForm } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/InstituicaoCurso/TurmaDisciplinaProfessorController';
import ProfessorForm from './components/professor-form';

export default function Create() {
  const {
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
    turma,
    classeTurnoDisciplina,
    professores,
    disciplinas,
  } = usePage().props;

  const { data, setData, post, errors, processing } = useForm({
    professor_id: '',
    disciplina_id: classeTurnoDisciplina,
  });

  const submit = (e) => {
    e.preventDefault();
    post(
      store({
        instituicao,
        cursoTutelado,
        cursoClasse,
        cursoClasseTurno,
        turma,
        classeTurnoDisciplina,
      }).url,
    );
  };

  return (
    <ProfessorForm
      disciplinas={disciplinas ?? []}
      professores={professores ?? []}
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      submitFn={submit}
    />
  );
}
