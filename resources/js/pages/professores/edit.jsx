import { useForm } from '@inertiajs/react';
import { ProfessorForm } from './components/professor-form';

export default function Edit({ professor }) {
  const { put, data, setData, processing, errors } = useForm({
    nome: professor.user.nome,
    email: professor.user.email,
    bi: professor.user.bi,
    telefone: professor.user.telefone,
    especialidade: professor.especialidade,
  });

  return (
    <ProfessorForm
      title="Editar Professor"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      submitFn={(e) => {
        e.preventDefault();
        console.log('professor:', professor);
        console.log('professor.id:', professor.id);
        console.log('data:', data);
        put(`/professores/${professor.id}`);
      }}
    />
  );
}
