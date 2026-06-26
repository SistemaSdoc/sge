import { Form, useForm } from '@inertiajs/react';
import { router, usePage } from '@inertiajs/react';
import { update } from '@/actions/App/Http/Controllers/BancaJuriPapController';
import { show } from '@/actions/App/Http/Controllers/GrupoPapController';
import { CreateForm } from './components/create.form';

export default function Edit() {
    const {
        instituicao,
        cursoTutelado,
        cursoClasse,
        cursoClasseTurno,
        turma,
        grupoPap,
        bancaJuriPap,
        professores,
        funcoes,
    } = usePage().props;

    const { data, setData, errors, processing } = useForm({
        professor_id: bancaJuriPap.professor_id ?? '',
        funcao: bancaJuriPap.funcao ?? '',
    });

    return (
        <Form
            {...update.form({
                instituicao: instituicao.id,
                cursoTutelado: cursoTutelado.id,
                cursoClasse: cursoClasse.id,
                cursoClasseTurno: cursoClasseTurno.id,
                turma: turma.id,
                grupoPap: grupoPap.id,
                bancaJuriPap: bancaJuriPap.id,
            })}
            transform={(formData) => ({
                ...formData,
                professor_id: data.professor_id,
                funcao: data.funcao,
            })}
            onSuccess={() =>
                router.visit(
                    show.url({
                        instituicao: instituicao.id,
                        cursoTutelado: cursoTutelado.id,
                        cursoClasse: cursoClasse.id,
                        cursoClasseTurno: cursoClasseTurno.id,
                        turma: turma.id,
                        grupoPap: grupoPap.id,
                    }),
                )
            }
        >
            {({ errors: formErrors, processing: formProcessing }) => (
                <CreateForm
                    data={data}
                    setData={setData}
                    errors={{ ...formErrors, ...errors }}
                    processing={formProcessing || processing}
                    professores={professores}
                    funcoes={funcoes}
                />
            )}
        </Form>
    );
}