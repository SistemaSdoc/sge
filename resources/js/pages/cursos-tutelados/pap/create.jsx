"use client";
import { useRouter } from "next/navigation";
import { GrupoPapForm } from "../components/grupo-pap-form";
import { useCreateGrupoPap } from "../hooks/useCreateGrupoPap";
import { useTurma } from "../../hooks/classes/turnos/turmas/useTurma";
import { useProfessores } from "../../hooks/professores/useProfessores";
import Loader from "@/components/loader";
import { useAlunosDisponiveis } from "../hooks/useAlunosDisponiveis";

export function GrupoPapCreate({
  instituicaoId,
  cursoId,
  classeId,
  turnoId,
  turmaId,
}) {
  const router = useRouter();
  const mutation = useCreateGrupoPap(
    instituicaoId,
    cursoId,
    classeId,
    turnoId,
    turmaId,
  );
  const { data: turma, isLoading: loadingTurma } = useTurma(
    instituicaoId,
    cursoId,
    turmaId,
  );

  const { data: professores, isLoading: loadingProfessores } = useProfessores(
    instituicaoId,
    cursoId,
  );

  const { data: alunosDisponiveis, isLoading: loadingAlunos } =
    useAlunosDisponiveis(instituicaoId, cursoId, classeId, turnoId, turmaId);
  console.log("lista de profs em create: ", professores);

  if (/* loadingTurma ||   */ loadingProfessores || loadingAlunos)
    return <Loader />;

  return (
    <GrupoPapForm
      title="Criar grupo PAP"
      isPending={mutation.isPending}
      professores={professores ?? []}
      alunos={alunosDisponiveis ?? []}
      defaultValues={{
        nome_grupo: "",
        tema_grupo: "",
        professor_tutor_id: "",
        alunos: [],
        estudo_caso: "",
        nota_final: "",
        data_defesa: "",
      }}
      submitFn={(formData) =>
        mutation.mutate(
          {
            ...formData,
            turma_id: turmaId,
          },
          {
            onSuccess: () =>
              router.push(
                `/dashboard/instituicoes/${instituicaoId}/cursos/${cursoId}/classes/${classeId}/turnos/${turnoId}/turmas/${turmaId}`,
              ),
            onError: (error) =>
              alert(
                error?.response?.data?.message ?? "Erro ao criar grupo PAP",
              ),
          },
        )
      }
    />
  );
}
