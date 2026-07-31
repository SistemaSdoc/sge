import { Form } from '@inertiajs/react';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { create, index, store } from '@/routes/inscricoes';
import InscricaoForm from './components/inscricao-form';

export default function Create() {
  const {
    cursos,
    anosLectivos = [],
    anoLectivoId: initialAnoLectivoId,
  } = usePage().props;

  const [cursoId, setCursoId] = useState(undefined);
  const [cursoClasseTurnoId, setCursoClasseTurnoId] = useState(undefined);
  const [anoLectivoSelecionado, setAnoLectivoSelecionado] = useState(
    initialAnoLectivoId ?? '',
  );

  const cursoSelecionado = cursos?.find(
    (c) => String(c.id) === String(cursoId),
  );

  const handleAnoLectivoChange = (value) => {
    if (!value) return;
    setAnoLectivoSelecionado(value);
    router.visit(window.location.pathname, {
      data: {
        ano_lectivo_id: value,
      },
      preserveScroll: true,
    });
  };

  return (
    <Form
      action={store.url()}
      method="post"
      transform={(data) => ({
        ...data,
        curso_classe_turno_id: cursoClasseTurnoId,
        ano_lectivo_id: anoLectivoSelecionado,
      })}
      onSuccess={() =>
        router.visit(index.url({ ano_lectivo_id: anoLectivoSelecionado }))
      }
    >
      {({ errors, processing }) => (
        <InscricaoForm
          errors={errors}
          processing={processing}
          cursos={cursos}
          cursoId={cursoId}
          setCursoId={(val) => {
            setCursoId(val);
            setCursoClasseTurnoId(undefined);
          }}
          cursoSelecionado={cursoSelecionado}
          cursoClasseTurnoId={cursoClasseTurnoId}
          setCursoClasseTurnoId={setCursoClasseTurnoId}
          anosLectivos={anosLectivos}
          anoLectivoSelecionado={anoLectivoSelecionado}
          onAnoLectivoChange={handleAnoLectivoChange}
        />
      )}
    </Form>
  );
}
