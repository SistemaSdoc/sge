import { usePage } from '@inertiajs/react';
import { useProfessorForm } from '@/hooks/use-professor-form';
import ProfessorForm from './components/professor-form';

export default function Create() {
    const {
        instituicao,
        cursoTutelado,
        cursoClasse,
        cursoClasseTurno,
        turma,
        classeTurnoDisciplina,
        professores,
        disciplinas,
    } = usePage().props;

    const routeParams = {
        instituicao,
        cursoTutelado,
        cursoClasse,
        cursoClasseTurno,
        turma,
        classeTurnoDisciplina,
    };

    const { data, setData, errors, submit } = useProfessorForm(routeParams, classeTurnoDisciplina);

    return (
        <ProfessorForm
            disciplinas={disciplinas ?? []}
            professores={professores ?? []}
            data={data}
            setData={setData}
            errors={errors}
            processing={false}
            submitFn={submit}
        />
    );
}