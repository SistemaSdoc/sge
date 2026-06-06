import { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Field, FieldLabel, FieldError, FieldGroup, FieldSet } from '@/components/ui/field';
import {
  Select, SelectContent, SelectGroup, SelectLabel,
  SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import MultipleSelect from '@/components/multiple-select';

export default function Create({ instituicao, cursoTutelado, classesTurnos, turnos }) {
  const { data, setData, put, processing, errors } = useForm({
    turnos: [],
  });

  const [cursoClasseId, setCursoClasseId] = useState('');

  const handleClasseChange = (value) => {
    setCursoClasseId(value);
    const classe = classesTurnos?.find(c => String(c.id) === value);
    setData('turnos', classe?.turnos?.map(t => t.turno.id) ?? []);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    put(
      `/instituicoes/${instituicao.id}/cursos-tutelados/${cursoTutelado.id}/classes/${cursoClasseId}/turnos`,
      {
        preserveScroll: true,
        onSuccess: () =>
          router.visit(
            `/instituicoes/${instituicao.id}/cursos-tutelados/${cursoTutelado.id}`,
          ),
      }
    );
  };

  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-195">
      <form onSubmit={handleSubmit}>
        <Card className="overflow-visible">
          <CardHeader className="border-b">
            <CardTitle>Definir Turnos por Classe</CardTitle>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                <Field>
                  <FieldLabel>Classe</FieldLabel>
                  <Select
                    value={cursoClasseId || undefined}
                    onValueChange={handleClasseChange}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder="Selecione a classe" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>Classes</SelectLabel>
                        {classesTurnos?.map(cc => (
                          <SelectItem key={cc.id} value={String(cc.id)}>
                            {cc.classe.nome}
                          </SelectItem>
                        ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                </Field>

                <Field>
                  <FieldLabel>Turnos</FieldLabel>
                  <MultipleSelect
                    placeholder="Selecione os turnos"
                    items={turnos?.map(t => ({ value: t.id, label: t.nome }))}
                    onChange={(opts) => setData('turnos', opts.map(o => o.value))}
                    value={data.turnos.map(id => ({
                      value: id,
                      label: turnos?.find(t => t.id === id)?.nome ?? id,
                    }))}
                    disabled={!cursoClasseId}
                  />
                  {errors.turnos && <FieldError>{errors.turnos}</FieldError>}
                </Field>

                <Field>
                  <Button type="submit" disabled={!cursoClasseId || processing}>
                    Guardar
                  </Button>
                </Field>
              </FieldSet>
            </FieldGroup>
          </CardContent>
        </Card>
      </form>
    </div>
  );
}