import { Form, usePage } from '@inertiajs/react';
import { update } from '@/actions/App/Http/Controllers/ClasseTurnoDisciplinaController';
import DisciplinaForm from './components/disciplina-form';

export default function Edit() {
    const {
        disciplina,
        instituicaoId,
        cursoId,
        classeId,
        turnoId,
    } = usePage().props;

    return (
        <Form
            {...update.form({
                instituicao: instituicaoId,
                cursoTutelado: cursoId,
                cursoClasse: classeId,
                cursoClasseTurno: turnoId,
                classeTurnoDisciplina: disciplina.id,
            })}
        >
            {({ errors, processing }) => (
                <DisciplinaForm
                    disciplina={disciplina}
                    errors={errors}
                    processing={processing}
                    isEdit
                />
            )}
        </Form>
    );
}