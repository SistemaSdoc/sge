import { Head, router } from '@inertiajs/react';
import { index } from '@/actions/App/Http/Controllers/Tenant/HorarioController';
import { HorarioTable } from './components/horario-table';

export default function Horarios({
  aulas = [],
  perfil,
  anosLectivos = [],
  anoLectivoActual,
}) {
  const handleAnoLectivoChange = (value) => {
    router.visit(index().url, {
      data: { ano_lectivo_id: value },
      preserveScroll: true,
      preserveState: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-4 md:p-6">
      <Head title="Horários" />

      <HorarioTable
        aulas={aulas}
        perfil={perfil}
        anosLectivos={anosLectivos}
        anoLectivoActual={anoLectivoActual}
        onAnoLectivoChange={handleAnoLectivoChange}
      />
    </div>
  );
}
