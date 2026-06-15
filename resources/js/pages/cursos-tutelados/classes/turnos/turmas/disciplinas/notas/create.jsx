import { Form, usePage } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import LancamentosTable from './components/lancamentos-table';

export default function Create({
  instituicaoId,
  cursoId,
  classeId,
  turnoId,
  turmaId,
}) {
  const { data } = usePage().props;

  if (!data?.alunos || data.alunos.length === 0) {
    return (
      <div className="flex justify-center py-20">
        <span className="text-sm text-muted-foreground">
          Sem dados disponíveis.
        </span>
      </div>
    );
  }

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      <Form
        action={`/instituicoes/${instituicaoId}/cursos-tutelados/${cursoId}/classes/${classeId}/turnos/${turnoId}/turmas/${turmaId}/disciplinas/${data.disciplina.id}/notas`}
        method="post"
        options={{ preserveScroll: true }}
      >
        {({ processing }) => (
          <LancamentosTable
            data={data}
            isPending={processing}
            instituicaoId={instituicaoId}
            cursoId={cursoId}
            classeId={classeId}
            turnoId={turnoId}
            turmaId={turmaId}
            disciplinaId={data.disciplina.id}
          />
        )}
      </Form>
    </div>
  );
}
