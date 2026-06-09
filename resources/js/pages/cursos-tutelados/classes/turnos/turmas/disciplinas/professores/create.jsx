import { Form, router, usePage, useForm } from "@inertiajs/react"
import { store } from "@/actions/App/Http/Controllers/InstituicaoCurso/TurmaDisciplinaProfessorController"
import { show } from "@/actions/App/Http/Controllers/ClasseTurnoTurmaController"
import ProfessorForm from "../../../../../components/classes/turnos/turmas/disciplinas/professores/professor-form"

export default function Create() {
  const {
    instituicaoId, cursoId, classeId, turnoId, turmaId,
    disciplinaId: classeTurnoDisciplinaId,
    professores, disciplinas
  } = usePage().props

  const { data, setData, errors, processing } = useForm({
    disciplina_id: classeTurnoDisciplinaId,
    professor_id: '',
  })

  return (
    <Form
      {...store.form({
        instituicao: instituicaoId,
        cursoTutelado: cursoId,
        cursoClasse: classeId,
        cursoClasseTurno: turnoId,
        turma: turmaId,
        classeTurnoDisciplina: classeTurnoDisciplinaId,
      })}
      data={data}
      onSuccess={() =>
        router.visit(
          show({
            instituicao: instituicaoId,
            cursoTutelado: cursoId,
            cursoClasse: classeId,
            cursoClasseTurno: turnoId,
            turma: turmaId,
          }),
        )
      }
    >
      {({ errors: formErrors, processing: formProcessing }) => (
        <ProfessorForm
          disciplinas={disciplinas ?? []}
          professores={professores ?? []}
          data={data}
          setData={setData}
          errors={{ ...formErrors, ...errors }}
          processing={formProcessing || processing}
        />
      )}
    </Form>
  )
}