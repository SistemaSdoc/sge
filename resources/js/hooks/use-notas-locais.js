import { useState } from 'react';

/**
 * Gere o estado local das notas em edição, por aluno e por período.
 * Os valores locais têm prioridade sobre os valores vindos do servidor.
 *
 * O estado é automaticamente isolado por tdpId — mudar de disciplina
 * não contamina os valores de outra, sem necessidade de reset explícito.
 *
 * @param {string|null} tdpId - ID do TurmaDisciplinaProfessor activo.
 */
export function useNotasLocais(tdpId) {
    // { [tdpId]: { [turmaAlunoId-periodo]: { mac, npp, npt, faltas } } }
    const [notasLocais, setNotasLocais] = useState({});

    /**
     * Obtém o valor local de um campo para um aluno num período específico.
     * Retorna null se não houver valor local (deve usar o valor do servidor).
     */
    const getValor = (turmaAlunoId, periodo, campo) =>
        notasLocais[tdpId]?.[`${turmaAlunoId}-${periodo}`]?.[campo] ?? null;

    /**
     * Define o valor local de um campo para um aluno num período específico.
     */
    const setValor = (turmaAlunoId, periodo, campo, valor) =>
        setNotasLocais((prev) => ({
            ...prev,
            [tdpId]: {
                ...prev[tdpId],
                [`${turmaAlunoId}-${periodo}`]: {
                    ...prev[tdpId]?.[`${turmaAlunoId}-${periodo}`],
                    [campo]: valor,
                },
            },
        }));

    return { getValor, setValor };
}