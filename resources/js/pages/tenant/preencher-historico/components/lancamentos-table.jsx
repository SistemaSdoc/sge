import { useState } from 'react';
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
import { Loader2, LockKeyhole, LockKeyholeOpen, ArrowLeft } from 'lucide-react';
import { mediaTrimestral } from '@/utils/media-trimestral';
import { verificarSituacao } from '@/utils/verificar-situacao';

export default function LancamentosHistoricoTable({
  aluno,
  turmaAluno,
  turma,
  disciplinas = [],
  isPending,
  errors = {},
  can,
  onSubmit,
}) {
  const [periodo, setPeriodo] = useState('1');
  const [expandidos, setExpandidos] = useState({});

  // notas locais: { [tdp_id]: { [periodo]: { mac, npp, npt, faltas } } }
  // pré-populadas com o que vier do servidor
  const [notasLocais, setNotasLocais] = useState(() => {
    const inicial = {};
    disciplinas.forEach((d) => {
      inicial[d.tdp_id] = {};
      Object.entries(d.notas ?? {}).forEach(([per, nota]) => {
        inicial[d.tdp_id][per] = {
          mac: nota.mac ?? '',
          npp: nota.nota_prova_professor ?? '',
          npt: nota.nota_prova_trimestral ?? '',
          faltas: nota.faltas ?? '',
        };
      });
    });
    return inicial;
  });

  // ── helpers ────────────────────────────────────────────────────────────────

  const getNota = (tdpId, campo) =>
    notasLocais[tdpId]?.[periodo]?.[campo] ?? '';

  const setNota = (tdpId, campo, valor) => {
    setNotasLocais((prev) => ({
      ...prev,
      [tdpId]: {
        ...prev[tdpId],
        [periodo]: {
          ...(prev[tdpId]?.[periodo] ?? {}),
          [campo]: valor,
        },
      },
    }));
  };

  const toggleDisciplina = (tdpId) =>
    setExpandidos((prev) => ({ ...prev, [tdpId]: !prev[tdpId] }));

  const todosAbertos =
    disciplinas.length > 0 && disciplinas.every((d) => expandidos[d.tdp_id]);

  const toggleTodos = () => {
    if (todosAbertos) {
      setExpandidos({});
    } else {
      setExpandidos(
        Object.fromEntries(disciplinas.map((d) => [d.tdp_id, true])),
      );
    }
  };

  // verifica se há pelo menos uma nota preenchida
  const temNotasPreenchidas = () => {
    return disciplinas.some((d) => {
      const mac = getNota(d.tdp_id, 'mac');
      const npp = getNota(d.tdp_id, 'npp');
      const npt = getNota(d.tdp_id, 'npt');
      const faltas = getNota(d.tdp_id, 'faltas');
      return mac !== '' || npp !== '' || npt !== '' || faltas !== '';
    });
  };

  // monta payload no formato que o store espera
  const recolherDados = () => {
    const notas = {};
    disciplinas.forEach((d) => {
      notas[d.tdp_id] = {
        mac: getNota(d.tdp_id, 'mac'),
        npp: getNota(d.tdp_id, 'npp'),
        npt: getNota(d.tdp_id, 'npt'),
        faltas: getNota(d.tdp_id, 'faltas'),
      };
    });
    return {
      turma_aluno_id: turmaAluno.id,
      periodo: parseInt(periodo),
      notas,
    };
  };

  // ── render ─────────────────────────────────────────────────────────────────

  return (
    <>
      {/* Cabeçalho do aluno */}
      <Card>
        <CardHeader>
          <div>
            <CardTitle>Lançamento de Histórico Académico</CardTitle>
            <CardDescription>
              Aluno: <strong>{aluno?.nome}</strong>
              {aluno?.matricula ? ` (${aluno.matricula})` : ''} &nbsp;|&nbsp;
              Classe: <strong>{turma?.classe}</strong>
              &nbsp;|&nbsp; Turma: <strong>{turma?.nome}</strong>
              &nbsp;|&nbsp; Turno: <strong>{turma?.turno}</strong>
              &nbsp;|&nbsp; Ano: <strong>{turma?.ano_lectivo}</strong>
            </CardDescription>
          </div>
          <CardAction>
            <Button
              variant="outline"
              size="sm"
              onClick={() => window.history.back()}
            >
              <ArrowLeft className="mr-1 size-4" />
              Voltar
            </Button>
          </CardAction>
        </CardHeader>
      </Card>

      {/* Tabela de disciplinas */}
      <Card className="gap-0">
        <CardHeader className="border-b">
          <div>
            <CardTitle>Disciplinas</CardTitle>
            <CardDescription>
              Preenche as notas para o trimestre selecionado.
            </CardDescription>
            {errors?.periodo && (
              <p className="mt-2 text-sm text-destructive">{errors.periodo}</p>
            )}
          </div>

          <CardAction className="flex items-center gap-3">
            <Button
              type="button"
              variant="ghost"
              size="sm"
              onClick={toggleTodos}
            >
              {todosAbertos ? (
                <>
                  <LockKeyhole className="mr-1 size-4" />
                  Fechar todos
                </>
              ) : (
                <>
                  <LockKeyholeOpen className="mr-1 size-4" />
                  Abrir todos
                </>
              )}
            </Button>

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

            {can?.lancar && (
              <>
                <Button
                  type="button"
                  variant="outline"
                  disabled={isPending}
                  onClick={() => onSubmit('guardar', recolherDados())}
                >
                  {isPending && (
                    <Loader2 className="mr-2 size-4 animate-spin" />
                  )}
                  Guardar rascunho
                </Button>
                <Button
                  type="button"
                  disabled={isPending || !temNotasPreenchidas()}
                  title={
                    !temNotasPreenchidas()
                      ? 'Preencha pelo menos uma nota antes de finalizar'
                      : ''
                  }
                  onClick={() => onSubmit('finalizar', recolherDados())}
                >
                  {isPending && (
                    <Loader2 className="mr-2 size-4 animate-spin" />
                  )}
                  Finalizar trimestre
                </Button>
              </>
            )}
          </CardAction>
        </CardHeader>

        <CardContent className="p-0!">
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="w-1 px-4">#</TableHead>
                <TableHead className="w-48 px-4">Disciplina</TableHead>
                <TableHead className="w-1 text-center">MAC</TableHead>
                <TableHead className="w-1 text-center">NPP</TableHead>
                <TableHead className="w-1 text-center">NPT</TableHead>
                <TableHead className="w-1 text-center">MT</TableHead>
                <TableHead className="w-1 text-center">F.I</TableHead>
                <TableHead className="w-8 px-2" />
                <TableHead className="w-20 px-4 text-end">Resultado</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              {disciplinas.map((disciplina, index) => {
                const mac = getNota(disciplina.tdp_id, 'mac');
                const npp = getNota(disciplina.tdp_id, 'npp');
                const npt = getNota(disciplina.tdp_id, 'npt');
                const faltas = getNota(disciplina.tdp_id, 'faltas');
                const mt = mediaTrimestral(mac, npp, npt);
                const situacao = verificarSituacao(mt, Number(faltas));
                const aberto = Boolean(expandidos[disciplina.tdp_id]);

                return (
                  <TableRow key={disciplina.tdp_id}>
                    <TableCell className="px-4">{index + 1}</TableCell>
                    <TableCell className="px-4 font-medium">
                      {disciplina.nome}
                      <span className="ml-1 text-xs text-muted-foreground">
                        ({disciplina.sigla})
                      </span>
                    </TableCell>

                    {/* MAC */}
                    <TableCell>
                      {aberto ? (
                        <Input
                          type="number"
                          min={0}
                          max={20}
                          value={mac}
                          disabled={isPending || !can?.lancar}
                          onChange={(e) =>
                            setNota(disciplina.tdp_id, 'mac', e.target.value)
                          }
                          className="text-center"
                        />
                      ) : (
                        <span className="block text-center text-sm text-muted-foreground">
                          {mac !== '' ? mac : '-'}
                        </span>
                      )}
                    </TableCell>

                    {/* NPP */}
                    <TableCell>
                      {aberto ? (
                        <Input
                          type="number"
                          min={0}
                          max={20}
                          value={npp}
                          disabled={isPending || !can?.lancar}
                          onChange={(e) =>
                            setNota(disciplina.tdp_id, 'npp', e.target.value)
                          }
                          className="text-center"
                        />
                      ) : (
                        <span className="block text-center text-sm text-muted-foreground">
                          {npp !== '' ? npp : '-'}
                        </span>
                      )}
                    </TableCell>

                    {/* NPT */}
                    <TableCell>
                      {aberto ? (
                        <Input
                          type="number"
                          min={0}
                          max={20}
                          value={npt}
                          disabled={isPending || !can?.lancar}
                          onChange={(e) =>
                            setNota(disciplina.tdp_id, 'npt', e.target.value)
                          }
                          className="text-center"
                        />
                      ) : (
                        <span className="block text-center text-sm text-muted-foreground">
                          {npt !== '' ? npt : '-'}
                        </span>
                      )}
                    </TableCell>

                    {/* MT */}
                    <TableCell className="text-center font-medium">
                      {mt ?? '-'}
                    </TableCell>

                    {/* Faltas */}
                    <TableCell>
                      {aberto ? (
                        <Input
                          type="number"
                          min={0}
                          value={faltas}
                          disabled={isPending || !can?.lancar}
                          onChange={(e) =>
                            setNota(disciplina.tdp_id, 'faltas', e.target.value)
                          }
                          className="text-center"
                        />
                      ) : (
                        <span className="block text-center text-sm text-muted-foreground">
                          {faltas !== '' ? faltas : '-'}
                        </span>
                      )}
                    </TableCell>

                    {/* Toggle individual */}
                    <TableCell className="px-2">
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="h-6 w-6"
                        onClick={() => toggleDisciplina(disciplina.tdp_id)}
                      >
                        {aberto ? (
                          <LockKeyholeOpen className="size-4" />
                        ) : (
                          <LockKeyhole className="size-4" />
                        )}
                      </Button>
                    </TableCell>

                    {/* Resultado */}
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
        </CardContent>
      </Card>
    </>
  );
}
