import { useForm, router } from '@inertiajs/react';
import {
  Combobox,
  ComboboxContent,
  ComboboxEmpty,
  ComboboxInput,
  ComboboxItem,
  ComboboxList,
} from '@/components/ui/combobox';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { CartSummary } from './cart-summary';
import { CartItem } from './cart-items';
import { store } from '@/actions/App/Http/Controllers/PagamentoController';

const metodos = [
  { value: 'dinheiro', label: 'Dinheiro' },
  { value: 'transferencia', label: 'Transferência' },
  { value: 'multicaixa', label: 'Multicaixa' },
  { value: 'outro', label: 'Outro' },
];

export function PagamentosForm({
  alunos,
  itensPagaveis,
  paidRecord,
  pendenciasComMulta = {},
}) {
  const currentMonth = new Date().getMonth() + 1;
  const currentYear = new Date().getFullYear();

  const { data, setData, post, processing, errors } = useForm({
    aluno_id: null,
    data_pagamento: new Date().toISOString().slice(0, 10),
    metodo: 'dinheiro',
    referencia: '',
    observacoes: '',
    itens: [],
  });

  const aluno = alunos.find((a) => a.id === data.aluno_id) ?? null;
  const studentPaid = paidRecord ?? {};

  function paidMonthsFor(itemPagavelId) {
    return studentPaid[itemPagavelId] ?? [];
  }

  // Função que busca o valor com multa para um item/mês/ano
  function valorDoMes(itemId, mes, ano) {
    const linhas = pendenciasComMulta[itemId] ?? [];
    const encontrada = linhas.find((p) => p.mes === mes && p.ano === ano);
    return encontrada ?? null;
  }

  function handleAlunoChange(a) {
    setData((prev) => ({ ...prev, aluno_id: a?.id ?? null, itens: [] }));
    if (a?.id) {
      router.reload({
        only: ['paidRecord', 'itensPagaveis', 'pendenciasComMulta'],
        data: { aluno_id: a.id },
        preserveState: true,
        preserveScroll: true,
      });
    }
  }

  function handleToggleItem(item, checked) {
    setData((prev) => {
      if (!checked) {
        return {
          ...prev,
          itens: prev.itens.filter((e) => e.item_pagavel_id !== item.id),
        };
      }

      const meses =
        item.frequencia === 'mensal'
          ? paidMonthsFor(item.id).includes(currentMonth)
            ? []
            : [currentMonth]
          : [];

      return {
        ...prev,
        itens: [
          ...prev.itens,
          {
            item_pagavel_id: item.id,
            ano: currentYear,
            meses,
          },
        ],
      };
    });
  }

  function handleMonthsChange(itemPagavelId, meses) {
    setData((prev) => ({
      ...prev,
      itens: prev.itens.map((e) =>
        e.item_pagavel_id === itemPagavelId ? { ...e, meses } : e,
      ),
    }));
  }

  function handleRemove(itemPagavelId) {
    setData((prev) => ({
      ...prev,
      itens: prev.itens.filter((e) => e.item_pagavel_id !== itemPagavelId),
    }));
  }

  function handleSubmit() {
    post(store().url, { onSuccess: () => setData('itens', []) });
  }

  const hasErrors = Object.keys(errors).length > 0;

  return (
    <main className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-6">
      <header className="flex flex-col gap-1">
        <h1 className="text-xl font-bold tracking-tight">
          Pagamento de Emolumentos
        </h1>
        <p className="text-sm text-muted-foreground">
          Selecione o aluno, escolha os itens a pagar e confirme o pagamento.
        </p>
      </header>

      {hasErrors && (
        <div
          role="alert"
          className="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive"
        >
          <p className="font-medium">Não foi possível registar o pagamento.</p>
          <ul className="mt-2 list-disc space-y-1 pl-5">
            {Object.entries(errors).map(([key, message]) => (
              <li key={key}>{message}</li>
            ))}
          </ul>
        </div>
      )}

      <FieldGroup className="grid grid-cols-1 gap-4 md:grid-cols-3">
        <Field data-invalid={Boolean(errors.aluno_id)}>
          <FieldLabel>Aluno</FieldLabel>
          <Combobox
            items={alunos}
            itemToStringValue={(a) => a.nome}
            value={aluno?.nome ?? ''}
            onValueChange={(a) => handleAlunoChange(a ?? null)}
            showClear
          >
            <ComboboxInput
              placeholder="Pesquisar aluno..."
              aria-invalid={Boolean(errors.aluno_id)}
            />
            <ComboboxContent>
              <ComboboxEmpty>Nenhum aluno encontrado.</ComboboxEmpty>
              <ComboboxList>
                {(a) => (
                  <ComboboxItem key={a.id} value={a}>
                    {a.nome}
                  </ComboboxItem>
                )}
              </ComboboxList>
            </ComboboxContent>
          </Combobox>
          {errors.aluno_id && <FieldError>{errors.aluno_id}</FieldError>}
        </Field>

        <Field data-invalid={Boolean(errors.data_pagamento)}>
          <FieldLabel htmlFor="data_pagamento">Data</FieldLabel>
          <Input
            id="data_pagamento"
            type="date"
            value={data.data_pagamento}
            onChange={(e) => setData('data_pagamento', e.target.value)}
            aria-invalid={Boolean(errors.data_pagamento)}
          />
          {errors.data_pagamento && (
            <FieldError>{errors.data_pagamento}</FieldError>
          )}
        </Field>

        <Field data-invalid={Boolean(errors.metodo)}>
          <FieldLabel htmlFor="metodo">Método</FieldLabel>
          <Select
            value={data.metodo}
            onValueChange={(val) => setData('metodo', val)}
          >
            <SelectTrigger
              id="metodo"
              className="w-full"
              aria-invalid={Boolean(errors.metodo)}
            >
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                {metodos.map((m) => (
                  <SelectItem key={m.value} value={m.value}>
                    {m.label}
                  </SelectItem>
                ))}
              </SelectGroup>
            </SelectContent>
          </Select>
          {errors.metodo && <FieldError>{errors.metodo}</FieldError>}
        </Field>
      </FieldGroup>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section className="flex flex-col gap-3" aria-label="Itens a pagar">
          <h2 className="text-sm font-medium">Itens a pagar</h2>
          {!aluno && (
            <p className="text-sm text-muted-foreground">
              Selecione primeiro um aluno para ativar os itens.
            </p>
          )}
          {itensPagaveis.map((item) => {
            const entry = data.itens.find((e) => e.item_pagavel_id === item.id);
            return (
              <CartItem
                key={item.id}
                item={item}
                selected={entry !== undefined}
                selectedMonths={entry?.meses ?? []}
                paidMonths={aluno ? paidMonthsFor(item.id) : []}
                disabled={!aluno}
                onToggle={(checked) => handleToggleItem(item, checked)}
                onMonthsChange={(meses) => handleMonthsChange(item.id, meses)}
                valorDoMes={(mes) => valorDoMes(item.id, mes, currentYear)}
              />
            );
          })}
        </section>

        <aside className="lg:sticky lg:top-8 lg:self-start">
          <CartSummary
            student={aluno}
            entries={data.itens}
            feeItems={itensPagaveis}
            processing={processing}
            onRemove={handleRemove}
            onSubmit={handleSubmit}
            valorDoMes={valorDoMes}
          />
        </aside>
      </div>
    </main>
  );
}