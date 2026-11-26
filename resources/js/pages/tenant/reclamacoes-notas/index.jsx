import { InfiniteScroll, router, usePage } from '@inertiajs/react';
import { MessageSquareText, SearchX } from 'lucide-react';
import { useState } from 'react';
import { index as reclamacoesNotasIndex } from '@/actions/App/Http/Controllers/Tenant/ReclamacaoNotaController';
import {
  baixarAnexo as baixarAnexoReclamacao,
  decidir as decidirReclamacao,
  anexos as anexosReclamacao,
} from '@/actions/App/Http/Controllers/Tenant/ReclamacaoNotaController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { EmptyState } from '@/components/empty-state';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { TableSearch } from '@/components/table-search';
import { useTableSearch } from '@/hooks/use-table-search';
import { DecidirReclamacaoDialog } from './components/decidir-reclamacao-dialog';
import { AnexoPreview } from './components/anexo-preview';

const labels = {
  pendente: 'Pendente',
  em_analise: 'Em análise',
  aguarda_informacao: 'A aguardar informação',
  aprovada: 'Aprovada',
  reprovada: 'Reprovada',
};

const componentes = {
  mac: 'MAC',
  nota_prova_professor: 'Prova do professor',
  nota_prova_trimestral: 'Prova trimestral',
};

const mensagensEstado = {
  pendente: 'A reclamação foi enviada e aguarda análise.',
  em_analise: 'O professor ou membro do grupo está a analisar a reclamação.',
  aguarda_informacao:
    'É necessária informação adicional antes da decisão final.',
  aprovada: 'Reclamação aprovada. A nota foi corrigida.',
  reprovada: 'Reclamação reprovada. A nota original foi mantida.',
};

const estados = [
  { value: '', label: 'Todas' },
  { value: 'pendente', label: labels.pendente },
  { value: 'em_analise', label: labels.em_analise },
  { value: 'aguarda_informacao', label: labels.aguarda_informacao },
  { value: 'aprovada', label: labels.aprovada },
  { value: 'reprovada', label: labels.reprovada },
];

const formatarData = (data) => {
  if (!data) return 'Data indisponível';

  return new Intl.DateTimeFormat('pt-PT', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(data));
};

