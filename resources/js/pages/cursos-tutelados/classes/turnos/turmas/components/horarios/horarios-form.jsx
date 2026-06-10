"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { Loader2 } from "lucide-react";

const DIAS_SEMANA = [
  { id: 1, nome: "Segunda" },
  { id: 2, nome: "Terça" },
  { id: 3, nome: "Quarta" },
  { id: 4, nome: "Quinta" },
  { id: 5, nome: "Sexta" },
  { id: 6, nome: "Sábado" },
  { id: 7, nome: "Domingo" },
];

export function HorariosForm({
  disciplina,
  onSubmit,
  isLoading = false,
  defaultValues = null,
}) {
  const [horariosAtivos, setHorariosAtivos] = useState(
    defaultValues?.map((h) => h.dia_semana) || [1, 2, 3, 4, 5]
  );

  const { control, handleSubmit, watch } = useForm({
    defaultValues: {
      horarios: defaultValues || DIAS_SEMANA.map((dia) => ({
        dia_semana: dia.id,
        hora_inicio: "08:00",
        hora_fim: "09:30",
      })),
    },
  });

  const horarios = watch("horarios");

  const toggleDia = (diaSemana) => {
    setHorariosAtivos((prev) =>
      prev.includes(diaSemana)
        ? prev.filter((d) => d !== diaSemana)
        : [...prev, diaSemana]
    );
  };

  const handleFormSubmit = (data) => {
    const horariosParaEnviar = data.horarios.filter((h) =>
      horariosAtivos.includes(h.dia_semana)
    );
    onSubmit(horariosParaEnviar);
  };

  return (
    <form onSubmit={handleSubmit(handleFormSubmit)} className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Definir Horários</CardTitle>
          <CardDescription>
            Selecione os dias e horários que a disciplina {disciplina?.nome} tem aula
          </CardDescription>
        </CardHeader>

        <CardContent className="space-y-6">
          {/* Grid de Dias */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {DIAS_SEMANA.map((dia, idx) => {
              const estaAtivo = horariosAtivos.includes(dia.id);
              const horario = horarios[idx];

              return (
                <div
                  key={dia.id}
                  className={`p-4 border rounded-lg transition-colors ${
                    estaAtivo
                      ? "bg-blue-50 border-blue-200"
                      : "bg-gray-50 border-gray-200"
                  }`}
                >
                  {/* Checkbox + Dia */}
                  <div className="flex items-center gap-3 mb-3">
                    <Checkbox
                      id={`dia-${dia.id}`}
                      checked={estaAtivo}
                      onCheckedChange={() => toggleDia(dia.id)}
                    />
                    <Label
                      htmlFor={`dia-${dia.id}`}
                      className="font-medium cursor-pointer"
                    >
                      {dia.nome}
                    </Label>
                  </div>

                  {/* Horários - apenas se dia ativo */}
                  {estaAtivo && (
                    <div className="space-y-3 pl-7">
                      <div className="grid grid-cols-2 gap-2">
                        <div>
                          <Label htmlFor={`inicio-${dia.id}`} className="text-xs">
                            Início
                          </Label>
                          <Controller
                            name={`horarios.${idx}.hora_inicio`}
                            control={control}
                            render={({ field }) => (
                              <Input
                                {...field}
                                id={`inicio-${dia.id}`}
                                type="time"
                                className="text-sm"
                              />
                            )}
                          />
                        </div>

                        <div>
                          <Label htmlFor={`fim-${dia.id}`} className="text-xs">
                            Fim
                          </Label>
                          <Controller
                            name={`horarios.${idx}.hora_fim`}
                            control={control}
                            render={({ field }) => (
                              <Input
                                {...field}
                                id={`fim-${dia.id}`}
                                type="time"
                                className="text-sm"
                              />
                            )}
                          />
                        </div>
                      </div>
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </CardContent>
      </Card>

      {/* Botões */}
      <div className="flex gap-3">
        <Button
          type="submit"
          disabled={isLoading || horariosAtivos.length === 0}
          className="flex-1"
        >
          {isLoading && <Loader2 className="mr-2 size-4 animate-spin" />}
          {isLoading ? "Salvando..." : "Salvar Horários"}
        </Button>
      </div>

      {horariosAtivos.length === 0 && (
        <p className="text-sm text-red-600">
          ⚠️ Selecione pelo menos um dia para salvar
        </p>
      )}
    </form>
  );
}
