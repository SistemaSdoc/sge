import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { store as storeReclamacao } from '@/actions/App/Http/Controllers/Tenant/ReclamacaoNotaController';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { FieldError } from '@/components/ui/field';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { BookOpenIcon, Minus } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { TrimestroSelector } from './trimestre-selector';
import { getStatusVariant } from '@/utils/get-variants';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { ResultadoBadge } from '@/pages/tenant/pautas/components/pauta-table/resultado-badge';
import {
  corFaltas,
  corNota,
} from '@/pages/tenant/pautas/components/pauta-table/utils';

/**
 * Componente de tabela para exibição de notas do aluno.
 *
 * Estrutura:
 * - Coluna: Disciplina
 * - Colunas: Prova 1, Prova 2, Prova 3, Média Trimestral, Classificação
 *
 * @component
 * @param {Array} data - Array de notas com provas por trimestre
 * @param {number} trimestre - Trimestre selecionado (1, 2 ou 3)
 * @returns {JSX.Element}
 */
export function NotasTable({
  data = [],
  resultadoFinal,
  classes = [],
  classeId,
  handleClasseChange,
  periodo,
  onPeriodoChange,
}) {
  const [reclamacao, setReclamacao] = useState(null);
  const form = useForm({
    nota_id: '',
    componente: '',
    nota_reclamada: '',
    justificativa: '',
    anexos: [],
  });

  const abrirReclamacao = (nota, trimData, disciplina) => {
    if (!trimData.nota_id) {
      return;
    }

    const componentesDisponiveis = trimData.componentes
      .map((componente, index) => ({
        componente,
        notaOriginal: trimData.provas[index],
      }))
      .filter(({ componente, notaOriginal }) =>
        notaOriginal !== null
        && notaOriginal !== undefined
        && !trimData.reclamacoes_abertas?.[componente],
      );

    if (componentesDisponiveis.length === 0) {
      return;
    }

    const primeiro = componentesDisponiveis[0];

    form.setData({
      nota_id: trimData.nota_id,
      componente: primeiro.componente,
      nota_reclamada: '',
      justificativa: '',
      anexos: [],
    });
    form.clearErrors();
    setReclamacao({
      disciplina,
      componentes: componentesDisponiveis,
      componente: primeiro.componente,
      notaOriginal: primeiro.notaOriginal,
    });
  };

  const enviarReclamacao = () => {
    form.post(storeReclamacao.url(), {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        setReclamacao(null);
        form.reset();
      },
    });
  };

  const isTrimestral = ['1', '2', '3'].includes(periodo);
  const trimestre = Number(periodo);
  const disciplinasFinais = resultadoFinal?.disciplinas ?? [];
  const disciplinasRecurso = disciplinasFinais.filter(
    (disciplina) => disciplina.situacao === 'recurso',
  );
  const isEmpty = isTrimestral
    ? !data || data.length === 0
    : periodo === 'final'
      ? disciplinasFinais.length === 0
      : disciplinasRecurso.length === 0;

  const formatarNota = (n) =>
    n !== null && n !== undefined ? (
      parseFloat(Number(n).toFixed(1))
    ) : (
      <Minus
        className="mx-auto block size-4 text-muted-foreground"
        aria-label="Nota indisponível"
      />
    );

  const descricao = isTrimestral
    ? `Desempenho nas provas do ${trimestre === 1 ? '1º' : trimestre === 2 ? '2º' : '3º'} trimestre`
    : periodo === 'final'
      ? 'Resultados finais por disciplina'
      : 'Disciplinas e resultados do recurso';
  const rotuloResultadoFinal = {
    aprovado: 'Transita',
    transita: 'Transita',
    transita_com_deficiencia: 'Transita c/ Deficiência',
    recurso: 'Recurso',
    reprovado: 'N/Transita',
    reprovado_negativas: 'N/Transita',
    EEF: 'N/Transita',
    incompleto: 'Incompleto',
  };

  return (
    <>
    <Card className="w-full gap-0 pb-0">
      <CardHeader className="flex flex-col gap-3 border-b md:flex-row md:items-center">
        <div className="min-w-0 md:flex-1">
          <CardTitle>Minhas Notas</CardTitle>
          <CardDescription>{descricao}</CardDescription>
        </div>

        <CardAction className="flex w-full flex-col gap-2 self-stretch md:w-auto md:flex-row md:self-center">
          <Select value={classeId ?? ''} onValueChange={handleClasseChange}>
            <SelectTrigger id="classe" className="w-full md:w-fit">
              <SelectValue placeholder="Selecione a classe" />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                <SelectLabel>Classes</SelectLabel>
                {classes?.map((classe) => (
                  <SelectItem key={classe.id} value={classe.id}>
                    {classe.nome}
                  </SelectItem>
                ))}
              </SelectGroup>
            </SelectContent>
          </Select>
          <TrimestroSelector value={periodo} onChange={onPeriodoChange} />
        </CardAction>
      </CardHeader>

      <CardContent className="gap-0 p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={BookOpenIcon}
            title={
              periodo === 'recurso'
                ? 'Sem disciplinas em recurso'
                : 'Sem notas disponíveis'
            }
            description={
              periodo === 'recurso'
                ? 'Não há disciplinas com resultado de recurso disponível.'
                : 'Aguarde o lançamento de suas notas.'
            }
          />
        ) : (
          <div className="w-full overflow-x-auto">
            <Table>
              {isTrimestral ? (
                <>
                  <TableHeader>
                    <TableRow className="bg-muted/72">
                      <TableHead className="min-w-50 px-4">
                        Disciplina
                      </TableHead>

                      <TableHead className="min-w-20 px-4 text-center">
                        Prova 1
                      </TableHead>

                      <TableHead className="min-w-20 px-4 text-center">
                        Prova 2
                      </TableHead>

                      <TableHead className="min-w-20 px-4 text-center">
                        Prova 3
                      </TableHead>

                      <TableHead className="min-w-20 px-4 text-center">
                        Faltas
                      </TableHead>

                      <TableHead className="min-w-25 px-4 text-center">
                        Média Trim.
                      </TableHead>

                      <TableHead className="min-w-30 px-4 text-center">
                        Classificação
                      </TableHead>
                      <TableHead className="min-w-30 px-4 text-center">
                        Reclamação
                      </TableHead>
                    </TableRow>
                  </TableHeader>

                  <TableBody>
                    {data.map((nota) => {
                      const trimData = nota.trimestres[trimestre];
                      const [prova1, prova2, prova3] = trimData.provas;
                      const reclamacoesAbertas = trimData.reclamacoes_abertas ?? {};
                      const componentesReclamaveis = trimData.componentes.filter(
                        (componente, index) => trimData.provas[index] !== null
                          && trimData.provas[index] !== undefined
                          && !reclamacoesAbertas[componente],
                      );
                      const reclamacoesAbertasCount = Object.keys(reclamacoesAbertas).length;

                      return (
                        <TableRow key={nota.id}>
                          <TableCell className="px-4 font-medium">
                            {nota.disciplina}
                          </TableCell>

                          {[prova1, prova2, prova3].map((notaComponente, index) => (
                            <TableCell key={trimData.componentes[index]} className="px-4 text-center">
                              {formatarNota(notaComponente)}
                            </TableCell>
                          ))}

                          <TableCell className="px-4 text-center">
                            {trimData.faltas !== null &&
                            trimData.faltas !== undefined
                              ? trimData.faltas
                              : '-'}
                          </TableCell>

                          <TableCell className="px-4 text-center font-semibold">
                            {formatarNota(trimData.media)}
                          </TableCell>

                          <TableCell className="px-4 text-center">
                            {trimData.situacao ? (
                              <Badge
                                variant={getStatusVariant(trimData.situacao)}
                              >
                                {trimData.situacao}
                              </Badge>
                            ) : (
                              <span className="text-xs text-muted-foreground">
                                —
                              </span>
                            )}
                          </TableCell>
                          <TableCell className="px-4 text-center">
                            {reclamacoesAbertasCount > 0 && (
                              <p className="mb-1 text-xs text-muted-foreground">
                                {reclamacoesAbertasCount} nota(s) em reclamação
                              </p>
                            )}
                            <Button
                              type="button"
                              variant="outline"
                              size="sm"
                              onClick={() => abrirReclamacao(nota, trimData, nota.disciplina)}
                              disabled={!trimData.nota_id || componentesReclamaveis.length === 0}
                            >
                              {componentesReclamaveis.length === 0 && reclamacoesAbertasCount > 0
                                ? 'Reclamação aberta'
                                : reclamacoesAbertasCount > 0
                                  ? 'Reclamar outra nota'
                                  : 'Reclamar'}
                            </Button>
                          </TableCell>
                        </TableRow>
                      );
                    })}
                  </TableBody>
                </>
              ) : periodo === 'final' ? (
                <>
                  <TableHeader>
                    <TableRow className="bg-muted/72">
                      <TableHead className="min-w-50 px-4">
                        Disciplina
                      </TableHead>
                      <TableHead className="min-w-20 px-4 text-center">
                        MT1
                      </TableHead>
                      <TableHead className="min-w-20 px-4 text-center">
                        MT2
                      </TableHead>
                      <TableHead className="min-w-20 px-4 text-center">
                        MT3
                      </TableHead>
                      <TableHead className="min-w-20 px-4 text-center">
                        F.I.
                      </TableHead>
                      <TableHead className="min-w-25 px-4 text-center">
                        MFD
                      </TableHead>
                      <TableHead className="min-w-40 px-4 text-center">
                        Resultado
                      </TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {disciplinasFinais.map((disciplina) => (
                      <TableRow key={disciplina.disciplina_id}>
                        <TableCell className="px-4 font-medium">
                          {disciplina.disciplina}
                        </TableCell>
                        <TableCell className="px-4 text-center">
                          <span className={corNota(disciplina.mt1)}>
                            {formatarNota(disciplina.mt1)}
                          </span>
                        </TableCell>
                        <TableCell className="px-4 text-center">
                          <span className={corNota(disciplina.mt2)}>
                            {formatarNota(disciplina.mt2)}
                          </span>
                        </TableCell>
                        <TableCell className="px-4 text-center">
                          <span className={corNota(disciplina.mt3)}>
                            {formatarNota(disciplina.mt3)}
                          </span>
                        </TableCell>
                        <TableCell className="px-4 text-center">
                          <span className={corFaltas(disciplina.total_faltas)}>
                            {disciplina.total_faltas ?? '—'}
                          </span>
                        </TableCell>
                        <TableCell className="px-4 text-center font-semibold">
                          <span className={corNota(disciplina.mfd)}>
                            {formatarNota(disciplina.mfd)}
                          </span>
                        </TableCell>
                        <TableCell className="px-4 text-center">
                          <ResultadoBadge
                            resultado={
                              disciplina.situacao === 'aprovado'
                                ? 'transita'
                                : disciplina.situacao
                            }
                            label={
                              rotuloResultadoFinal[disciplina.situacao] ??
                              disciplina.situacao
                            }
                          />
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </>
              ) : (
                <>
                  <TableHeader>
                    <TableRow className="bg-muted/72">
                      <TableHead className="min-w-50 px-4">
                        Disciplina
                      </TableHead>
                      <TableHead className="min-w-35 px-4 text-center">
                        Nota de Recurso
                      </TableHead>
                      <TableHead className="min-w-40 px-4 text-center">
                        Resultado final
                      </TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {disciplinasRecurso.map((disciplina) => (
                      <TableRow key={disciplina.disciplina_id}>
                        <TableCell className="px-4 font-medium">
                          {disciplina.disciplina}
                        </TableCell>
                        <TableCell className="px-4 text-center font-semibold">
                          <span className={corNota(disciplina.nota_recurso)}>
                            {formatarNota(disciplina.nota_recurso)}
                          </span>
                        </TableCell>
                        <TableCell className="px-4 text-center">
                          <ResultadoBadge
                            resultado={
                              disciplina.situacao_recurso ?? 'pendente'
                            }
                          />
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </>
              )}
            </Table>
          </div>
        )}
      </CardContent>
    </Card>

    <Dialog
      open={reclamacao !== null}
      onOpenChange={(open) => {
        if (!open) {
          setReclamacao(null);
          form.reset();
        }
      }}
    >
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Reclamar esta nota</DialogTitle>
          <DialogDescription>
            A reclamação será enviada ao professor da disciplina e à direção.
          </DialogDescription>
        </DialogHeader>

        {reclamacao && (
          <div className="space-y-4 py-2">
            <p className="text-sm text-muted-foreground">
              Disciplina: <strong className="text-foreground">{reclamacao.disciplina}</strong>
            </p>

            <div className="space-y-1.5">
              <Label htmlFor="componente-reclamacao">Nota a reclamar</Label>
              <Select
                value={form.data.componente}
                onValueChange={(componente) => {
                  const selecionado = reclamacao.componentes.find((item) => item.componente === componente);
                  form.setData('componente', componente);
                  setReclamacao((atual) => ({
                    ...atual,
                    componente,
                    notaOriginal: selecionado?.notaOriginal ?? atual.notaOriginal,
                  }));
                }}
              >
                <SelectTrigger
                  id="componente-reclamacao"
                  aria-invalid={Boolean(form.errors.componente)}
                >
                  <SelectValue placeholder="Selecione a nota" />
                </SelectTrigger>
                <SelectContent>
                  {reclamacao.componentes.map((item) => (
                    <SelectItem key={item.componente} value={item.componente}>
                      {item.componente === 'mac'
                        ? `MAC - ${item.notaOriginal}`
                        : item.componente === 'nota_prova_professor'
                          ? `Prova do professor - ${item.notaOriginal}`
                          : `Prova trimestral - ${item.notaOriginal}`}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <FieldError>{form.errors.componente}</FieldError>
            </div>

            <p className="text-sm text-muted-foreground">
              Nota atual: <strong className="text-foreground">{reclamacao.notaOriginal}</strong>
            </p>

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label htmlFor="nota-reclamada">Nota que considera correta</Label>
                <Input
                  id="nota-reclamada"
                  type="number"
                  min="0"
                  max="20"
                  step="0.1"
                  aria-invalid={Boolean(form.errors.nota_reclamada)}
                  value={form.data.nota_reclamada}
                  onChange={(event) => form.setData('nota_reclamada', event.target.value)}
                />
                <FieldError>{form.errors.nota_reclamada}</FieldError>
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="anexos-reclamacao">Anexos</Label>
                <Input
                  id="anexos-reclamacao"
                  type="file"
                  multiple
                  accept=".pdf,.png,.jpg,.jpeg,.doc,.docx"
                  aria-invalid={Boolean(form.errors.anexos)}
                  onChange={(event) => form.setData('anexos', Array.from(event.target.files ?? []).slice(0, 5))}
                />
                <FieldError>
                  {form.errors.anexos ?? Object.entries(form.errors).find(([field]) => field.startsWith('anexos.'))?.[1]}
                </FieldError>
              </div>
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="justificativa-reclamacao">Justificativa</Label>
              <Textarea
                id="justificativa-reclamacao"
                rows={5}
                maxLength={2000}
                aria-invalid={Boolean(form.errors.justificativa)}
                value={form.data.justificativa}
                onChange={(event) => form.setData('justificativa', event.target.value)}
                placeholder="Explique a diferença entre a prova e a nota lançada."
              />
              <FieldError>{form.errors.justificativa}</FieldError>
            </div>
          </div>
        )}

        <DialogFooter>
          <Button type="button" variant="outline" onClick={() => setReclamacao(null)}>Cancelar</Button>
          <Button type="button" disabled={form.processing} onClick={enviarReclamacao}>
            {form.processing ? 'A enviar...' : 'Enviar reclamação'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
    </>
  );
}
