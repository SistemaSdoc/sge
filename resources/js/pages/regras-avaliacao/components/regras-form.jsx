import { Button } from '@/components/ui/button';
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { ChevronDownIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

function SectionHeader({ title }) {
  return (
    <div className="flex items-center justify-between gap-4 px-4">
      <div className="text-sm font-semibold">{title}</div>
      <CollapsibleTrigger asChild>
        <Button variant="ghost" size="icon-sm" type="button">
          <ChevronDownIcon className="text-muted-foreground transition-transform in-data-open:rotate-180" />
          <span className="sr-only">Toggle</span>
        </Button>
      </CollapsibleTrigger>
    </div>
  );
}

export function RegraAvaliacaoForm({
  submitLabel = 'Guardar',
  data,
  setData,
  errors,
  processing,
  submitFn,
  classesPorNivel = {},
  niveisEnsino = [],
}) {
  const classesFiltradas = data.nivel_ensino_id
    ? (classesPorNivel[data.nivel_ensino_id] ?? [])
    : [];

  const handleNivelChange = (value) => {
    setData('nivel_ensino_id', value);
    setData('classe_id', '');
  };

  return (
    <div className="mx-auto w-full max-w-sm space-y-6 px-6 py-6 md:max-w-md lg:max-w-3xl">
      <div>
        <h1 className="text-xl font-bold tracking-tight">
          Regras de Avaliação
        </h1>
        <p className="mt-2 text-sm text-muted-foreground">
          Configure as regras de avaliação para sua instituição. Estas regras
          determinarão os critérios de aprovação, frequência mínima e outras
          condições importantes para os alunos.
        </p>
      </div>

      <form onSubmit={submitFn}>
        <div className="w-full divide-y border">
          <Collapsible defaultOpen className="flex flex-col gap-2 py-2">
            <SectionHeader title="Dados Básicos" />
            <CollapsibleContent className="flex flex-col gap-3 px-4 pt-1 pb-3">
              <Field data-invalid={Boolean(errors.nome)}>
                <FieldLabel htmlFor="nome">Nome da Regra</FieldLabel>
                <Input
                  id="nome"
                  type="text"
                  placeholder="Ex.: Regra IMCL - 10ª Classe"
                  value={data.nome}
                  onChange={(e) => setData('nome', e.target.value)}
                  aria-invalid={Boolean(errors.nome)}
                />
                {errors.nome && <FieldError>{errors.nome}</FieldError>}
              </Field>
            </CollapsibleContent>
          </Collapsible>

          <Collapsible defaultOpen className="flex flex-col gap-2 py-2">
            <SectionHeader title="Onde será aplicada?" />
            <CollapsibleContent className="flex flex-col gap-3 px-4 pt-1 pb-3">
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Field data-invalid={Boolean(errors.nivel_ensino_id)}>
                  <FieldLabel htmlFor="nivel_ensino_id">
                    Nível de Ensino
                  </FieldLabel>
                  <Select
                    value={data.nivel_ensino_id || ''}
                    onValueChange={handleNivelChange}
                  >
                    <SelectTrigger
                      id="nivel_ensino_id"
                      className="w-full"
                      aria-invalid={Boolean(errors.nivel_ensino_id)}
                    >
                      <SelectValue placeholder="Nenhum (geral)" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectItem value="">Nenhum (geral)</SelectItem>
                        {niveisEnsino.map((nivel) => (
                          <SelectItem key={nivel.id} value={nivel.id}>
                            {nivel.nome}
                          </SelectItem>
                        ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  {errors.nivel_ensino_id && (
                    <FieldError>{errors.nivel_ensino_id}</FieldError>
                  )}
                </Field>

                <Field data-invalid={Boolean(errors.classe_id)}>
                  <FieldLabel htmlFor="classe_id">Classe específica</FieldLabel>
                  <Select
                    value={data.classe_id || ''}
                    onValueChange={(value) => setData('classe_id', value)}
                    disabled={!data.nivel_ensino_id}
                  >
                    <SelectTrigger
                      id="classe_id"
                      className="w-full"
                      aria-invalid={Boolean(errors.classe_id)}
                    >
                      <SelectValue
                        placeholder={
                          !data.nivel_ensino_id
                            ? 'Selecione um nível primeiro'
                            : classesFiltradas.length > 0
                              ? 'Selecione uma classe'
                              : 'Nenhuma classe disponível'
                        }
                      />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectItem value="">Nenhuma (todas)</SelectItem>
                        {classesFiltradas.map((classe) => (
                          <SelectItem key={classe.id} value={classe.id}>
                            {classe.nome}
                          </SelectItem>
                        ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  {errors.classe_id && (
                    <FieldError>{errors.classe_id}</FieldError>
                  )}
                </Field>
              </div>
            </CollapsibleContent>
          </Collapsible>

          <Collapsible defaultOpen className="flex flex-col gap-2 py-2">
            <SectionHeader title="Critérios de Avaliação" />
            <CollapsibleContent className="flex flex-col gap-3 px-4 pt-1 pb-3">
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Field data-invalid={Boolean(errors.media_minima_aprovacao)}>
                  <FieldLabel htmlFor="media_minima_aprovacao">
                    Nota Mín. para Aprovação
                  </FieldLabel>
                  <Input
                    id="media_minima_aprovacao"
                    type="number"
                    step="0.5"
                    min="0"
                    max="20"
                    placeholder="Ex.: 10"
                    value={data.media_minima_aprovacao}
                    onChange={(e) =>
                      setData('media_minima_aprovacao', e.target.value)
                    }
                    aria-invalid={Boolean(errors.media_minima_aprovacao)}
                  />
                  {errors.media_minima_aprovacao && (
                    <FieldError>{errors.media_minima_aprovacao}</FieldError>
                  )}
                </Field>

                <Field data-invalid={Boolean(errors.max_disciplinas_negativas)}>
                  <FieldLabel htmlFor="max_disciplinas_negativas">
                    Máx. Disciplinas Negativas
                  </FieldLabel>
                  <Input
                    id="max_disciplinas_negativas"
                    type="number"
                    min="0"
                    max="100"
                    placeholder="Ex.: 2"
                    value={data.max_disciplinas_negativas ?? ''}
                    onChange={(e) =>
                      setData(
                        'max_disciplinas_negativas',
                        e.target.value ? parseInt(e.target.value) : null,
                      )
                    }
                    aria-invalid={Boolean(errors.max_disciplinas_negativas)}
                  />

                  {errors.max_disciplinas_negativas && (
                    <FieldError>{errors.max_disciplinas_negativas}</FieldError>
                  )}
                </Field>

                <Field data-invalid={Boolean(errors.frequencia_minima)}>
                  <FieldLabel htmlFor="frequencia_minima">
                    Frequência Mínima (%)
                  </FieldLabel>
                  <Input
                    id="frequencia_minima"
                    type="number"
                    step="1"
                    min="0"
                    max="100"
                    placeholder="Ex.: 75"
                    value={data.frequencia_minima}
                    onChange={(e) =>
                      setData('frequencia_minima', e.target.value)
                    }
                    aria-invalid={Boolean(errors.frequencia_minima)}
                  />
                  {errors.frequencia_minima && (
                    <FieldError>{errors.frequencia_minima}</FieldError>
                  )}
                </Field>
              </div>
            </CollapsibleContent>
          </Collapsible>

          <Collapsible defaultOpen className="flex flex-col gap-2 py-2">
            <SectionHeader title="Regras de Recurso" />
            <CollapsibleContent className="flex flex-col gap-3 px-4 pt-1 pb-3">
              <Field
                orientation="horizontal"
                data-invalid={Boolean(errors.permite_recurso)}
              >
                <FieldLabel htmlFor="permite_recurso">
                  Permite Recurso?
                </FieldLabel>
                <Switch
                  id="permite_recurso"
                  size="sm"
                  checked={data.permite_recurso}
                  onCheckedChange={(checked) =>
                    setData('permite_recurso', checked)
                  }
                />
                {errors.permite_recurso && (
                  <FieldError>{errors.permite_recurso}</FieldError>
                )}
              </Field>

              {data.permite_recurso && (
                <Field data-invalid={Boolean(errors.nota_minima_recurso)}>
                  <FieldLabel htmlFor="nota_minima_recurso">
                    Nota Mínima para Aprovação no Recurso
                  </FieldLabel>
                  <Input
                    id="nota_minima_recurso"
                    type="number"
                    step="0.5"
                    min="0"
                    max="20"
                    placeholder="Ex.: 10"
                    value={data.nota_minima_recurso}
                    onChange={(e) =>
                      setData('nota_minima_recurso', e.target.value)
                    }
                    aria-invalid={Boolean(errors.nota_minima_recurso)}
                  />
                  {errors.nota_minima_recurso && (
                    <FieldError>{errors.nota_minima_recurso}</FieldError>
                  )}
                </Field>
              )}
            </CollapsibleContent>
          </Collapsible>
        </div>

        <Button type="submit" disabled={processing} className="mt-4 w-full">
          {processing ? 'A criar...' : submitLabel}
        </Button>
      </form>
    </div>
  );
}
