import { useForm } from "@inertiajs/react";
import { CursoForm } from "./components/curso-form";

interface Curso {
  id: number;
  nome: string;
  duracao_anos: number;
  descricao: string;
}

export default function Edit({ curso }: { curso: Curso }) {
  const { put, data, setData, processing, errors } = useForm({
    nome: curso.nome,
    duracao_anos: curso.duracao_anos,
    descricao: curso.descricao,
  });

  return (
    <CursoForm
      title="Editar Curso"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      submitFn={(e) => {
        e.preventDefault();
        put(`/cursos/${curso.id}`);
      }}
    />
  );
}