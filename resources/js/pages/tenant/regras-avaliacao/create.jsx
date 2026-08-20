import { useForm } from '@inertiajs/react';
import { RegraAvaliacaoForm } from './components/regras-form';
import { Head } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/Tenant/RegraAvaliacaoController';

export default function Create({ niveisEnsino, classesPorNivel }) {
  const { data, setData, errors, post, processing } = useForm({
    classe_id: '',
    nivel_ensino_id: '',
    nome: '',
    media_minima_aprovacao: '10',
    frequencia_minima: '75',
    nota_minima_recurso: '10',
    max_disciplinas_negativas: '2',
    permite_recurso: true,
    activo: true,
  });

  const submit = (e) => {
    e.preventDefault();
    post(store().url);
  };

  return (
    <>
      <Head title="Nova Regra de Avaliação" />
      <RegraAvaliacaoForm
        title="Nova Regra de Avaliação"
        submitLabel="Criar Regra"
        data={data}
        setData={setData}
        errors={errors}
        processing={processing}
        submitFn={submit}
        niveisEnsino={niveisEnsino}
        classesPorNivel={classesPorNivel}
      />
    </>
  );
}
