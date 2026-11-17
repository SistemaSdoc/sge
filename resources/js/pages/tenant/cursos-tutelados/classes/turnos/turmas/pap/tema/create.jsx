import { useForm, usePage } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/Tenant/Pap/TemaPapController';
import { TemaForm } from './components/tema-form';

export default function Create() {
  const {
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
    grupoPap,
    turma,
    form,
  } = usePage().props;

  const { data, setData, post, processing, errors } = useForm({
    professor_tutor_id: '',
    origem_tema: form.sugestoes_temas.length > 0 ? 'sugerido' : 'autoral',
    tema_sugerido_id: '',
    tema_grupo: '',
    problema: '',
    objectivo_geral: '',
    objectivos_especificos: '',
    estudo_caso: '',
  });

  return (
    <TemaForm
      title="Definir Proposta de Tema"
      description="Escolha ou proponha o tema e preencha os dados que orientam o trabalho de PAP."
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      professores={form.professores}
      sugestoesTemas={form.sugestoes_temas}
      submitFn={(event) => {
        event.preventDefault();
        post(
          store({
            instituicao: instituicao.id,
            cursoTutelado: cursoTutelado.id,
            cursoClasse: cursoClasse.id,
            cursoClasseTurno: cursoClasseTurno.id,
            turma: turma.id,
            grupoPap: grupoPap.id,
          }).url,
        );
      }}
    />
  );
}
