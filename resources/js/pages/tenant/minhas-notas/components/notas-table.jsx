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
                    </TableRow>
                  </TableHeader>

                  <TableBody>
                    {data.map((nota) => {
                      const trimData = nota.trimestres[trimestre];
                      const [prova1, prova2, prova3] = trimData.provas;

                      return (
                        <TableRow key={nota.id}>
                          <TableCell className="px-4 font-medium">
                            {nota.disciplina}
                          </TableCell>

                          <TableCell className="px-4 text-center">
                            {formatarNota(prova1)}
                          </TableCell>

                          <TableCell className="px-4 text-center">
                            {formatarNota(prova2)}
                          </TableCell>

                          <TableCell className="px-4 text-center">
                            {formatarNota(prova3)}
                          </TableCell>

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
  );
}
