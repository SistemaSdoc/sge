import { Form } from '@inertiajs/react';
import { usePage, router } from '@inertiajs/react';
import { useState } from 'react';

import { update } from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoProfessorController';
import { show } from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoController';

import ProfessorForm from './components/professor-form';

export default function Edit() {
  const { vinculo, professores, instituicaoId, cursoTuteladoId } =
    usePage().props;

  const [professorId] = useState(vinculo.professor_id);
  const [tipo, setTipo] = useState(vinculo.tipo);
  const [coordenador, setCoordenador] = useState(vinculo.coordenador ?? false);

  return (
    <Form
      {...update(vinculo.id).form()}
      transform={(data) => ({
        ...data,
        tipo,
        coordenador,
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
          professores={professores}
          professorId={professorId}
          setProfessorId={() => {}}
          tipo={tipo}
          setTipo={setTipo}
          coordenador={coordenador}
          setCoordenador={setCoordenador}
          errors={errors}
          processing={processing}
          disableProfessor
        />
      )}
    </Form>
  );
}
