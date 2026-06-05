import { router } from '@inertiajs/react';
import { TurnoTable } from './components/turno-table';

export default function Index({ turnos, deleteFn }) {
  const isEmpty = !turnos || turnos.length === 0;

  const excluir = (id) => {
    if (confirm('Tem certeza que deseja excluir esse turno?')) {
      deleteFn(id);
    }
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <TurnoTable turnos={turnos} deleteFn={deleteFn} />
    </div>
  );
}
