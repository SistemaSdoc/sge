import { Badge } from '@/components/ui/badge';
import { CardAction, CardFooter } from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription,
} from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination';
import { FileTextIcon, Loader2, ArrowRight } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import { exportarExcel } from '@/actions/App/Http/Controllers/ExportarPautaController';

// ── Helpers ────────────────────────────────────────────────────────────────

const PERIODOS = [
  { value: '1', label: '1º Trimestre' },
  { value: '2', label: '2º Trimestre' },
  { value: '3', label: '3º Trimestre' },
  { value: 'final', label: 'Pauta Final' },
  { value: 'recurso', label: 'Recurso' },
];

const ehPautaFinal = (p) => p === 'final';
const ehPautaRecurso = (p) => p === 'recurso';
const ehPautaEspecial = (p) => ehPautaFinal(p) || ehPautaRecurso(p);
const formatarNota = (n) =>
  n !== null && n !== undefined ? parseFloat(Number(n).toFixed(1)) : '—';

// ── Badge de situação por disciplina ─────────────────────────────────────

const SITUACAO_CONFIG = {
  aprovado: {
    label: 'Aprovado',
    className: 'bg-green-50 text-green-600 border-green-200',
  },
  transita_com_deficiencia: {
    label: 'Def.',
    className: 'bg-amber-50 text-amber-600 border-amber-200',
  },
  recurso_aprovado: {
    label: 'Recurso ✓',
    className: 'bg-blue-50 text-blue-600 border-blue-200',
  },
  recurso_reprovado: {
    label: 'Reprovado',
    className: 'bg-red-50 text-destructive border-red-200',
  },
  reprovado: {
    label: 'Reprovado',
    className: 'bg-red-50 text-destructive border-red-200',
  },
  sem_nota: { label: '—', className: 'bg-muted text-muted-foreground' },
};

function SituacaoBadge({ situacao }) {
  const config = SITUACAO_CONFIG[situacao] ?? SITUACAO_CONFIG.sem_nota;
  return (
    <Badge
      variant="outline"
      className={`text-xs font-medium ${config.className}`}
    >
      {config.label}
    </Badge>
  );
}

// ── Badge de resultado global do aluno ────────────────────────────────────

function ResultadoBadge({ resultado }) {
  if (resultado === null || resultado === undefined)
    return <span className="text-sm text-muted-foreground">—</span>;

  const config = {
    transita: {
      label: 'Transita',
      className: 'bg-green-50 text-green-600 border-green-200',
    },
    transita_com_deficiencia: {
      label: 'c/ Deficiência',
      className: 'bg-amber-50 text-amber-600 border-amber-200',
    },
    recurso: {
      label: 'Recurso',
      className: 'bg-yellow-50 text-yellow-600 border-yellow-200',
    },
    aprovado_recurso: {
      label: 'Aprovado',
      className: 'bg-green-50 text-green-600 border-green-200',
    },
    reprovado_recurso: {
      label: 'Reprovado',
      className: 'bg-red-50 text-destructive border-red-200',
    },
    pendente: {
      label: 'Pendente',
      className: 'bg-muted text-muted-foreground',
    },
    EEF: {
      label: 'EEF',
      className: 'bg-orange-50 text-orange-600 border-orange-200',
    },
  }[resultado] ?? {
    label: resultado,
    className: 'bg-muted text-muted-foreground',
  };

  return (
    <Badge
      variant="outline"
      className={`text-xs font-medium ${config.className}`}
    >
      {config.label}
    </Badge>
  );
}

// ── Célula de nota ────────────────────────────────────────────────────────

