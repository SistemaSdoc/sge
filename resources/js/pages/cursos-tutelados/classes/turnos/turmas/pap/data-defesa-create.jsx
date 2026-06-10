"use client";
import { useRouter } from "next/navigation";
import { useDefinirDataDefesa } from "../hooks/useDefinirDataDefesa";
import { DataDefesaForm } from "../components/data-defesa-form";

export function DataDefesaCreate({ grupoId }) {
  const router = useRouter();
  const mutation = useDefinirDataDefesa(grupoId);

  return (
    <DataDefesaForm
      title="Definir data da defesa"
      isPending={mutation.isPending}
      submitFn={(formData) =>
        mutation.mutate(formData, {
          onSuccess: () => router.push(`/dashboard/pap/grupos/${grupoId}`),
          onError: (error) =>
            alert(
              error?.response?.data?.message ??
                "Erro ao definir data da defesa",
            ),
        })
      }
    />
  );
}
