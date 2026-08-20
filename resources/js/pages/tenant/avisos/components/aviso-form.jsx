import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';

const tipos = [
  { label: 'Aviso', value: 'aviso' },
  { label: 'Evento', value: 'evento' },
  { label: 'Urgente', value: 'urgente' },
];

const destinatarios = [
  { label: 'Todos', value: 'todos' },
  { label: 'Alunos', value: 'alunos' },
  { label: 'Professores', value: 'professores' },
];

export function AvisoForm({
  title,
  submitLabel = 'Publicar',
  data,
  setData,
  errors,
  processing,
  submitFn,
}) {
  return (
    <div className="mx-auto w-full max-w-2xl px-6 py-6">
      <form onSubmit={submitFn}>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between border-b">
            <CardTitle>{title}</CardTitle>
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <span>Publicado</span>
              <Switch
                checked={!!data.ativo}
                onCheckedChange={(val) => setData('ativo', val)}
              />
            </div>
          </CardHeader>

          <CardContent className="">
            <FieldGroup>
              <FieldSet>
                {/* Título */}
                <Field>
                  <FieldLabel htmlFor="titulo">Título</FieldLabel>
                  <Input
                    id="titulo"
                    type="text"
                    placeholder="Ex: Entrega de documentos até sexta-feira"
                    value={data.titulo}
                    onChange={(e) => setData('titulo', e.target.value)}
                  />
                  {errors.titulo && <FieldError>{errors.titulo}</FieldError>}
                </Field>

                {/* Tipo + Destinatário lado a lado */}
                {/* <div className="grid grid-cols-2 gap-4">
                  <Field>
                    <FieldLabel>Tipo</FieldLabel>
                    <SegmentedControl
                      options={tipos}
                      value={data.tipo}
                      onChange={(val) => setData('tipo', val)}
                    />
                    {errors.tipo && <FieldError>{errors.tipo}</FieldError>}
                  </Field>

                  <Field>
                    <FieldLabel>Destinatário</FieldLabel>
                    <SegmentedControl
                      options={destinatarios}
                      value={data.destinatario}
                      onChange={(val) => setData('destinatario', val)}
                    />
                    {errors.destinatario && (
                      <FieldError>{errors.destinatario}</FieldError>
                    )}
                  </Field>
                </div> */}

                <FieldSet>
                  
                  <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    {/* Tipo */}
                    <Field className="">
                      <FieldLabel htmlFor="tipo">Tipo</FieldLabel>
                          <ToggleGroup
                            variant="outline"
                            type="single"
                            value={data.tipo}
                            onValueChange={(value) => setData('tipo', value)}
                            className="w-full"
                          >
                            {tipos.map((t) => (
                              <ToggleGroupItem
                                key={t.value}
                                value={t.value}
                                aria-label={t.label}
                                className="flex-1"
                              >
                                {t.label}
                              </ToggleGroupItem>
                            ))}
                          </ToggleGroup>

                      {errors?.tipo && (
                        <FieldError>{errors.tipo?.message}</FieldError>
                      )}
                    </Field>

                    {/* Destinatário */}
                    <Field>
                      <FieldLabel htmlFor="destinatario">
                        Destinatário
                      </FieldLabel>

                          <ToggleGroup
                            type="single"
                            variant="outline"
                            value={data.destinatario}
                            onValueChange={(value) => setData('destinatario', value)}
                            className="w-full"
                          >
                            {destinatarios.map((d) => (
                              <ToggleGroupItem
                                key={d.value}
                                value={d.value}
                                aria-label={d.label}
                                className="flex-1"
                              >
                                {d.label}
                              </ToggleGroupItem>
                            ))}
                          </ToggleGroup>

                      {errors?.destinatario && (
                        <FieldError>{errors.destinatario?.message}</FieldError>
                      )}
                    </Field>
                  </div>
                </FieldSet>

                {/* Data / hora */}
                <Field>
                  <FieldLabel htmlFor="data">
                    Data / hora{' '}
                    <span className="font-normal text-muted-foreground">
                      (opcional)
                    </span>
                  </FieldLabel>
                  <Input
                    id="data"
                    type="datetime-local"
                    value={data.data ?? ''}
                    onChange={(e) => setData('data', e.target.value)}
                  />
                  {errors.data && <FieldError>{errors.data}</FieldError>}
                </Field>

                {/* Descrição */}
                <Field>
                  <FieldLabel htmlFor="descricao">
                    Descrição{' '}
                    <span className="font-normal text-muted-foreground">
                      (opcional)
                    </span>
                  </FieldLabel>
                  <Textarea
                    id="descricao"
                    placeholder="Detalhes adicionais..."
                    value={data.descricao}
                    onChange={(e) => setData('descricao', e.target.value)}
                  />
                  {errors.descricao && (
                    <FieldError>{errors.descricao}</FieldError>
                  )}
                </Field>

                {/* Submit */}
                <Button type="submit" className="w-full" disabled={processing}>
                  {processing ? 'A publicar...' : submitLabel}
                </Button>
              </FieldSet>
            </FieldGroup>
          </CardContent>
        </Card>
      </form>
    </div>
  );
}

// // Componente auxiliar de seleção por botões
// function SegmentedControl({ options, value, onChange }) {
//   return (
//     <div className="flex overflow-hidden rounded-md border border-input">
//       {options.map((opt) => (
//         <button
//           key={opt.value}
//           type="button"
//           onClick={() => onChange(opt.value)}
//           className={[
//             'flex-1 px-3 py-2 text-sm transition-colors',
//             'border-r border-input last:border-r-0',
//             value === opt.value
//               ? 'bg-primary font-medium text-primary-foreground'
//               : 'bg-background text-muted-foreground hover:bg-muted',
//           ].join(' ')}
//         >
//           {opt.label}
//         </button>
//       ))}
//     </div>
//   );
// }
