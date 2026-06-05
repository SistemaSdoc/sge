import { router } from "@inertiajs/react";
import { TurnoTable } from "./components/turno-table"

interface Turno {
  id: number;
  nome: string;
}

interface props {
  turnos: Turno[];
  deleteFn: (id: number) => void;
}



export default function Index({ turnos, deleteFn }: props) {
  const isEmpty = !turnos || turnos.length === 0;

  const excluir = (id: number) => {
    if (confirm("Tem certeza que deseja excluir esse turno?")) {
      deleteFn(id);
    }
  };

  return (
    <div>
      <TurnoTable
        turnos={turnos}
        deleteFn={deleteFn}
      />
    </div>
  )
}