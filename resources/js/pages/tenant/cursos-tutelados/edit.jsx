import { useForm } from '@inertiajs/react';
import { CursoForm } from './components/forms/edit.form';
import { update } from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoController';

export default function Edit({
  instituicao,
  cursoTutelado,
  classes,
  niveisEnsino,
  tenantsTutores,
}) {
  const { data, setData, put, processing, errors } = useForm({
    nome: cursoTutelado.curso.nome,
    nivel_ensino_id: cursoTutelado?.nivel_ensino_id ?? '',
    duracao_anos: cursoTutelado?.curso?.duracao_anos ?? '',
    tenant_tutor_id: cursoTutelado?.tenant_tutor_id ?? '',
    classes: Array.isArray(cursoTutelado?.classes) ? cursoTutelado.classes : [],
  });

  const parms = {
    instituicao,
    cursoTutelado,
  };

  const handleSubmit = (e) => {
    e.preventDefault();

    put(update(parms).url, { preserveScroll: true });
  };

  return (
    <div className="mx-auto w-full max-w-5xl px-4 py-6">
      <CursoForm
        title="Editar curso tutelado"
        instituicao={instituicao}
        classes={classes}
        niveisEnsino={niveisEnsino}
        tenantsTutores={tenantsTutores}
        data={data}
        setData={setData}
        errors={errors}
        processing={processing}
        onSubmit={handleSubmit}
      />
    </div>
  );
}
