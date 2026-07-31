import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldError, FieldGroup, FieldLabel, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

export function TurmaForm({ data, setData, errors, processing, onSubmit, can = {}, anosLectivos = [] }) {
  const canSubmit = Boolean(can.create ?? can.update ?? true);

  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-195">
      <form onSubmit={onSubmit}>
        <Card className="overflow-visible">
          <CardHeader>
            <CardTitle>Criar Turma</CardTitle>
            <CardDescription>Preencha os dados abaixo para criar a turma</CardDescription>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                <Field>
                  <FieldLabel htmlFor="nome">Nome</FieldLabel>
                  <Input
                    id="nome"
                    type="text"
                    placeholder="Ex.: Turma A"
                    value={data.nome}
                    onChange={(e) => setData('nome', e.target.value)}
                  />
                  {errors.nome && <FieldError>{errors.nome}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel htmlFor="max_alunos">Máximo de alunos</FieldLabel>
                  <Input
                    id="max_alunos"
                    type="number"
                    placeholder="Ex.: 30"
                    value={data.max_alunos}
                    onChange={(e) => setData('max_alunos', e.target.value)}
                  />
                  {errors.max_alunos && <FieldError>{errors.max_alunos}</FieldError>}
                </Field>

                <Field>
                  <FieldLabel>Ano Lectivo</FieldLabel>
                  <Select
                    value={data.ano_lectivo_id ?? ''}
                    onValueChange={(value) => setData('ano_lectivo_id', value)}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder="Selecione o ano lectivo" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>Anos Lectivos</SelectLabel>
                        {anosLectivos.map((ano) => (
                          <SelectItem key={ano?.id} value={ano?.id}>
                            {ano?.nome}
                          </SelectItem>
                        ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                </Field>

                <Field>
                  <Button type="submit" disabled={processing || !canSubmit}>
                    Salvar
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
