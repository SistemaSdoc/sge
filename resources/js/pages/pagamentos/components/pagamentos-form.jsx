import { useEffect, useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

const monthNames = [
  'Janeiro',
  'Fevereiro',
  'Março',
  'Abril',
  'Maio',
  'Junho',
  'Julho',
  'Agosto',
  'Setembro',
  'Outubro',
  'Novembro',
  'Dezembro',
];

const formatCurrency = (value) =>
  Number(value ?? 0).toLocaleString('pt-PT', {
    style: 'currency',
    currency: 'EUR',
  });

export function PagamentosForm({
  title,
  submitLabel = 'Guardar pagamento',
  data,
  setData,
  errors,
  processing,
  submitFn,
  alunos = [],
  itensPagaveis = [],
}) {
  const [cartItems, setCartItems] = useState(() => data.itens ?? []);
  const [selectedItemId, setSelectedItemId] = useState('');

  const selectedAluno = useMemo(() => {
    if (!data.aluno_id) {
      return null;
    }

    return alunos.find((aluno) => aluno.id === data.aluno_id) ?? null;
  }, [alunos, data.aluno_id]);

  useEffect(() => {
    setData('aluno_id', data.aluno_id ?? '');
  }, [data.aluno_id, setData]);

  useEffect(() => {
    setData('itens', cartItems);
    setData('valor_total', calculateTotal(cartItems));
  }, [cartItems, setData]);

  const addItemToCart = (item) => {
    if (cartItems.some((entry) => entry.id === item.id)) {
      return;
    }

    setCartItems((current) => [
      ...current,
      {
        id: item.id,
        nome: item.nome,
        tipo: item.tipo,
        valor: Number(item.valor_padrao ?? 0),
        meses: item.tipo === 'mensalidade' ? ['Janeiro'] : [],
        periodo: item.tipo === 'mensalidade' ? 'Mensal' : 'Único',
      },
    ]);
    setSelectedItemId('');
  };

  const updateItemMonths = (itemId, months) => {
    setCartItems((current) =>
      current.map((entry) =>
        entry.id === itemId ? { ...entry, meses: months } : entry,
      ),
    );
  };

  const removeItem = (itemId) => {
    setCartItems((current) => current.filter((entry) => entry.id !== itemId));
  };

  const totalValue = useMemo(() => calculateTotal(cartItems), [cartItems]);

  const handleSubmit = (event) => {
    event.preventDefault();

    setData('itens', cartItems);
    setData('valor_total', totalValue);
    setData('valor', totalValue);

    submitFn(event);
  };

  return (
    <div className="mx-auto w-full max-w-6xl p-3 sm:p-5">
      <form onSubmit={handleSubmit}>
        <div className="mb-6">
          <h1 className="text-lg font-semibold">{title}</h1>
          <p className="text-sm text-muted-foreground">
            Selecione o estudante e adicione os itens ao carrinho.
          </p>
        </div>

        <div className="grid gap-4 xl:grid-cols-[1.45fr_0.75fr]">
          <div className="space-y-4">
            <Card>
              <CardHeader>
                <div>
                  <CardTitle>Dados do pagamento</CardTitle>
                  <CardDescription>
                    Escolha o estudante e adicione itens ao carrinho.
                  </CardDescription>
                </div>
              </CardHeader>

              <CardContent className="space-y-6">
                <Field>
                  <FieldLabel htmlFor="aluno_id">Estudante</FieldLabel>
                  <Select
                    value={data.aluno_id ?? ''}
                    onValueChange={(value) => setData('aluno_id', value)}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder="Selecione o estudante" />
                    </SelectTrigger>
                    <SelectContent className=''>
                      {alunos.length === 0 && (
                        <SelectItem value="" disabled>
                          Nenhum estudante disponível.
                        </SelectItem>
                      )}
                      {alunos.map((aluno) => (
                        <SelectItem key={aluno.id} value={aluno.id}>
                          {aluno.nome}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors?.aluno_id && (
                    <FieldError>{errors.aluno_id}</FieldError>
                  )}
                </Field>

                {selectedAluno && (
                  <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                    <span className="font-medium text-foreground">
                      {selectedAluno.nome}
                    </span>
                    <span>•</span>
                    <span>{selectedAluno.curso}</span>
                    <span>•</span>
                    <span>{selectedAluno.classe}</span>
                    <span>•</span>
                    <span>{selectedAluno.turma}</span>
                  </div>
                )}

                <Card>
                  <CardHeader>
                    <div>
                      <CardTitle>Itens pagáveis</CardTitle>
                      <CardDescription>
                        Selecione itens do catálogo para o carrinho.
                      </CardDescription>
                    </div>
                  </CardHeader>

                  <CardContent className="">
                    <div className="grid gap-3 sm:grid-cols-[1fr_auto]">
                      <Select
                        value={selectedItemId}
                        onValueChange={setSelectedItemId}
                      >
                        <SelectTrigger className="w-full">
                          <SelectValue placeholder="Selecione um item" />
                        </SelectTrigger>
                        <SelectContent>
                          {itensPagaveis.map((item) => (
                            <SelectItem key={item.id} value={item.id}>
                              {item.nome}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>

                      <Button
                        type="button"
                        size="sm"
                        variant="secondary"
                        className="w-full rounded-none sm:w-auto"
                        disabled={!selectedItemId}
                        onClick={() => {
                          const item = itensPagaveis.find(
                            (entry) => entry.id === selectedItemId,
                          );

                          if (item) {
                            addItemToCart(item);
                          }
                        }}
                      >
                        Adicionar
                      </Button>
                    </div>
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader>
                    <div>
                      <CardTitle>Carrinho</CardTitle>
                      <CardDescription>
                        Revise os itens antes de gravar o pagamento.
                      </CardDescription>
                    </div>
                  </CardHeader>

                  <CardContent className="space-y-4">
                    {cartItems.length === 0 ? (
                      <p className="text-sm text-muted-foreground">
                        Ainda não há itens no carrinho.
                      </p>
                    ) : (
                      <div className="space-y-4">
                        {cartItems.map((entry) => (
                          <div
                            key={entry.id}
                            className="space-y-3 rounded-none p-0"
                          >
                            <div className="flex items-center justify-between gap-3">
                              <div>
                                <p className="text-sm font-medium">
                                  {entry.nome}
                                </p>
                              </div>
                              <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="rounded-none"
                                onClick={() => removeItem(entry.id)}
                              >
                                Remover
                              </Button>
                            </div>

                            {entry.tipo === 'mensalidade' ? (
                              <div className="space-y-2">
                                <p className="text-[11px] tracking-wide text-muted-foreground uppercase">
                                  Meses a pagar
                                </p>
                                <div className="flex flex-wrap gap-1.5">
                                  {monthNames.map((month) => {
                                    const selected =
                                      entry.meses.includes(month);

                                    return (
                                      <Button
                                        key={month}
                                        type="button"
                                        variant={
                                          selected ? 'default' : 'outline'
                                        }
                                        size="sm"
                                        className="h-7 rounded-none px-2.5 text-xs"
                                        onClick={() => {
                                          const nextMonths = selected
                                            ? entry.meses.filter(
                                                (current) => current !== month,
                                              )
                                            : [...entry.meses, month];

                                          updateItemMonths(
                                            entry.id,
                                            nextMonths,
                                          );
                                        }}
                                      >
                                        {month}
                                      </Button>
                                    );
                                  })}
                                </div>
                              </div>
                            ) : (
                              <p className="text-[11px] text-muted-foreground">
                                Este item será registado com um único período.
                              </p>
                            )}
                          </div>
                        ))}
                      </div>
                    )}
                  </CardContent>
                </Card>
              </CardContent>
            </Card>
          </div>

          <Card className="xl:sticky xl:top-4 xl:self-start">
            <CardHeader>
              <div>
                <CardTitle>Resumo</CardTitle>
                <CardDescription>
                  Confira o estudante e o total antes de gravar.
                </CardDescription>
              </div>
            </CardHeader>

            <CardContent className="space-y-4">
              <div className="flex items-center justify-between text-sm text-muted-foreground">
                <span>Estudante</span>
                <span className="max-w-[60%] truncate text-right font-medium text-foreground">
                  {selectedAluno?.nome ?? 'Sem seleção'}
                </span>
              </div>

              <div className="space-y-2 text-sm">
                {cartItems.length === 0 ? (
                  <p className="text-sm text-muted-foreground">
                    Nenhum item selecionado.
                  </p>
                ) : (
                  cartItems.map((entry) => (
                    <div
                      key={entry.id}
                      className="flex items-center justify-between gap-2"
                    >
                      <span className="truncate text-sm">{entry.nome}</span>
                      <span className="font-medium text-foreground">
                        {formatCurrency(
                          entry.valor * (entry.meses.length || 1),
                        )}
                      </span>
                    </div>
                  ))
                )}
              </div>

              <div className="flex items-center justify-between text-base font-semibold">
                <span>Total</span>
                <span>{formatCurrency(totalValue)}</span>
              </div>
            </CardContent>

            <CardFooter>
              <Button
                type="submit"
                className="w-full rounded-none"
                disabled={processing}
              >
                {processing ? 'A guardar...' : submitLabel}
              </Button>
            </CardFooter>
          </Card>
        </div>
      </form>
    </div>
  );
}

function calculateTotal(cartItems) {
  return cartItems.reduce((total, entry) => {
    const multiplier = entry.meses?.length ? entry.meses.length : 1;

    return total + Number(entry.valor ?? 0) * multiplier;
  }, 0);
}
