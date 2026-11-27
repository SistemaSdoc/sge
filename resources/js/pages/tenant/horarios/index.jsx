import { Head, router } from '@inertiajs/react';
import { CalendarClock } from 'lucide-react';
import { index } from '@/actions/App/Http/Controllers/Tenant/HorarioController';
import { EmptyState } from '@/components/empty-state';
import {
  Combobox,
  ComboboxContent,
  ComboboxEmpty,
  ComboboxInput,
  ComboboxItem,
  ComboboxList,
} from '@/components/ui/combobox';
import { Label } from '@/components/ui/label';
import { HorarioTable } from './components/horario-table';

export default function Horarios({
  aulas = [],
  perfil,
  professores = [],
  professorSeleccionado,
  anosLectivos = [],
  anoLectivoActual,
}) {
  const handleAnoLectivoChange = (value) => {
    const data = { ano_lectivo_id: value };

    if (perfil === 'director' && professorSeleccionado) {
      data.professor_id = professorSeleccionado;
    }

    router.visit(index().url, {
      data,
      preserveScroll: true,
      preserveState: true,
    });
  };

  const handleProfessorChange = (professor) => {
    const data = { ano_lectivo_id: anoLectivoActual };

    if (professor?.id) {
      data.professor_id = professor.id;
    }

    router.visit(index().url, {
      data,
      preserveScroll: true,
      preserveState: true,
    });
  };

  const professorNome = professores.find(
    (professor) => professor.id === professorSeleccionado,
  )?.nome;
  const professorActual =
    professores.find((professor) => professor.id === professorSeleccionado) ??
    null;

  return (
    <div className="mx-auto w-full max-w-7xl p-4 md:p-6">
      <Head title="Horários" />
      <HorarioTable
        aulas={aulas}
        perfil={perfil}
        professores={professores}
        professorSeleccionado={professorSeleccionado}
        onProfessorChange={handleProfessorChange}
        professorNome={professorNome}
        anosLectivos={anosLectivos}
        anoLectivoActual={anoLectivoActual}
        onAnoLectivoChange={handleAnoLectivoChange}
      />
    </div>
  );
}
