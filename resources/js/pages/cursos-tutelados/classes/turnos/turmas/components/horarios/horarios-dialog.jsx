import { Form } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/ClasseTurnoDisciplinaHorarioController';
import { HorariosForm } from "./horarios-form";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";

export function HorariosDialog({
  isOpen,
  onClose,
  disciplina,
  instituicaoId,
  cursoTuteladoId,
  cursoClasseId,
  cursoClasseTurnoId,
  turmaId,
  defaultValues = null,
  onSuccess = null,
}) {
  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-2xl">
        <DialogHeader>
          <DialogTitle>Horários de {disciplina?.nome}</DialogTitle>
          <DialogDescription>
            Configure os horários de aulas para esta disciplina
          </DialogDescription>
        </DialogHeader>

        <Form
          {...store.form({
            instituicao: instituicaoId,
            cursoTutelado: cursoTuteladoId,
            cursoClasse: cursoClasseId,
            cursoClasseTurno: cursoClasseTurnoId,
            turma: turmaId,
            classeTurnoDisciplina: disciplina?.id,
          })}
          onSuccess={() => {
            onClose();
            onSuccess?.();
          }}
        >
          {({ processing }) => (
            <HorariosForm
              disciplina={disciplina}
              isLoading={processing}
              defaultValues={defaultValues}
            />
          )}
        </Form>
      </DialogContent>
    </Dialog>
  );
}
