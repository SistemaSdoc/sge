import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
  FieldDescription,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

const frequencias = [
  { label: 'Mensal', value: 'mensal' },
  { label: 'Anual', value: 'anual' },
  { label: 'Único', value: 'unico' },
];

const tipo = [
  { label: 'Financeiro', value: 'financeiro' },
  { label: 'Documento', value: 'documento' },
];

const subtipoLabels = {
  declaracao_sem_notas: 'Declaração Sem Notas',
  declaracao_com_notas: 'Declaração Com Notas',
  certificado: 'Certificado',
};

export function ItensForm({
  title,
  submitLabel = 'Guardar',
  data,
  setData,
  errors,
  processing,
  cursosClasse = [],
  submitFn,
}) {
  const selectValue = data.curso_classe_id || 'todos';
  const temMulta = data.multa_dias_tolerancia || data.multa_valor;

  return (
    <div className="mx-auto w-full max-w-2xl p-6">
      <form onSubmit={submitFn}>
        <Card>
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                <Field data-invalid={Boolean(errors?.nome)}>
                  <FieldLabel htmlFor="nome">Nome</FieldLabel>
                  <Input
                    id="nome"
                    type="text"
                    placeholder="Nome do emolumento (ex.: Propina para a 10ª classe)"
                    value={data.nome ?? ''}
                    onChange={(e) => setData('nome', e.target.value)}
                    aria-invalid={Boolean(errors?.nome)}
                  />
                  {errors?.nome && <FieldError>{errors.nome}</FieldError>}
                </Field>

                <div className="grid gap-4 md:grid-cols-2">
                  <Field data-invalid={Boolean(errors?.valor)}>
                    <FieldLabel htmlFor="valor">Valor</FieldLabel>
                    <Input
                      id="valor"
                      type="number"
                      min="0"
                      placeholder="0,00"
                      value={data.valor ?? ''}
                      onChange={(e) => setData('valor', e.target.value)}
                      aria-invalid={Boolean(errors?.valor)}
                    />
                    {errors?.valor && <FieldError>{errors.valor}</FieldError>}
                  </Field>

                  <Field data-invalid={Boolean(errors?.frequencia)}>
                    <FieldLabel>Frequência de pagamento</FieldLabel>
                    <ToggleGroup
                      type="single"
                      variant="outline"
                      value={data.frequencia ?? 'mensal'}
                      onValueChange={(val) => val && setData('frequencia', val)}
                      className="flex w-full!"
                    >
                      {frequencias.map((f) => (
                        <ToggleGroupItem key={f.value} value={f.value}>
                          {f.label}
                        </ToggleGroupItem>
                      ))}
                    </ToggleGroup>
                    {errors?.frequencia && (
                      <FieldError>{errors.frequencia}</FieldError>
                    )}
                  </Field>
                </div>

                <Field>
                  <FieldLabel>Tipo</FieldLabel>
                  <Select
                    value={data.tipo ?? 'financeiro'}
                    onValueChange={(val) => setData('tipo', val)}
                    disabled={processing}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder="Selecione um Tipo" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>Tipos</SelectLabel>
                        <SelectItem value="financeiro">Financeiro</SelectItem>
                        <SelectItem value="documento">Documento</SelectItem>
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  {errors.tipo && <FieldError>{errors.tipo}</FieldError>}
                </Field>

                {data.tipo === 'documento' && (
                  <Field>
                    <FieldLabel htmlFor="subtipo">
                      Subtipo <span className="text-red-500">*</span>
                    </FieldLabel>
                    <Select
                      value={data.subtipo ?? ''}
                      onValueChange={(v) => {
                        setData('subtipo', v);
                        if (!data.nome) {
                          setData('nome', subtipoLabels[v]);
                        }
                      }}
                    > 
                      <SelectTrigger id="subtipo">
                        <SelectValue placeholder="Seleccione o subtipo..." />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="declaracao_sem_notas">
                          Declaração Sem Notas
                        </SelectItem>
                        <SelectItem value="declaracao_com_notas">
                          Declaração Com Notas
                        </SelectItem>
                        <SelectItem value="certificado">Certificado</SelectItem>
                      </SelectContent>
                    </Select>
                    {errors.subtipo && (
                      <FieldError>{errors.subtipo}</FieldError>
                    )}
                  </Field>
                )}

                <Field data-invalid={Boolean(errors?.curso_classe_id)}>
                  <FieldLabel htmlFor="curso_classe_id">
                    Aplicar a Curso / Classe
                  </FieldLabel>
                  <Select
                    value={selectValue}
                    onValueChange={(val) => {
                      setData('curso_classe_id', val === 'todos' ? '' : val);
                    }}
                  >
                    <SelectTrigger
                      id="curso_classe_id"
                      aria-invalid={Boolean(errors?.curso_classe_id)}
                    >
                      <SelectValue placeholder="Aplica-se a toda a instituição" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="todos">
                        Toda a instituição (sem curso/classe específico)
                      </SelectItem>
                      {cursosClasse.map((cc) => (
                        <SelectItem key={cc.id} value={String(cc.id)}>
                          {cc.nome}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors?.curso_classe_id && (
                    <FieldError>{errors.curso_classe_id}</FieldError>
                  )}
                </Field>

                <Field data-invalid={Boolean(errors?.descricao)}>
                  <FieldLabel htmlFor="descricao">Descrição</FieldLabel>
                  <Textarea
                    id="descricao"
                    placeholder="Opcional"
                    value={data.descricao ?? ''}
                    onChange={(e) => setData('descricao', e.target.value)}
                    aria-invalid={Boolean(errors?.descricao)}
                  />
                  {errors?.descricao && (
                    <FieldError>{errors.descricao}</FieldError>
                  )}
                </Field>

                <Field className="flex flex-row items-center justify-between">
                  <FieldLabel htmlFor="ativo" className="cursor-pointer">
                    Bloquear Estudantes se não pagarem este emolumento?
                  </FieldLabel>
                  <Switch
                    id="ativo"
                    size="sm"
                    checked={Boolean(data.ativo)}
                    onCheckedChange={(val) => setData('ativo', val)}
                  />
                </Field>

                <div className="rounded-lg border p-4">
                  <p className="text-sm font-medium">Multa por atraso</p>
                  <p className="mb-3 text-xs text-muted-foreground">
                    Opcional. Deixa os dois campos vazios se este emolumento não
                    tiver multa. Só se aplica a itens de frequência mensal (ex:
                    propina).
                  </p>

                  <div className="grid gap-4 md:grid-cols-2">
                    <Field
                      data-invalid={Boolean(errors?.multa_dias_tolerancia)}
                    >
                      <FieldLabel htmlFor="multa_dias_tolerancia">
                        Dias de tolerância (a partir do início do mês)
                      </FieldLabel>
                      <Input
                        id="multa_dias_tolerancia"
                        type="number"
                        min="1"
                        max="31"
                        placeholder="Ex.: 10"
                        value={data.multa_dias_tolerancia ?? ''}
                        onChange={(e) =>
                          setData('multa_dias_tolerancia', e.target.value)
                        }
                        aria-invalid={Boolean(errors?.multa_dias_tolerancia)}
                      />
                      <FieldDescription>
                        Ex.: 10 = pode pagar até ao dia 10 sem multa; a partir
                        do dia 11, aplica-se.
                      </FieldDescription>
                      {errors?.multa_dias_tolerancia && (
                        <FieldError>{errors.multa_dias_tolerancia}</FieldError>
                      )}
                    </Field>

                    <Field data-invalid={Boolean(errors?.multa_valor)}>
                      <FieldLabel htmlFor="multa_valor">
                        Valor da multa (Kz)
                      </FieldLabel>
                      <Input
                        id="multa_valor"
                        type="number"
                        min="0"
                        placeholder="Ex.: 2500"
                        value={data.multa_valor ?? ''}
                        onChange={(e) => setData('multa_valor', e.target.value)}
                        aria-invalid={Boolean(errors?.multa_valor)}
                      />
                      {errors?.multa_valor && (
                        <FieldError>{errors.multa_valor}</FieldError>
                      )}
                    </Field>
                  </div>

                  {temMulta && (
                    <p className="mt-3 text-xs text-muted-foreground">
                      Resumo: após o dia {data.multa_dias_tolerancia || '—'} do
                      mês, soma-se{' '}
                      {data.multa_valor
                        ? `${Number(data.multa_valor).toLocaleString('pt')} Kz`
                        : '—'}{' '}
                      ao valor da propina em atraso.
                    </p>
                  )}
                </div>

                <Button type="submit" disabled={processing}>
                  {processing ? 'Criando...' : submitLabel}
                </Button>
              </FieldSet>
            </FieldGroup>
          </CardContent>
        </Card>
      </form>
    </div>
  );
}