function CelulaNotaDisciplina({ nota, periodo }) {
  // Pauta trimestral
  if (!ehPautaEspecial(periodo)) {
    if (nota === null || nota === undefined)
      return <span className="text-muted-foreground">—</span>;

    const media = nota.media;
    return (
      <div className="flex flex-col items-center gap-0.5">
        <span className={media < 10 ? 'font-semibold text-destructive' : ''}>
          {media !== null ? formatarNota(media) : '—'}
        </span>
      </div>
    );
  }

  // Pauta de recurso
  if (ehPautaRecurso(periodo)) {
    if (!nota) return <span className="text-muted-foreground">—</span>;
    return (
      <div className="flex flex-col items-center gap-1">
        {nota.media_recurso !== null && (
          <span
            className={`text-sm font-medium ${nota.media_recurso < 10 ? 'text-destructive' : ''}`}
          >
            {formatarNota(nota.media_recurso)}
          </span>
        )}
        <SituacaoBadge situacao={nota.situacao} />
      </div>
    );
  }

  // Pauta final
  if (ehPautaFinal(periodo)) {
    if (!nota) return <span className="text-muted-foreground">—</span>;

    return (
      <div className="flex flex-col items-center gap-1">
        {/* Notas dos 3 trimestres */}
        <span className="text-xs text-muted-foreground">
          {formatarNota(nota.t1)} /&nbsp;
          {formatarNota(nota.t2)} /&nbsp;
          {formatarNota(nota.t3)}
        </span>

        {/* Média final */}
        <span
          className={`text-sm font-medium ${nota.mf !== null && nota.mf < 10 ? 'text-destructive' : ''}`}
        >
          {formatarNota(nota.mf)}
        </span>

        {/* Nota de recurso — só aparece se existir */}
        {nota.recurso !== null && (
          <span
            className={`text-xs ${nota.recurso < 10 ? 'text-destructive' : 'text-yellow-600'}`}
          >
            Rec: {formatarNota(nota.recurso)}
          </span>
        )}

        <SituacaoBadge situacao={nota.situacao ?? 'sem_nota'} />
      </div>
    );
  }
}

// ── Rodapé de resumo ──────────────────────────────────────────────────────

function ResumoFinal({ resumo }) {
  if (!resumo) return null;

  const items = [
    { label: 'Total', value: resumo.total, className: '' },
    { label: 'Transita', value: resumo.transita, className: 'text-green-600' },
    {
      label: 'c/ Deficiência',
      value: resumo.transita_com_deficiencia,
      className: 'text-amber-600',
    },
    { label: 'Recurso', value: resumo.recurso, className: 'text-blue-600' },
    {
      label: 'N/Apto',
      value: resumo.nao_transita,
      className: 'text-destructive',
    },
    { label: 'EEF', value: resumo.EEF, className: 'text-orange-600' },
  ];

  return (
    <div className="flex flex-wrap gap-4 text-sm">
      {items.map(({ label, value, className }) => (
        <span key={label} className="text-muted-foreground">
          {label}:{' '}
          <span className={`font-semibold ${className}`}>{value ?? 0}</span>
        </span>
      ))}
    </div>
  );
}

// ── Componente principal ──────────────────────────────────────────────────

