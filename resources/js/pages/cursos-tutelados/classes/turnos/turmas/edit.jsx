import { useForm } from '@inertiajs/react';
import { TurmaForm } from './components/turma-form';
import { update } from '@/actions/App/Http/Controllers/ClasseTurnoTurmaController';

export default function Edit({ turma,
    origem,
    instituicaoId,
    cursoId,
    classeId,
    turnoId,
    can = {}, }) {

    const { data, setData, put, processing, errors } = useForm({
        nome: turma?.nome ?? '',
        max_alunos: turma?.max_alunos ?? '',
        origem: origem,
    });

    const handleSubmit = (e) => {
        e.preventDefault();

        put(
            update({
                instituicao: instituicaoId,
                cursoTutelado: cursoId,
                cursoClasse: classeId,
                cursoClasseTurno: turnoId,
                turma: turma.id,
            }).url,
            { preserveScroll: true },
        );
    };

    return (
        <TurmaForm
            data={data}
            setData={setData}
            errors={errors}
            processing={processing}
            can={can}
            onSubmit={handleSubmit}
        />
    );
}