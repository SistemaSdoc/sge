import { useForm, router } from '@inertiajs/react';
import { createIndependente, storeIndependente } from '@/actions/App/Http/Controllers/Tenant/GrupoPapController';
import GrupoPapForm from './components/grupo-pap-form';

export default function CreateIndependente({
  instituicao,
  cursosTutelados = [],
  classes = [],
  turnos = [],
  turmas = [],
  professores = [],
  alunos = [],
}) {
  const { data, setData, post, processing, errors } = useForm({
    curso_tutelado_id: '',
    curso_classe_id: '',
    curso_classe_turno_id: '',
    turma_id: '',
    nome_grupo: '',
    professor_tutor_id: '',
    alunos: [],
  });

  const reload = (only, extra) =>
    router.visit(createIndependente(instituicao.id).url, {
      data: { ...data, ...extra },
      only,
      preserveState: true,
      preserveScroll: true,
    });

  const setCursoTuteladoId = (value) => {
    setData((prev) => ({
      ...prev,
      curso_tutelado_id: value,
      curso_classe_id: '',
      curso_classe_turno_id: '',
      turma_id: '',
    }));
    reload(['classes'], { curso_tutelado_id: value });
  };

  const setCursoClasseId = (value) => {
    setData((prev) => ({
      ...prev,
      curso_classe_id: value,
      curso_classe_turno_id: '',
      turma_id: '',
    }));
    reload(['turnos'], { curso_classe_id: value });
  };

  const setCursoClasseTurnoId = (value) => {
    setData((prev) => ({
      ...prev,
      curso_classe_turno_id: value,
      turma_id: '',
    }));
    reload(['turmas'], { curso_classe_turno_id: value });
  };

  const setTurmaId = (value) => {
    setData((prev) => ({ ...prev, turma_id: value }));
    reload(['form'], { turma_id: value }); // traz professores + alunos dessa turma
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    post(storeIndependente(instituicao.id).url);
  };

  return (
    <form onSubmit={handleSubmit}>
      <GrupoPapForm
        title="Criar grupo PAP"
        errors={errors}
        processing={processing}
        cursosTutelados={cursosTutelados}
        classes={classes}
        turnos={turnos}
        turmas={turmas}
        cursoTuteladoId={data.curso_tutelado_id}
        setCursoTuteladoId={setCursoTuteladoId}
        cursoClasseId={data.curso_classe_id}
        setCursoClasseId={setCursoClasseId}
        cursoClasseTurnoId={data.curso_classe_turno_id}
        setCursoClasseTurnoId={setCursoClasseTurnoId}
        turmaId={data.turma_id}
        setTurmaId={setTurmaId}
        professores={professores}
        alunos={alunos}
        professorTutorId={data.professor_tutor_id}
        setProfessorTutorId={(v) => setData('professor_tutor_id', v)}
        alunoIds={data.alunos}
        setAlunoIds={(ids) => setData('alunos', ids)}
      />
    </form>
  );
}