export function PautaTable({
  data,
  periodo,
  setPeriodo,
  disciplinas = [],
  alunos = [],
  turmaId,
  instituicaoId,
  cursoTuteladoId,
  cursoClasseId,
  cursoClasseTurnoId,
}) {
  const isEmpty = !alunos || alunos.length === 0;
  // const { mutate: exportar, isPending: isExporting } = useExportarPauta()

  const periodoLabel =
    PERIODOS.find((p) => p.value === periodo)?.label ?? periodo;

  return (
    <Card className="mx-auto w-full max-w-7xl gap-0">
      <CardHeader className="border-b">
        <CardTitle>Pauta — {data?.turma?.nome}</CardTitle>

        <CardDescription>{periodoLabel}</CardDescription>

        <CardAction className="flex items-center gap-3">
          <Select value={periodo} onValueChange={setPeriodo}>
            <SelectTrigger className="w-44">
              <SelectValue placeholder="Seleccionar período" />
            </SelectTrigger>
            <SelectContent>
              {PERIODOS.map((p) => (
                <SelectItem key={p.value} value={p.value}>
                  {p.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          {/*{ehPautaFinal(periodo) && data?.turma?.curso_tutelado_id && (
            <Link
              href={`/dashboard/instituicoes/${data?.turma?.instituicao_id || 'default'}/cursos/${data?.turma?.curso_tutelado_id}/classes/default/turnos/default/turmas/${data?.turma?.id}/progressao`}
            >
              <Button variant="default" size="sm">
                <ArrowRight className="size-4 mr-2" />
                Executar Progressão
              </Button>
            </Link>
          )} */}

          <Button variant="outline" asChild>
            <a
              href={
                exportarExcel({
                  instituicao: instituicaoId,
                  cursoTutelado: cursoTuteladoId,
                  cursoClasse: cursoClasseId,
                  cursoClasseTurno: cursoClasseTurnoId,
                  turma: turmaId,
                }).url + `?periodo=${periodo}`
              }
              target="_blank"
              rel="noopener noreferrer"
            >
              Exportar
            </a>
          </Button>
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={FileTextIcon}
            title="Nenhuma pauta disponível"
            description="Ainda não existem notas lançadas para este período"
            action={{
              label: 'Lançar notas',
              href: '/dashboard/notas/create',
              variant: 'outline',
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/70">
                <TableHead className="w-10 px-4">#</TableHead>
                <TableHead className="px-4">Nome</TableHead>
                {disciplinas.map((disc) => (
                  <TableHead key={disc} className="px-4 text-center">
                    <div className="flex flex-col items-center">
                      <span>{disc}</span>
                      {ehPautaFinal(periodo) && (
                        <span className="text-[10px] font-normal text-muted-foreground">
                          1T / 2T / 3T / MF
                        </span>
                      )}
                      {ehPautaRecurso(periodo) && (
                        <span className="text-[10px] font-normal text-muted-foreground">
                          MF / Recurso
                        </span>
                      )}
                    </div>
                  </TableHead>
                ))}
                <TableHead className="px-4 text-center">Faltas</TableHead>
                <TableHead className="px-4 text-end">Resultado</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              {alunos.map((aluno) => (
                <TableRow key={aluno.aluno_id}>
                  <TableCell className="px-4 text-muted-foreground">
                    {aluno.numero}
                  </TableCell>
                  <TableCell className="px-4 font-medium">
                    {aluno.nome}
                  </TableCell>

                  {disciplinas.map((disc) => (
                    <TableCell key={disc} className="px-4 text-center">
                      <CelulaNotaDisciplina
                        nota={aluno.notas[disc]}
                        periodo={periodo}
                      />
                    </TableCell>
                  ))}

                  <TableCell className="px-4 text-center">
                    <span
                      className={
                        aluno.total_faltas > 10
                          ? 'font-medium text-destructive'
                          : ''
                      }
                    >
                      {aluno.total_faltas}
                    </span>
                  </TableCell>

                  <TableCell className="px-4">
                    <div className="flex justify-end">
                      <ResultadoBadge resultado={aluno.resultado} />
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </CardContent>

      {!isEmpty && (
        <CardFooter className="flex-col items-start gap-4 border-t pt-4">
          {/* Resumo — só aparece na pauta final */}
          {ehPautaFinal(periodo) && <ResumoFinal resumo={data?.resumo} />}

          <div className="flex w-full items-center justify-between">
            <span className="text-sm text-muted-foreground">Página 1 de 4</span>
            <Pagination>
              <PaginationContent>
                <PaginationItem>
                  <PaginationPrevious href="#" />
                </PaginationItem>
                <PaginationItem>
                  <PaginationNext href="#" />
                </PaginationItem>
              </PaginationContent>
            </Pagination>
          </div>
        </CardFooter>
      )}
    </Card>
  );
}
