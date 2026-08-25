import { useForm } from '@inertiajs/react';
import { RegraAvaliacaoForm } from './components/regras-form';
import { Head } from '@inertiajs/react';
import { update } from '@/actions/App/Http/Controllers/Tenant/RegraAvaliacaoController';

export default function Edit({
  regraAvaliacao,
  niveisEnsino,
  classesPorNivel,
}) {
  const { data, setData, errors, put, processing } = useForm({
    classe_id: regraAvaliacao.classe_id || '',
    nivel_ensino_id: regraAvaliacao.nivel_ensino_id || '',
    nome: regraAvaliacao.nome,
    media_minima_aprovacao: regraAvaliacao.media_minima_aprovacao.toString(),
    frequencia_minima: regraAvaliacao.frequencia_minima.toString(),
    nota_minima_recurso: regraAvaliacao.nota_minima_recurso?.toString() || '10',
    max_disciplinas_negativas:
      regraAvaliacao.max_disciplinas_negativas?.toString() || '',
    permite_recurso: regraAvaliacao.permite_recurso,
    activo: regraAvaliacao.activo,
  });

  const submit = (e) => {
    e.preventDefault();
    put(update(regraAvaliacao.id).url);
  };

  return (
    <>
      <Head title="Editar Regra de Avaliação" />
      <RegraAvaliacaoForm
        title="Editar Regra de Avaliação"
        submitLabel="Atualizar Regra"
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
