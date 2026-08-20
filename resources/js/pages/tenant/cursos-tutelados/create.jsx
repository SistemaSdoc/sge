import { useForm } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoController';
import { CursoForm } from './components/forms/create.form';

export default function Create({ instituicao, classes, cursos, niveisEnsino }) {
  const { post, data, setData, processing, errors } = useForm({
    curso_id: '',
    nivel_ensino_id: '',
    classe_ids: [],
    nome: '',
    duracao_anos: '',
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    post(store({ instituicao: instituicao.id }), {
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-5xl px-4 py-6">
      <CursoForm
        title="Adicionar Curso"
        instituicao={instituicao}
        classes={classes}
        cursos={cursos}
        niveisEnsino={niveisEnsino}
        data={data}
        setData={setData}
        errors={errors}
        processing={processing}
        onSubmit={handleSubmit}
      />
    </div>
  );
}
