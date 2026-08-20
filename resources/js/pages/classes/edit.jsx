import { useForm } from '@inertiajs/react';
import { ClasseForm } from './components/classe-form';
import { update } from '@/actions/App/Http/Controllers/Tenant/ClasseController';

export default function Edit({ can = {}, classe }) {
  const { put, data, setData, processing, errors } = useForm({
    nome: classe.nome,
    ordem: classe.ordem,
  });

  return (
    <ClasseForm
      title="Editar Classe"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      can={can}
      submitFn={(e) => {
        e.preventDefault();
        put(update(classe.id).url);
      }}
    />
  );
}
