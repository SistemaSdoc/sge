import { useForm } from '@inertiajs/react';
import { TurmaForm } from '../../../components/classes/turnos/turmas/turma-form';

export default function Create({ instituicao, cursoTutelado, cursoClasse, cursoClasseTurno }) {
  const { data, setData, post, processing, errors } = useForm({
    nome: '',
    max_alunos: '',
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    post(
      `/instituicoes/${instituicao.id}/cursos-tutelados/${cursoTutelado.id}/classes/${cursoClasse.id}/turnos/${cursoClasseTurno.id}/turmas`,
      { preserveScroll: true },
    );
  };

  return (
    <TurmaForm
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      onSubmit={handleSubmit}
    />
  );
}