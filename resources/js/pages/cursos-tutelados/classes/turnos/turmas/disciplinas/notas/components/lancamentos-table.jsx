import { useState, useEffect, useRef } from 'react';
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Loader2, ClipboardListIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { mediaTrimestral } from '@/utils/media-trimestral';
import { verificarSituacao } from '@/utils/verificar-situacao';
import { useNotasLocais } from '@/hooks/use-notas-locais';
import TablePagination from '@/components/table-pagination';

export default function LancamentosTable({
  data,
  isPending,
  instituicaoId,
  cursoId,
  classeId,
  turnoId,
  turmaId,
  disciplinaId,
  can,
  pagination = {},
  onPageChange,
}) {
  const [periodo, setPeriodo] = useState('1');
  const { getValor, setValor } = useNotasLocais(data?.tdp_id);
  const alunos = [...(data?.alunos?.data ?? [])].sort((alunoA, alunoB) =>
    (alunoA?.nome ?? '').localeCompare(alunoB?.nome ?? '', 'pt', {
      sensitivity: 'base',
    }),
  );
  const isEmpty = alunos.length === 0;
  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <div>
          <CardTitle>{data?.disciplina?.nome}</CardTitle>

          <CardDescription>
            Preencha as notas dos alunos para o trimestre seleccionado
          </CardDescription>
        </div>

        <CardAction className="flex items-center gap-3">
          <Select value={periodo} onValueChange={setPeriodo}>
            <SelectTrigger className="w-40">
              <SelectValue placeholder="Trimestre" />
            </SelectTrigger>

            <SelectContent>
              <SelectItem value="1">1º Trimestre</SelectItem>
              <SelectItem value="2">2º Trimestre</SelectItem>
              <SelectItem value="3">3º Trimestre</SelectItem>
            </SelectContent>
          </Select>

          {/* hidden inputs para tdp_id e periodo */}
          <input type="hidden" name="tdp_id" value={data?.tdp_id ?? ''} />
          <input type="hidden" name="periodo" value={parseInt(periodo)} />

          {can?.create && (
            <Button type="submit" disabled={isPending}>
              {isPending ? (
                <Loader2 className="mr-2 size-4 animate-spin" />
              ) : null}
              Lançar
            </Button>
          )}
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={ClipboardListIcon}
            title="Nenhum lançamento"
            description="Nenhuma nota para registar"
            action={{
              label: 'Lançar Notas',
              href: '#',
              variant: 'outline',
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="w-1! px-4">#</TableHead>
                <TableHead className="w-48 px-4">Aluno</TableHead>
                <TableHead className="w-1 text-center">MAC</TableHead>
                <TableHead className="w-1 text-center">NPP</TableHead>
                <TableHead className="w-1 text-center">NPT</TableHead>
                <TableHead className="w-1 text-center">MT</TableHead>
                <TableHead className="w-1 text-center">F.I</TableHead>
                <TableHead className="w-20 px-4 text-end">Resultado</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {alunos.map((aluno, index) => {
                const nota = aluno.notas?.[periodo] ?? {};

                // local tem prioridade sobre servidor
                const mac =
                  getValor(aluno.turma_aluno_id, periodo, 'mac') ??
                  nota.mac ??
                  '';
                const npp =
                  getValor(aluno.turma_aluno_id, periodo, 'npp') ??
                  nota.nota_prova_professor ??
                  '';
                const npt =
                  getValor(aluno.turma_aluno_id, periodo, 'npt') ??
                  nota.nota_prova_trimestral ??
                  '';
                const faltas =
                  getValor(aluno.turma_aluno_id, periodo, 'faltas') ??
                  nota.faltas ??
                  '';

                const mt = mediaTrimestral(mac, npp, npt);
                const situacao = verificarSituacao(mt, Number(faltas));

                return (
                  <TableRow key={aluno.turma_aluno_id}>
                    {' '}
                    {/* ← key sem período */}
                    <TableCell className="px-4">{index + 1}</TableCell>
                    <TableCell className="px-4">{aluno.nome}</TableCell>
                    <TableCell>
                      <Input
                        type="number"
                        min={0}
                        max={20}
                        name={`notas[${aluno.turma_aluno_id}][mac]`}
                        value={mac}
                        onChange={(e) =>
                          setValor(
                            aluno.turma_aluno_id,
                            periodo,
                            'mac',
                            e.target.value,
                          )
                        }
                        className="text-center"
                      />
                    </TableCell>
                    <TableCell>
                      <Input
                        type="number"
                        min={0}
                        max={20}
                        name={`notas[${aluno.turma_aluno_id}][npp]`}
                        value={npp}
                        onChange={(e) =>
                          setValor(
                            aluno.turma_aluno_id,
                            periodo,
                            'npp',
                            e.target.value,
                          )
                        }
                        className="text-center"
                      />
                    </TableCell>
                    <TableCell>
                      <Input
                        type="number"
                        min={0}
                        max={20}
                        name={`notas[${aluno.turma_aluno_id}][npt]`}
                        value={npt}
                        onChange={(e) =>
                          setValor(
                            aluno.turma_aluno_id,
                            periodo,
                            'npt',
                            e.target.value,
                          )
                        }
                        className="text-center"
                      />
                    </TableCell>
                    <TableCell className="text-center font-medium">
                      {mt ?? '-'}
                    </TableCell>
                    <TableCell>
                      <Input
                        type="number"
                        min={0}
                        name={`notas[${aluno.turma_aluno_id}][faltas]`}
                        value={faltas}
                        onChange={(e) =>
                          setValor(
                            aluno.turma_aluno_id,
                            periodo,
                            'faltas',
                            e.target.value,
                          )
                        }
                        className="text-center"
                      />
                    </TableCell>
                    <TableCell className="px-4 text-end">
                      {situacao === 'APTO' && (
                        <Badge className="bg-green-50 text-green-500">
                          APTO
                        </Badge>
                      )}
                      {situacao === 'N/APTO' && (
                        <Badge className="bg-red-50 text-red-500">
                          NÃO APTO
                        </Badge>
                      )}
                      {situacao === null && (
                        <span className="text-sm text-muted-foreground">-</span>
                      )}
                    </TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        )}
      </CardContent>
      <TablePagination pagination={pagination} onPageChange={onPageChange} />
    </Card>
  );
}
