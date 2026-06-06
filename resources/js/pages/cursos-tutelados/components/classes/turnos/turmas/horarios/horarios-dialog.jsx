"use client";

import { useState } from "react";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { HorariosForm } from "./horarios-form";
import { useDefineHorarios } from "../../../../../hooks/classes/turnos/turmas/horarios/useDefineHorarios";
import { toast } from "sonner";

export function HorariosDialog({
  isOpen,
  onClose,
  disciplina,
  instituicaoId,
  cursoId,
  classeId,
  turnoId,
  turmaId,
  defaultValues = null,
  onSuccess = null,
}) {
  const { mutate, isPending } = useDefineHorarios(
    instituicaoId,
    cursoId,
    classeId,
    turnoId,
    turmaId,
    disciplina?.id
  );

  const handleSubmit = (horarios) => {
    mutate(horarios, {
      onSuccess: (data) => {
        toast.success("Horários salvos com sucesso!");
        onClose();
        onSuccess?.();
      },
      onError: (error) => {
        toast.error(
          error?.response?.data?.message || "Erro ao salvar horários"
        );
      },
    });
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-2xl">
        <DialogHeader>
          <DialogTitle>Horários de {disciplina?.nome}</DialogTitle>
          <DialogDescription>
            Configure os horários de aulas para esta disciplina
          </DialogDescription>
        </DialogHeader>

        <HorariosForm
          disciplina={disciplina}
          onSubmit={handleSubmit}
          isLoading={isPending}
          defaultValues={defaultValues}
        />
      </DialogContent>
    </Dialog>
  );
}