export default function ReclamacoesNotasIndex() {
  const {
    reclamacoes = {},
    filters = {},
    statusCounts = {},
    canDecidir = false,
  } = usePage().props;
  const [selecionada, setSelecionada] = useState(null);
  const { search, onChange, submit } = useTableSearch(filters.search, {
    only: ['reclamacoes', 'filters', 'statusCounts'],
    reset: ['reclamacoes'],
  });

  const lista = reclamacoes.data ?? [];
  const totalEstados = Object.values(statusCounts).reduce(
    (total, count) => total + Number(count ?? 0),
    0,
  );

  const aplicarFiltros = (novosFiltros, opcoes = {}) => {
    router.get(reclamacoesNotasIndex().url, novosFiltros, {
      preserveScroll: true,
      preserveState: true,
      replace: true,
      reset: ['reclamacoes'],
      ...opcoes,
    });
  };

  const filtrosAtuais = {
    search: filters.search ?? '',
    status: filters.status ?? '',
    periodo: filters.periodo ?? '',
  };

  const abrirDecisao = (reclamacao) => {
    setSelecionada(reclamacao);
  };

  return (
    <div className="mx-auto flex w-full max-w-6xl flex-col gap-4 p-4 sm:p-6">
      <Card className="gap-0 overflow-visible pb-0">
        <CardHeader className="border-b border-foreground/10">
          <div className="min-w-0 space-y-1">
            <CardTitle className="text-lg">Reclamações de notas</CardTitle>
            <CardDescription>
              Acompanhe pedidos de revisão e respostas da disciplina.
            </CardDescription>
          </div>
        </CardHeader>

        <CardContent className="space-y-3 px-4 py-3">
          <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 className="text-sm font-semibold whitespace-nowrap">Filtros</h2>
            <div className="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:justify-end">
              <div className="w-full sm:max-w-xs">
                <TableSearch
                  bare
                  buttonGroupClassName="max-w-none"
                  value={search}
                  onChange={onChange}
                  onSubmit={submit}
                  placeholder="Pesquisar aluno, matrícula..."
                />
              </div>
              <Select
                value={filters.periodo || 'todos'}
                onValueChange={(periodo) =>
                  aplicarFiltros({
                    ...filtrosAtuais,
                    search: search.trim(),
                    periodo: periodo === 'todos' ? '' : periodo,
                  })
                }
              >
                <SelectTrigger
                  className="w-full sm:w-48"
                  aria-label="Filtrar por período"
                >
                  <SelectValue placeholder="Todos os períodos" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="todos">Todos os períodos</SelectItem>
                  {[1, 2, 3, 4].map((periodo) => (
                    <SelectItem key={periodo} value={String(periodo)}>
                      {periodo}º trimestre
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          <div
            className="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap"
            aria-label="Filtrar por estado"
          >
            {estados.map((estado) => {
              const quantidade = estado.value
                ? (statusCounts[estado.value] ?? 0)
                : totalEstados;
              const ativo = (filters.status ?? '') === estado.value;

              return (
                <Button
                  key={estado.value || 'todas'}
                  type="button"
                  size="sm"
                  variant={ativo ? 'default' : 'outline'}
                  className="w-full justify-between sm:w-auto"
                  aria-pressed={ativo}
                  onClick={() =>
                    aplicarFiltros({
                      ...filtrosAtuais,
                      search: search.trim(),
                      status: estado.value,
                    })
                  }
                >
                  {estado.label}
                  <span className="ml-1 tabular-nums">{quantidade}</span>
                </Button>
              );
            })}
          </div>
        </CardContent>
      </Card>

      <InfiniteScroll
        data="reclamacoes"
        onlyNext
        buffer={500}
        loading={() => (
          <p
            className="px-4 py-3 text-center text-xs text-muted-foreground"
            role="status"
          >
            A carregar mais reclamações...
          </p>
        )}
      >
        {lista.length === 0 ? (
          <EmptyState
            variant="table"
            icon={
              filters.search || filters.status || filters.periodo
                ? SearchX
                : MessageSquareText
            }
            title={
              filters.search || filters.status || filters.periodo
                ? 'Nenhuma reclamação encontrada'
                : 'Não existem reclamações'
            }
            description={
              filters.search || filters.status || filters.periodo
                ? 'Tente ajustar ou limpar os filtros aplicados.'
                : 'Ainda não foram registadas reclamações de notas.'
            }
          />
        ) : (
          lista.map((reclamacao) => {
            const nota = reclamacao.nota;
            const tdp = nota?.turma_disciplina_professor;
            const disciplina =
              tdp?.classe_turno_disciplina?.disciplina?.nome ?? 'Disciplina';
            const aluno = nota?.turma_aluno?.aluno;
            const nomeAluno =
              aluno?.user?.nome ??
              aluno?.inscricao?.candidato?.nome ??
              reclamacao.solicitante?.aluno?.nome ??
              'Aluno';
            const identificadorAluno =
              aluno?.matricula ?? aluno?.numero_processo;

            return (
              <Card key={reclamacao.id}>
                <CardHeader className="pb-3">
                  <div className="flex flex-col justify-between gap-2 sm:flex-row sm:items-start">
                    <div className="min-w-0 space-y-1">
                      <CardTitle className="text-base">{disciplina}</CardTitle>
                      <p className="text-sm text-muted-foreground">
                        {nomeAluno ? `${nomeAluno} · ` : ''}
                        {identificadorAluno}
                      </p>
                    </div>
                    <div className="flex shrink-0 items-center gap-2">
                      <span className="text-xs text-muted-foreground">
                        {formatarData(reclamacao.created_at)}
                      </span>

                      <Badge
                        variant={
                          reclamacao.status === 'aprovada'
                            ? 'default'
                            : reclamacao.status === 'reprovada'
                              ? 'destructive'
                              : 'secondary'
                        }
                      >
                        {labels[reclamacao.status] ?? reclamacao.status}
                      </Badge>
                    </div>
                  </div>
                </CardHeader>
                <CardContent className="space-y-3 pt-0">
                  <div className="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                    <p>
                      <span className="text-muted-foreground">Período:</span>{' '}
                      {nota?.periodo ? `${nota.periodo}º trimestre` : '—'}
                    </p>
                    <p>
                      <span className="text-muted-foreground">Componente:</span>{' '}
                      {componentes[reclamacao.componente] ??
                        reclamacao.componente}
                    </p>
                    <p>
                      <span className="text-muted-foreground">
                        Nota original:
                      </span>{' '}
                      {reclamacao.nota_original}
                    </p>
                    <p>
                      <span className="text-muted-foreground">
                        Nota sugerida:
                      </span>{' '}
                      {reclamacao.nota_reclamada ?? '—'}
                    </p>
                  </div>

                  <div className="flex flex-wrap items-center justify-between gap-3 border-t pt-3">
                    <details className="min-w-0 flex-1 text-sm">
                      <summary className="w-fit cursor-pointer font-medium text-primary">
                        Clique para ver justificação, parecer e anexos
                      </summary>
                      <div className="mt-3 space-y-3">
                        <div>
                          <p className="text-xs font-medium">Justificação</p>
                          <p className="whitespace-pre-wrap text-muted-foreground">
                            {reclamacao.justificativa}
                          </p>
                        </div>
                        <div className="border bg-muted/30 p-3">
                          <p className="font-medium">
                            {mensagensEstado[reclamacao.status] ??
                              'Estado da reclamação actualizado.'}
                          </p>
                          {reclamacao.status === 'aprovada' &&
                            reclamacao.nota_corrigida != null && (
                              <p className="mt-1 text-muted-foreground">
                                Nota corrigida: {reclamacao.nota_corrigida}
                              </p>
                            )}
                          {reclamacao.status === 'reprovada' && (
                            <p className="mt-1 text-muted-foreground">
                              Nota mantida: {reclamacao.nota_original}
                            </p>
                          )}
                        </div>
                        {reclamacao.parecer && (
                          <div>
                            <p className="text-xs font-medium">Parecer</p>
                            <p className="whitespace-pre-wrap text-muted-foreground">
                              {reclamacao.parecer}
                            </p>
                          </div>
                        )}
                        {reclamacao.anexos?.length > 0 && (
                          <div className="flex flex-wrap gap-2">
                            {reclamacao.anexos.map((anexo) => (
                              <AnexoPreview
                                key={anexo.id}
                                anexo={anexo}
                                previewUrl={
                                  anexosReclamacao({
                                    reclamacaoNota: reclamacao.id,
                                    anexo: anexo.id,
                                  }).url
                                }
                                downloadUrl={
                                  baixarAnexoReclamacao({
                                    reclamacaoNota: reclamacao.id,
                                    anexo: anexo.id,
                                  }).url
                                }
                              />
                            ))}
                          </div>
                        )}
                      </div>
                    </details>

                    {canDecidir &&
                      reclamacao.status !== 'aprovada' &&
                      reclamacao.status !== 'reprovada' && (
                        <Button
                          size="sm"
                          onClick={() => abrirDecisao(reclamacao)}
                        >
                          Analisar
                        </Button>
                      )}
                  </div>
                </CardContent>
              </Card>
            );
          })
        )}
      </InfiniteScroll>

      <DecidirReclamacaoDialog
        open={selecionada !== null}
        reclamacao={selecionada}
        submitUrl={selecionada ? decidirReclamacao(selecionada.id).url : ''}
        onClose={() => setSelecionada(null)}
      />
    </div>
  );
}
