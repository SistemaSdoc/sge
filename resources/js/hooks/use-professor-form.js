import { useForm, router } from '@inertiajs/react';
import { useDialog } from '@/hooks/use-dialog';
import { toast } from 'sonner';
import { store } from '@/actions/App/Http/Controllers/Tenant/InstituicaoCurso/TurmaDisciplinaProfessorController';

export function useProfessorForm(routeParams, classeTurnoDisciplina, initialAnoLectivoId = '') {
    const { confirm } = useDialog();

    const { data, setData, errors, setError } = useForm({
        professor_id: '',
        disciplina_id: classeTurnoDisciplina,
        ano_lectivo_id: initialAnoLectivoId,
    });

    const doPost = (force = false) => {
        router.post(store(routeParams).url, { ...data, force }, {
            onSuccess: () => toast.success('Professor associado com sucesso.'),
            onError: (errs) => {
                if (errs.requires_confirmation) {
                    confirm({
                        title: 'Disciplina já tem professor',
                        description: 'Esta disciplina já tem um professor atribuído. Deseja substituí-lo pelo seleccionado?',
                        confirmLabel: 'Substituir',
                        confirmFn: () => doPost(true),
                    });
                } else {
                    setError(errs);
                }
            },
        });
    };

    const submit = (e) => {
        e.preventDefault();
        doPost(false);
    };

    return { data, setData, errors, submit };
}