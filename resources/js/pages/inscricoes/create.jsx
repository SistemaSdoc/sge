import { Form } from '@inertiajs/react';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { index, store } from '@/routes/inscricoes';
import InscricaoForm from './components/inscricao-form';

export default function Create() {
    const { cursos } = usePage().props;

    const [cursoId, setCursoId] = useState(undefined);
    const [cursoClasseTurnoId, setCursoClasseTurnoId] = useState(undefined);

    const cursoSelecionado = cursos?.find((c) => String(c.id) === String(cursoId));

    return (
        <Form
            action={store.url()}
            method="post"
            transform={(data) => ({
                ...data,
                curso_classe_turno_id: cursoClasseTurnoId,
            })}
            onSuccess={() => router.visit(index.url())}
        >
            {({ errors, processing }) => (
                <InscricaoForm
                    errors={errors}
                    processing={processing}
                    cursos={cursos}
                    cursoId={cursoId}
                    setCursoId={(val) => {
                        setCursoId(val);
                        setCursoClasseTurnoId(undefined);
                    }}
                    cursoSelecionado={cursoSelecionado}
                    cursoClasseTurnoId={cursoClasseTurnoId}
                    setCursoClasseTurnoId={setCursoClasseTurnoId}
                />
            )}
        </Form>
    );
}