import { useForm } from '@inertiajs/react';
import { ProfessorForm } from './components/professor-form';
import { update } from '@/actions/App/Http/Controllers/Tenant/ProfessorController';

export default function Edit({ professor }) {
  const { put, data, setData, processing, errors } = useForm({
    nome: professor.user.nome,
    email: professor.user.email,
    bi: professor.user.bi,
    telefone: professor.user.telefone,
    especialidade: professor.especialidade,
    nivel_academico: professor.nivel_academico,
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
        put(update(professor.id).url);
      }}
    />
  );
}
