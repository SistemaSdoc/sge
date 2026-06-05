import { useForm } from "@inertiajs/react";
import { CursoForm } from "./components/curso-form";

export default function Create() {
  const { post, data, setData, processing, errors } = useForm({
    nome: "",
    duracao_anos: "",
    descricao: "",
  });

  return (
    <CursoForm
      title="Adicionar Curso"
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      submitFn={(e) => {
        e.preventDefault();
        post('/cursos');
      }}
    />
  );
}

