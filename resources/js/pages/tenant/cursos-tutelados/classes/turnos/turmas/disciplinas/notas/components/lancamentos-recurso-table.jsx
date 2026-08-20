'use client';
import { useState, useEffect } from 'react';
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
  CardAction,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Loader2, ClipboardListIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';

function buildInitialNotas(alunos) {
  const state = {};
  for (const aluno of alunos) {
    state[aluno.aluno_id] = {};
    for (const [sigla, nota] of Object.entries(aluno.notas ?? {})) {
      state[aluno.aluno_id][sigla] = {
        nota_recurso: nota.recurso ?? '',
      };
    }
  }
  return state;
}

function SituacaoBadge({ valor }) {
  if (valor === '' || valor === null || valor === undefined)
    return <span className="text-sm text-muted-foreground">—</span>;

  const aprovado = Number(valor) >= 10;

  return aprovado ? (
    <Badge className="border-green-200 bg-green-50 text-green-600">
      Aprovado
    </Badge>
  ) : (
    <Badge className="border-red-200 bg-red-50 text-destructive">
      Reprovado
    </Badge>
  );
}
export default function LancamentosRecursoTable({
  alunos = [],
  onSubmit,
  isPending,
  podeLancarRecurso = true,
}) {
  const [notas, setNotas] = useState({});

  useEffect(() => {
    const state = {};
    for (const aluno of alunos) {
      state[aluno.turma_aluno_id] = aluno.nota_recurso ?? '';
    }
    setNotas(state);
  }, [alunos]);

  function handleSubmit() {
    if (!podeLancarRecurso) {
      return;
    }

    const lancamentos = alunos.map((aluno) => ({
      turma_aluno_id: aluno.turma_aluno_id,
      tdp_id: aluno.tdp_id,
      nota_recurso:
        notas[aluno.turma_aluno_id] !== ''
          ? Number(notas[aluno.turma_aluno_id])
          : null,
    }));
    onSubmit({ lancamentos });
  }

  const isEmpty = alunos.length === 0;
  const bloqueado = !podeLancarRecurso;

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <div>
          <CardTitle>Lançamento de Recurso</CardTitle>
          <CardDescription>
            Alunos com média final entre 7 e 9.5
          </CardDescription>
          {bloqueado && (
            <p className="mt-2 text-sm text-muted-foreground">
              O recurso já foi concluído. Apenas a direção pode alterar.
            </p>
          )}
        </div>
        <CardAction>
          <Button
            onClick={handleSubmit}
            disabled={isPending || isEmpty || bloqueado}
          >
            {isPending && <Loader2 className="mr-2 size-4 animate-spin" />}
            Lançar Recurso
          </Button>
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={ClipboardListIcon}
            title="Nenhum aluno em recurso"
            description="Nenhum aluno desta disciplina está em situação de recurso."
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/70">
                <TableHead className="w-10 px-4">#</TableHead>
                <TableHead className="px-4">Aluno</TableHead>
                <TableHead className="px-4 text-center">MF (P3)</TableHead>
                <TableHead className="px-4 text-center">Nota Recurso</TableHead>
                <TableHead className="px-4 text-end">Resultado</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {alunos.map((aluno, index) => {
                const valor = notas[aluno.turma_aluno_id] ?? '';
                return (
                  <TableRow key={aluno.turma_aluno_id}>
                    <TableCell className="px-4 text-muted-foreground">
                      {index + 1}
                    </TableCell>
                    <TableCell className="px-4 font-medium">
                      {aluno.nome}
                    </TableCell>
                    <TableCell className="px-4 text-center font-medium text-destructive">
                      {aluno.media_final_p3 ?? '—'}
                    </TableCell>
                    <TableCell className="px-4 text-center">
                      <Input
                        type="number"
                        min={0}
                        max={20}
                        value={valor}
                        disabled={bloqueado || isPending}
                        onChange={(e) =>
                          setNotas((prev) => ({
                            ...prev,
                            [aluno.turma_aluno_id]: e.target.value,
                          }))
                        }
                        className="mx-auto w-24 text-center"
                        placeholder="0–20"
                      />
                    </TableCell>
                    <TableCell className="px-4 text-end">
                      <SituacaoBadge valor={valor} />
                    </TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        )}
      </CardContent>
    </Card>
  );
}
