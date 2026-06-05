import { router } from "@inertiajs/react"
import { CursoForm } from "./components/tabs/forms/create.form"

interface InstituicaoProps {
  instituicao: {
    id: number
    nome: string
  }
  classes: Array<{ id: number; nome: string }>
  cursos: Array<{ id: number; nome: string }>
}

export default function Create({ instituicao, classes, cursos }: InstituicaoProps) {
  return (
    <div className="w-full max-w-5xl mx-auto px-4 py-6">
      <h1 className="text-2xl font-semibold mb-4">Adicionar Curso Tutelado</h1>
      <CursoForm
        title="Novo curso tutelado"
        classes={classes}
        cursos={cursos}
        defaultValues={{
          curso_id: '',
          modo: 'existente',
          classes: [],
          nome: '',
          duracao_anos: '',
        }}
        isLoading={false}
        submitFn={(formData) => {
          router.post(
            `/instituicoes/${instituicao.id}/cursos-tutelados`,
            formData,
            {
              preserveScroll: true,
              onSuccess: () => {
                router.visit(`/instituicoes/${instituicao.id}`)
              },
            }
          )
        }}
      />
    </div>
  )
}


