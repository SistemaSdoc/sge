import { Head } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import { FileBadge, History } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { ProfileAvatarPreview } from '@/components/profile-avatar-preview';
import TablePagination from '@/components/table-pagination';
import { TableSearch } from '@/components/table-search';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Combobox,
  ComboboxContent,
  ComboboxEmpty,
  ComboboxInput,
  ComboboxItem,
  ComboboxList,
} from '@/components/ui/combobox';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

type Instituicao = {
  id: string;
  nome: string;
  tipo: 'colegio' | 'instituto';
};

type Aluno = {
  id: string;
  nome: string;
  matricula: string | null;
  numero_processo: string | null;
  avatar: string | null;
};

type PaginaAlunos = {
  data: Aluno[];
  current_page: number;
  last_page: number;
  total: number;
};

type ItemHistoricoDeclaracao = {
  aluno: string;
  matricula: string | null;
  emitidoEm: string;
};

export default function Index({
  instituicoes,
  historico: historicoInicial,
  routes,
}: {
  instituicoes: Instituicao[];
  historico: ItemHistoricoDeclaracao[];
  routes: { alunos: string; store: string };
}) {
  const [tenantId, setTenantId] = useState('');
  const [pesquisa, setPesquisa] = useState('');
  const [pesquisaAplicada, setPesquisaAplicada] = useState('');
  const [pagina, setPagina] = useState(1);
  const [alunos, setAlunos] = useState<PaginaAlunos | null>(null);
  const [aCarregar, setACarregar] = useState(false);
  const [aEmitir, setAEmitir] = useState(false);
  const [alunoSelecionado, setAlunoSelecionado] = useState<Aluno | null>(null);
  const [historicoAberto, setHistoricoAberto] = useState(false);
  const [historico, setHistorico] =
    useState<ItemHistoricoDeclaracao[]>(historicoInicial);
  const formulario = useForm({
    tenant_id: '',
    aluno_id: '',
    nota: '',
    responsavel_nome: '',
    responsavel_cargo: '',
  });

  useEffect(() => {
    if (!tenantId) {
      return;
    }

    const controller = new AbortController();
    const timeout = window.setTimeout(async () => {
      setACarregar(true);

      try {
        const parametros = new URLSearchParams({
          tenant_id: tenantId,
          q: pesquisaAplicada,
          pagina: String(pagina),
        });
        const resposta = await fetch(`${routes.alunos}?${parametros}`, {
          headers: { Accept: 'application/json' },
          signal: controller.signal,
        });

        if (!resposta.ok) {
          throw new Error(
            'Não existem alunos da 13.ª classe nesta instituição.',
          );
        }

        const resultado = await resposta.json();
        setAlunos(resultado);
      } catch (erro) {
        if (erro instanceof DOMException && erro.name === 'AbortError') {
          return;
        }

        toast.error(
          erro instanceof Error
            ? erro.message
            : 'Ocorreu um erro ao pesquisar os alunos.',
        );
      } finally {
        setACarregar(false);
      }
    }, 250);

    return () => {
      window.clearTimeout(timeout);
      controller.abort();
    };
  }, [tenantId, pagina, pesquisaAplicada, routes.alunos]);

  const escolherInstituicao = (novoTenantId: string) => {
    setTenantId(novoTenantId);
    setPesquisa('');
    setPesquisaAplicada('');
    setPagina(1);
    setAlunos(null);
  };

  const pesquisarAlunos = () => {
    setPesquisaAplicada(pesquisa.trim());
    setPagina(1);
  };

  const prepararEmissao = (aluno: Aluno) => {
    formulario.reset();
    formulario.clearErrors();
    formulario.setData({
      tenant_id: tenantId,
      aluno_id: aluno.id,
      nota: '',
      responsavel_nome: '',
      responsavel_cargo: '',
    });
    setAlunoSelecionado(aluno);
  };

  const emitirDeclaracao = async (evento: FormEvent<HTMLFormElement>) => {
    evento.preventDefault();

    if (!alunoSelecionado) {
      return;
    }

    setAEmitir(true);
    formulario.clearErrors();

    try {
      const resposta = await fetch(routes.store, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN':
            document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
              ?.content ?? '',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(formulario.data),
      });

      if (!resposta.ok) {
        if (resposta.status === 422) {
          const dados = (await resposta.json()) as {
            errors?: Record<string, string | string[]>;
          };
          const erros = Object.fromEntries(
            Object.entries(dados.errors ?? {}).map(([campo, mensagem]) => [
              campo,
              Array.isArray(mensagem) ? mensagem[0] : mensagem,
            ]),
          );
          formulario.setError(erros);
        } else {
          toast.error('Não foi possível gerar o documento.');
        }

        return;
      }

      if (
        !resposta.headers
          .get('Content-Type')
          ?.toLowerCase()
          .startsWith('application/pdf')
      ) {
        toast.error(
          'O servidor não devolveu um PDF. Verifique os dados e tente novamente.',
        );

        return;
      }

      const blob = await resposta.blob();
      const cabecalhoPdf = await blob.slice(0, 5).text();

      if (cabecalhoPdf !== '%PDF-') {
        toast.error('O ficheiro recebido não é um PDF válido.');

        return;
      }

      const disposition = resposta.headers.get('Content-Disposition') ?? '';
      const nomeMatch = disposition.match(/filename="([^"]+)"/);
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = nomeMatch?.[1] ?? 'Declaracao_Estagio.pdf';
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);

      const novoHistorico = [
        {
          aluno: alunoSelecionado.nome,
          matricula: alunoSelecionado.matricula,
          emitidoEm: new Intl.DateTimeFormat('pt-AO', {
            dateStyle: 'short',
            timeStyle: 'short',
          }).format(new Date()),
        },
        ...historico,
      ];
      setHistorico(novoHistorico);

      setAlunoSelecionado(null);
      formulario.reset();
    } catch {
      toast.error('Ocorreu um erro de comunicação ao gerar a declaração.');
    } finally {
      setAEmitir(false);
    }
  };

  return (
    <>
      <Head title="Declarações de estágio" />

      <div className="mx-auto grid w-full max-w-7xl gap-6 p-4 md:p-6">
        <Card>
          <CardHeader className="relative border-b pr-44">
            <div className="grid gap-1">
              <CardTitle>Declaração de estágio</CardTitle>
              <CardDescription>
                Seleccione um aluno para gerar a declaração de estágio.
              </CardDescription>
            </div>
            <Button
              type="button"
              variant="outline"
              size="sm"
              className="absolute top-3 right-4 w-auto"
              onClick={() => setHistoricoAberto(true)}
            >
              <History data-icon="inline-start" />
              Histórico de declarações
            </Button>
          </CardHeader>

          <CardContent>
            <div className="grid gap-5">
              <Field>
                <FieldLabel htmlFor="instituicao">Instituição</FieldLabel>
                <Combobox
                  items={instituicoes}
                  value={
                    instituicoes.find(
                      (instituicao) => instituicao.id === tenantId,
                    ) ?? null
                  }
                  onValueChange={(instituicao) =>
                    escolherInstituicao(instituicao?.id ?? '')
                  }
                  itemToStringLabel={(instituicao) =>
                    instituicao
                      ? `${instituicao.nome} — ${instituicao.tipo === 'colegio' ? 'Colégio' : 'Instituto'}`
                      : ''
                  }
                  itemToStringValue={(instituicao) => instituicao?.id ?? ''}
                  isItemEqualToValue={(item, value) => item.id === value.id}
                >
                  <ComboboxInput
                    id="instituicao"
                    placeholder="Pesquisar instituição..."
                    showClear
                  />
                  <ComboboxContent>
                    <ComboboxEmpty>
                      Nenhuma instituição encontrada.
                    </ComboboxEmpty>
                    <ComboboxList>
                      {(instituicao) => (
                        <ComboboxItem key={instituicao.id} value={instituicao}>
                          <span>{instituicao.nome}</span>
                          <Badge variant="outline" className="ml-auto">
                            {instituicao.tipo === 'colegio'
                              ? 'Colégio'
                              : 'Instituto'}
                          </Badge>
                        </ComboboxItem>
                      )}
                    </ComboboxList>
                  </ComboboxContent>
                </Combobox>
              </Field>
            </div>
          </CardContent>
        </Card>

        <Card className="w-full min-w-0 gap-0 pb-0">
          <CardHeader className="border-b">
            <CardTitle>Alunos</CardTitle>
            <CardDescription>
              Selecione um aluno para gerar a declaração de estágio.
            </CardDescription>
          </CardHeader>

          <CardContent className="min-w-0 p-0!">
            <TableSearch
              value={pesquisa}
              onChange={setPesquisa}
              onSubmit={pesquisarAlunos}
              placeholder="Pesquisar por nome ou matrícula..."
              disabled={!tenantId}
              buttonGroupClassName=""
            />

            {aCarregar ? (
              <EmptyState
                variant="table"
                icon={FileBadge}
                title="A carregar alunos"
                description="A lista está a ser actualizada."
              />
            ) : !tenantId ? (
              <EmptyState
                variant="table"
                icon={FileBadge}
                title="Seleccione uma instituição"
                description="Escolha uma instituição para consultar os alunos elegíveis."
              />
            ) : !alunos?.data.length ? (
              <EmptyState
                variant="table"
                icon={FileBadge}
                title={
                  pesquisaAplicada
                    ? 'Nenhum aluno encontrado'
                    : 'Nenhum aluno elegível'
                }
                description={
                  pesquisaAplicada
                    ? 'A pesquisa não encontrou alunos da 13.ª classe com grupo PAP.'
                    : 'Não existem alunos da 13.ª classe integrados em grupos PAP nesta instituição.'
                }
              />
            ) : (
              <Table className="min-w-0">
                <TableHeader>
                  <TableRow className="bg-muted/72">
                    <TableHead className="px-4">Nome</TableHead>
                    <TableHead className="px-4 text-right">Acções</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {alunos.data.map((aluno) => (
                    <TableRow key={aluno.id}>
                      <TableCell className="px-4 font-medium">
                        <div className="flex min-w-0 items-center gap-3">
                          <ProfileAvatarPreview
                            src={aluno.avatar}
                            name={aluno.nome}
                          />
                          <div className="flex min-w-0 flex-col">
                            <span className="min-w-0 text-xs wrap-anywhere">
                              {aluno.nome}
                            </span>
                            <span className="text-[10px] text-muted-foreground">
                              Matrícula: {aluno.matricula ?? '—'}
                            </span>
                          </div>
                        </div>
                      </TableCell>
                      <TableCell className="px-4 text-right">
                        <Button
                          type="button"
                          variant="outline"
                          size="sm"
                          disabled={aEmitir}
                          onClick={() => prepararEmissao(aluno)}
                        >
                          <FileBadge data-icon="inline-start" />
                          Gerar declaração de estágio
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </CardContent>

          {tenantId && alunos?.data.length ? (
            <TablePagination pagination={alunos} onPageChange={setPagina} />
          ) : null}
        </Card>
      </div>

      <Dialog
        open={alunoSelecionado !== null}
        onOpenChange={(aberto) => {
          if (!aberto && !aEmitir) {
            setAlunoSelecionado(null);
            formulario.reset();
            formulario.clearErrors();
          }
        }}
      >
        <DialogContent className="max-h-[90svh] overflow-y-auto sm:max-w-2xl">
          <DialogHeader>
            <DialogTitle>Emitir declaração de estágio</DialogTitle>
            <DialogDescription>
              {alunoSelecionado
                ? `Preencha a nota e os dados complementares do estágio de ${alunoSelecionado.nome}.`
                : 'Preencha os dados da declaração de estágio.'}
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={emitirDeclaracao} className="grid gap-4">
            {(formulario.errors.tenant_id || formulario.errors.aluno_id) && (
              <p role="alert" className="text-sm text-destructive">
                {formulario.errors.tenant_id ?? formulario.errors.aluno_id}
              </p>
            )}

            <Field>
              <FieldLabel htmlFor="nota">Nota final (0 a 20)</FieldLabel>
              <Input
                id="nota"
                name="nota"
                type="number"
                min="0"
                max="20"
                step="0.5"
                required
                value={formulario.data.nota}
                onChange={(evento) =>
                  formulario.setData('nota', evento.target.value)
                }
                aria-invalid={Boolean(formulario.errors.nota)}
                disabled={aEmitir}
              />
              <InputError message={formulario.errors.nota} />
            </Field>

            <div className="grid gap-4 sm:grid-cols-2">
              <Field>
                <FieldLabel htmlFor="responsavel_nome">
                  Nome do Supervisor
                </FieldLabel>
                <Input
                  id="responsavel_nome"
                  name="responsavel_nome"
                  required
                  value={formulario.data.responsavel_nome}
                  onChange={(evento) =>
                    formulario.setData(
                      'responsavel_nome',
                      evento.target.value.replace(/\p{N}/gu, ''),
                    )
                  }
                  aria-invalid={Boolean(formulario.errors.responsavel_nome)}
                  disabled={aEmitir}
                />
                <InputError message={formulario.errors.responsavel_nome} />
              </Field>

              <Field>
                <FieldLabel htmlFor="responsavel_cargo">
                  Cargo do Supervisor
                </FieldLabel>
                <Input
                  id="responsavel_cargo"
                  name="responsavel_cargo"
                  required
                  value={formulario.data.responsavel_cargo}
                  onChange={(evento) =>
                    formulario.setData(
                      'responsavel_cargo',
                      evento.target.value.replace(/\p{N}/gu, ''),
                    )
                  }
                  aria-invalid={Boolean(formulario.errors.responsavel_cargo)}
                  disabled={aEmitir}
                />
                <InputError message={formulario.errors.responsavel_cargo} />
              </Field>
            </div>

            <DialogFooter>
              <DialogClose asChild>
                <Button type="button" variant="outline" disabled={aEmitir}>
                  Cancelar
                </Button>
              </DialogClose>
              <Button type="submit" disabled={aEmitir}>
                {aEmitir ? 'A emitir…' : 'Emitir declaração'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <Dialog open={historicoAberto} onOpenChange={setHistoricoAberto}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Histórico de declarações</DialogTitle>
            <DialogDescription>
              Registos das declarações emitidas. Os PDFs não são guardados no sistema.
            </DialogDescription>
          </DialogHeader>
          {historico.length === 0 ? (
            <EmptyState
              variant="table"
              icon={History}
              title="Nenhuma declaração emitida"
              description="Os documentos emitidos nesta sessão aparecerão aqui."
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Aluno</TableHead>
                  <TableHead>Matrícula</TableHead>
                  <TableHead>Emissão</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {historico.map((item, indice) => (
                  <TableRow key={`${item.aluno}-${item.emitidoEm}-${indice}`}>
                    <TableCell className="font-medium">{item.aluno}</TableCell>
                    <TableCell>{item.matricula ?? '—'}</TableCell>
                    <TableCell>{item.emitidoEm}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </DialogContent>
      </Dialog>
    </>
  );
}
