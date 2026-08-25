import { Form } from '@inertiajs/react';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import {
  create,
  index,
  store,
} from '@/actions/App/Http/Controllers/Tenant/InscricaoController';

import InscricaoForm from './components/inscricao-form';

export default function Create() {
  const {
    cursos,
    anosLectivos = [],
    anoLectivoActual,
    entity_label: entityLabel,
    tem_nota_teste: temNotaTeste,
  } = usePage().props;

  const [cursoId, setCursoId] = useState(undefined);
  const [classeId, setClasseId] = useState(undefined);
  const [cursoClasseTurnoId, setCursoClasseTurnoId] = useState(undefined);
  const [turmaId, setTurmaId] = useState(undefined);
  const [notaTeste, setNotaTeste] = useState('');

  const cursoSelecionado = cursos?.find(
    (c) => String(c.id) === String(cursoId),
  );

  const classeSelecionada = cursoSelecionado?.classes?.find(
    (cl) => String(cl.id) === String(classeId),
  );

  const turnoSelecionado = classeSelecionada?.turnos?.find(
    (t) => String(t.id) === String(cursoClasseTurnoId),
  );

  return (
    <Form
      action={store.url()}
      method="post"
      transform={(data) => {
        const filiacao =
          data.filiacao ||
          [data.nome_pai, data.nome_mae].filter(Boolean).join(' e ') ||
          null;

        return {
          ...data,
          curso_classe_turno_id: cursoClasseTurnoId,
          turma_id: turmaId || undefined,
          nota_teste: notaTeste || undefined,
          numero_estudante:
            data.numero_estudante ||
            `INS-${new Date().getFullYear()}-${Date.now().toString().slice(-4)}`,
          genero: data.genero || 'M',
          filiacao,
          ano_lectivo_id: data.ano_lectivo_id || anoLectivoActual || undefined,
        };
      }}
      onSuccess={() => router.visit(index.url())}
    >
      {({ errors, processing }) => (
        <InscricaoForm
          errors={errors}
          processing={processing}
          cursos={cursos}
          cursoId={cursoId}
          setCursoId={(val) => {
            setCursoId(val);
            setClasseId(undefined);
            setCursoClasseTurnoId(undefined);
            setTurmaId(undefined);
          }}
          cursoSelecionado={cursoSelecionado}
          classeId={classeId}
          setClasseId={(val) => {
            setClasseId(val);
            setCursoClasseTurnoId(undefined);
            setTurmaId(undefined);
          }}
          cursoClasseTurnoId={cursoClasseTurnoId}
          setCursoClasseTurnoId={(val) => {
            setCursoClasseTurnoId(val);
            setTurmaId(undefined);
          }}
          turnoSelecionado={turnoSelecionado}
          turmaId={turmaId}
          setTurmaId={setTurmaId}
          notaTeste={notaTeste}
          setNotaTeste={setNotaTeste}
          entityLabel={entityLabel}
          temNotaTeste={temNotaTeste}
          anoLectivoActual={anoLectivoActual}
        />
      )}
    </Form>
  );
}
