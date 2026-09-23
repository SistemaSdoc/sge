import { Form } from '@inertiajs/react';
import { usePage, router } from '@inertiajs/react';
import { useState } from 'react';

import { update } from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoProfessorController';
import { show } from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoController';

import ProfessorForm from './components/professor-form';

export default function Edit() {
  const { vinculo, instituicaoId, cursoTuteladoId } = usePage().props;

  const [tipo, setTipo] = useState(vinculo.tipo);
  const [coordenador, setCoordenador] = useState(vinculo.coordenador ?? false);
  const [opap, setOpap] = useState(vinculo.opap ?? false);

  return (
    <Form
      {...update.form({
        instituicao: instituicaoId,
        cursoTutelado: cursoTuteladoId,
        professor: vinculo.id,
      })}
      transform={(data) => ({
        ...data,
        tipo,
        coordenador,
        opap,
      })}
      onSuccess={() =>
        router.visit(
          show({
            instituicao: instituicaoId,
            cursoTutelado: cursoTuteladoId,
          }),
        )
      }
    >
      {({ errors, processing }) => (
        <ProfessorForm
          mode="edit"
          professorNome={vinculo.nome}
          tipo={tipo}
          setTipo={setTipo}
          coordenador={coordenador}
          setCoordenador={setCoordenador}
          opap={opap}
          setOpap={setOpap}
          errors={errors}
          processing={processing}
        />
      )}
    </Form>
  );
}
