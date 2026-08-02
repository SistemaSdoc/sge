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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

const frequencias = [
  { label: 'Mensal', value: 'mensal' },
  { label: 'Anual', value: 'anual' },
  { label: 'Único', value: 'unico' },
];

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
  // ---------- LOGS DE DEBUG ----------
  console.log('[ItensForm] RENDER ------------------------------');
  console.log('[ItensForm] data completo:', data);
  console.log(
    '[ItensForm] data.curso_classe_id:',
    JSON.stringify(data.curso_classe_id),
    'tipo:',
    typeof data.curso_classe_id,
  );
  console.log(
    '[ItensForm] cursosClasse recebido (length):',
    cursosClasse.length,
  );
  console.log('[ItensForm] cursosClasse recebido (array):', cursosClasse);

  // valor que vai efectivamente para a prop `value` do Select
  const selectValue = data.curso_classe_id || 'todos';
  console.log(
    '[ItensForm] selectValue calculado (vai para o Select):',
    JSON.stringify(selectValue),
  );

  // verifica se esse valor existe mesmo dentro da lista de opções
  const idsDisponiveis = cursosClasse.map((cc) => String(cc.id));
  const existeNaLista =
    idsDisponiveis.includes(selectValue) || selectValue === 'todos';
  console.log('[ItensForm] IDs disponíveis no Select:', idsDisponiveis);
  console.log(
    '[ItensForm] selectValue existe na lista de opções?',
    existeNaLista,
  );

  if (!existeNaLista) {
    console.warn(
      '[ItensForm] ⚠️ PROBLEMA ENCONTRADO: o valor "' +
        selectValue +
        '" não corresponde a NENHUM id em cursosClasse. ' +
        'O SelectValue vai ficar vazio/placeholder mesmo com dados corretos.',
    );
  }
  // ---------- FIM LOGS DE DEBUG ----------

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
                      step="500"
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

                <Field data-invalid={Boolean(errors?.curso_classe_id)}>
                  <FieldLabel htmlFor="curso_classe_id">
                    Aplicar a Curso / Classe
                  </FieldLabel>
                  <Select
                    value={selectValue}
                    onValueChange={(val) => {
                      console.log(
                        '[ItensForm] onValueChange disparado. val recebido:',
                        val,
                      );
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
                      {cursosClasse.map((cc) => {
                        const valorItem = String(cc.id);
                        if (valorItem === selectValue) {
                          console.log(
                            '[ItensForm] ✅ SelectItem que DEVERIA aparecer selecionado:',
                            cc.nome,
                            valorItem,
                          );
                        }
                        return (
                          <SelectItem key={cc.id} value={valorItem}>
                            {cc.nome}
                          </SelectItem>
                        );
                      })}
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
