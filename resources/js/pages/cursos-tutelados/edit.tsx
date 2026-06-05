import { router } from "@inertiajs/react"
import { CursoForm } from "./components/tabs/forms/edit.form"

interface Instituicao {
  id: number
  nome: string
}

interface CursoTutelado {
  id: number
  curso: {
    id: number
    nome: string
    duracao_anos?: number
  }
  instituicao_tutora: {
    id: number
    nome: string
  }
  classes: number[]
}

interface EditProps {
  instituicao: Instituicao
  cursoTutelado: CursoTutelado
  classes: Array<{ id: number; nome: string }>
  instituicoes: Array<{ id: number; nome: string }>
}

export default function Edit({ instituicao, cursoTutelado, classes, instituicoes }: EditProps) {
  return (
    <div className="w-full max-w-5xl mx-auto px-4 py-6">
      <h1 className="text-2xl font-semibold mb-4">Editar Curso Tutelado</h1>
      <CursoForm
        title="Editar curso tutelado"
        classes={classes}
        instituicoes={instituicoes}
        isLoading={false}
        defaultValues={{
          duracao_anos: cursoTutelado.curso.duracao_anos ?? '',
          instituicao_tutora_id: String(cursoTutelado.instituicao_tutora.id),
          classes: cursoTutelado.classes ?? [],
        }}
        submitFn={(formData) => {
          router.put(
            `/instituicoes/${instituicao.id}/cursos-tutelados/${cursoTutelado.id}`,
            formData,
            {
              preserveScroll: true,
              onSuccess: () => {
                router.visit(`/instituicoes/${instituicao.id}/cursos-tutelados/${cursoTutelado.id}`)
              },
            }
          )
        }}
      />
    </div>
  )
}
