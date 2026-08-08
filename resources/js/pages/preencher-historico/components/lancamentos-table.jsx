import { useForm, usePage } from '@inertiajs/react';
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
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  CardAction,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { ArrowLeft, Loader2, LockKeyhole, LockKeyholeOpen } from 'lucide-react';
import { mediaTrimestral } from '@/utils/media-trimestral';
import { verificarSituacao } from '@/utils/verificar-situacao';
import { Badge } from '@/components/ui/badge';

export default function PreencherHistoricoCreate({
  aluno,
  turmaAluno,
  turma,
  disciplinas = [],
  can,
}) {
  const [periodo, setPeriodo] = useState('1');
  const [expandidos, setExpandidos] = useState({});

  // useForm: notas[tdp_id][periodo][campo]
  // Pré-popula com os dados que vêm do servidor
  const notasIniciais = disciplinas.reduce((acc, d) => {
    acc[d.tdp_id] = d.notas ?? {};
    return acc;
  }, {});

  const { data, setData, post, processing, errors } = useForm({
    turma_aluno_id: turmaAluno.id,
    periodo: 1,
    notas: notasIniciais,
    accao: 'guardar',
  });

  // ── helpers ────────────────────────────────────────────────────────────────

  const getNota = (tdpId, campo) => {
    const nota = data.notas[tdpId]?.[periodo];
    if (!nota) return '';
    const map = { mac: 'mac', npp: 'nota_prova_professor', npt: 'nota_prova_trimestral', faltas: 'faltas' };
    return nota[map[campo]] ?? nota[campo] ?? '';
  };

  const setNota = (tdpId, campo, valor) => {
    const map = { mac: 'mac', npp: 'nota_prova_professor', npt: 'nota_prova_trimestral', faltas: 'faltas' };
    setData('notas', {
      ...data.notas,
      [tdpId]: {
        ...data.notas[tdpId],
        [periodo]: {
          ...(data.notas[tdpId]?.[periodo] ?? {}),
          [map[campo]]: valor,
        },
      },
    });
  };

  const toggleDisciplina = (tdpId) =>
    setExpandidos((prev) => ({ ...prev, [tdpId]: !prev[tdpId] }));

  const todosAbertos =
    disciplinas.length > 0 && disciplinas.every((d) => expandidos[d.tdp_id]);

  const toggleTodos = () => {
    if (todosAbertos) {
      setExpandidos({});
    } else {
      setExpandidos(Object.fromEntries(disciplinas.map((d) => [d.tdp_id, true])));
    }
  };

  // Monta o payload com o formato que o store espera: notas[tdp_id][mac|npp|npt|faltas]
  const handleSubmit = (accao) => {
    const notasEnvio = disciplinas.reduce((acc, d) => {
      acc[d.tdp_id] = {
        mac:    getNota(d.tdp_id, 'mac'),
        npp:    getNota(d.tdp_id, 'npp'),
        npt:    getNota(d.tdp_id, 'npt'),
        faltas: getNota(d.tdp_id, 'faltas'),
      };
      return acc;
    }, {});

    post(route('preencher-historico.store', { aluno: aluno.id }), {
      data: {
        turma_aluno_id: turmaAluno.id,
        periodo:        parseInt(periodo),
        notas:          notasEnvio,
        accao,
      },
      preserveScroll: true,
    });
  };

  // ── render ─────────────────────────────────────────────────────────────────

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      {/* Cabeçalho */}
      <Card>
        <CardHeader>
          <CardTitle>Lançamento de Histórico Académico</CardTitle>
          <CardDescription>
            Aluno: <strong>{aluno.nome}</strong> ({aluno.matricula}) &nbsp;|&nbsp;
            Classe: <strong>{turma.classe}</strong> &nbsp;|&nbsp;
            Turma: <strong>{turma.nome}</strong> &nbsp;|&nbsp;
            Turno: <strong>{turma.turno}</strong> &nbsp;|&nbsp;
            Ano: <strong>{turma.ano_lectivo}</strong>
          </CardDescription>
          <CardAction>
            <Button variant="outline" size="sm" onClick={() => window.history.back()}>
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
            {/* Toggle global */}
            <Button type="button" variant="ghost" size="sm" onClick={toggleTodos}>
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

            {/* Select Trimestre */}
            <Select
              value={periodo}
              onValueChange={(v) => {
                setPeriodo(v);
                setData('periodo', parseInt(v));
              }}
            >
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
                  disabled={processing}
                  onClick={() => handleSubmit('guardar')}
                >
                  {processing ? <Loader2 className="mr-2 size-4 animate-spin" /> : null}
                  Guardar rascunho
                </Button>
                <Button
                  type="button"
                  disabled={processing}
                  onClick={() => handleSubmit('finalizar')}
                >
                  {processing ? <Loader2 className="mr-2 size-4 animate-spin" /> : null}
                  Finalizar trimestre
                </Button>
              </>
            )}
          </CardAction>
        </CardHeader>

        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="w-1 px-4">#</TableHead>
                <TableHead className="w-48 px-4">Disciplina</TableHead>
                <TableHead className="w-1 text-center">MAC</TableHead>
                <TableHead className="w-1 text-center">NPP</TableHead>
                <TableHead className="w-1 text-center">NPT</TableHead>
                <TableHead className="w-1 text-center">MT</TableHead>
                <TableHead className="w-1 text-center">Faltas</TableHead>
                <TableHead className="w-8 px-2" />
                <TableHead className="w-20 px-4 text-end">Resultado</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              {disciplinas.map((disciplina, index) => {
                const mac    = getNota(disciplina.tdp_id, 'mac');
                const npp    = getNota(disciplina.tdp_id, 'npp');
                const npt    = getNota(disciplina.tdp_id, 'npt');
                const faltas = getNota(disciplina.tdp_id, 'faltas');
                const mt     = mediaTrimestral(mac, npp, npt);
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
                          type="number" min={0} max={20} step={0.5}
                          value={mac}
                          disabled={processing || !can?.lancar}
                          onChange={(e) => setNota(disciplina.tdp_id, 'mac', e.target.value)}
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
                          type="number" min={0} max={20} step={0.5}
                          value={npp}
                          disabled={processing || !can?.lancar}
                          onChange={(e) => setNota(disciplina.tdp_id, 'npp', e.target.value)}
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
                          type="number" min={0} max={20} step={0.5}
                          value={npt}
                          disabled={processing || !can?.lancar}
                          onChange={(e) => setNota(disciplina.tdp_id, 'npt', e.target.value)}
                          className="text-center"
                        />
                      ) : (
                        <span className="block text-center text-sm text-muted-foreground">
                          {npt !== '' ? npt : '-'}
                        </span>
                      )}
                    </TableCell>

                    {/* MT (calculada no frontend) */}
                    <TableCell className="text-center font-medium">
                      {mt ?? '-'}
                    </TableCell>

                    {/* Faltas */}
                    <TableCell>
                      {aberto ? (
                        <Input
                          type="number" min={0}
                          value={faltas}
                          disabled={processing || !can?.lancar}
                          onChange={(e) => setNota(disciplina.tdp_id, 'faltas', e.target.value)}
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
                        <Badge className="bg-green-50 text-green-500">APTO</Badge>
                      )}
                      {situacao === 'N/APTO' && (
                        <Badge className="bg-red-50 text-red-500">NÃO APTO</Badge>
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
    </div>
  );
}