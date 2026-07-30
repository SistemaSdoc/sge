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
import { Badge } from '@/components/ui/badge';
import { FileTextIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { mediaTrimestral } from '@/utils/media-trimestral';
import { verificarSituacao } from '@/utils/verificar-situacao';
import { create } from '@/actions/App/Http/Controllers/NotaDisciplinaController';
import { exportarDisciplina } from '@/actions/App/Http/Controllers/ExportarMiniPautaController';
import TablePagination from '@/components/table-pagination';

function buildInitialNotas(alunosData, periodo) {
  const state = {};
  for (const aluno of alunosData) {
    const nota = aluno.notas?.[periodo] ?? {};
    state[aluno.turma_aluno_id] = {
      mac: nota.mac ?? '',
      npp: nota.nota_prova_professor ?? '',
      npt: nota.nota_prova_trimestral ?? '',
      faltas: nota.faltas ?? '',
    };
  }
  return state;
}

export default function NotasTable({
  alunos = { data: [] },
  disciplina,
  tdpId,
  instituicao,
  cursoTutelado,
  cursoClasse,
  cursoClasseTurno,
  turma,
  can,
  periodosDisponiveis = {},
  todosDisponiveis = true,
  pagination = {},
  onPageChange,
}) {
  const [periodoSelecionado, setPeriodoSelecionado] = useState('1');
  const [periodoTabela, setPeriodoTabela] = useState('1');
  const [notas, setNotas] = useState({});

  const isEmpty = alunos.data.length === 0;

  useEffect(() => {
    setNotas(buildInitialNotas(alunos.data, periodoTabela));
  }, [alunos, periodoTabela]);

  const handlePeriodoChange = (value) => {
    setPeriodoSelecionado(value);

    if (value !== '0') {
      setPeriodoTabela(value);
    }
  };

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <CardTitle>{disciplina?.nome ?? 'Disciplina'}</CardTitle>

        {alunos.data.length > 0 && (
          <CardAction className="flex items-center gap-3">
            <Select
              value={periodoSelecionado}
              onValueChange={handlePeriodoChange}
            >
              <SelectTrigger className="w-40">
                <SelectValue placeholder="Trimestre" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="1">1º Trimestre</SelectItem>
                <SelectItem
                  value="2"
                  disabled={
                    !can?.overrideLockedPeriods && !periodosDisponiveis?.[2]
                  }
                >
                  2º Trimestre
                </SelectItem>
                <SelectItem
                  value="3"
                  disabled={
                    !can?.overrideLockedPeriods && !periodosDisponiveis?.[3]
                  }
                >
                  3º Trimestre
                </SelectItem>
                <SelectItem
                  value="0"
                  disabled={!can?.overrideLockedPeriods && !todosDisponiveis}
                >
                  Todos
                </SelectItem>
              </SelectContent>
            </Select>

            <Button>
              <a
                href={
                  exportarDisciplina({
                    instituicao,
                    cursoTutelado,
                    cursoClasse,
                    cursoClasseTurno,
                    turma,
                    classeTurnoDisciplina: disciplina?.id,
                  }).url + `?periodo=${periodoSelecionado}`
                }
                target="_blank"
                rel="noopener noreferrer"
              >
                Exportar
              </a>
            </Button>
          </CardAction>
        )}
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={FileTextIcon}
            title="Sem alunos associados"
            description={`Esta turma ainda não tem alunos`}
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
              {alunos.data.map((aluno, index) => {
                const n = notas[aluno.turma_aluno_id] ?? {};
                const mt = mediaTrimestral(n.mac, n.npp, n.npt);
                const faltasPeriodo = aluno.notas?.[periodoTabela]?.faltas ?? 0;
                const situacao = verificarSituacao(mt, faltasPeriodo);

                return (
                  <TableRow key={aluno.turma_aluno_id}>
                    <TableCell className="px-4">{index + 1}</TableCell>
                    <TableCell className="px-4">{aluno.nome}</TableCell>
                    <TableCell className="text-center">{n.mac ?? ''}</TableCell>
                    <TableCell className="text-center">{n.npp ?? ''}</TableCell>
                    <TableCell className="text-center">{n.npt ?? ''}</TableCell>
                    <TableCell className="text-center font-medium">
                      {mt ?? '-'}
                    </TableCell>
                    <TableCell className="text-center">
                      {n.faltas ?? ''}
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
