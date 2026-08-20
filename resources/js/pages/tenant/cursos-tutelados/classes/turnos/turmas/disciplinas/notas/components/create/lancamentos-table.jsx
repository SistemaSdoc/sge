import { useEffect, useState } from 'react';
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
import {
  Loader2,
  ClipboardListIcon,
  LockKeyhole,
  LockKeyholeOpen,
  Clock,
} from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { mediaTrimestral } from '@/utils/media-trimestral';
import { verificarSituacao } from '@/utils/verificar-situacao';
import { useNotasLocais } from '@/hooks/use-notas-locais';
import TablePagination from '@/components/table-pagination';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/react';

export default function LancamentosTable({
  data,
  isPending,
  errors = {},
  instituicaoId,
  cursoId,
  classeId,
  turnoId,
  turmaId,
  disciplinaId,
  periodosLancados = {},
  periodosDisponiveis = {},
  pagination = {},
  onPageChange,
  pautaStatus = {},
  dentroDoPrazo = {},
  can,
  onSubmit,
  autorizacaoAte = {}, // prazo de edição autorizado
  temSolicitacaoPendente = {},
}) {
  // ── 1. TODOS OS useState PRIMEIRO ──────────────────────────────
  const [periodo, setPeriodo] = useState('1');
  const [modalSolicitacao, setModalSolicitacao] = useState(false);
  const [expandidos, setExpandidos] = useState({});
  const [tempoRestante, setTempoRestante] = useState(null);

  // ── 2. HOOKS QUE DEPENDEM DE STATE ─────────────────────────────
  const { getValor, setValor } = useNotasLocais(data?.tdp_id);

  // ── 3. VARIÁVEIS DERIVADAS ──────────────────────────────────────
  const statusPeriodo = pautaStatus?.[periodo]?.status ?? 'rascunho';
  const finalizadaAutomaticamente =
    pautaStatus?.[periodo]?.finalizada_automaticamente ?? false;
  const estaFinalizada = statusPeriodo === 'finalizada';
  const estaExpirada = statusPeriodo === 'expirada';
  const podeOverride = Boolean(can?.notas?.overrideLockedPeriods);
  const temAutorizacaoActiva = Boolean(autorizacaoAte?.[periodo]);
  const tipoSolicitacao =
    estaFinalizada || estaExpirada ? 'reabertura_edicao' : 'extensao_prazo';

  const periodoBloqueado =
    !podeOverride &&
    !temAutorizacaoActiva &&
    (estaFinalizada || estaExpirada || !dentroDoPrazo?.[periodo]);

  const podeGuardar =
    can?.notas?.create &&
    (podeOverride ||
      temAutorizacaoActiva ||
      (!estaFinalizada && !estaExpirada && dentroDoPrazo?.[periodo]));

  const podeFinalizar =
    can?.notas?.finalizar &&
    (podeOverride ||
      temAutorizacaoActiva ||
      (!estaFinalizada && !estaExpirada && dentroDoPrazo?.[periodo]));

  const podeSolicitarEdicao =
    can?.notas?.solicitarEdicao &&
    !temAutorizacaoActiva &&
    (estaFinalizada || estaExpirada || !dentroDoPrazo?.[periodo]);

  // ── 4. useForm DEPOIS ──────────────────────────────────────────
  const formSolicitacao = useForm({
    tdp_id: data?.tdp_id,
    periodo: parseInt(periodo),
    motivo: '',
    tipo: tipoSolicitacao,
  });

  // ── 5. DADOS ────────────────────────────────────────────────────
  const alunos = [...(data?.alunos?.data ?? [])].sort((a, b) =>
    (a?.nome ?? '').localeCompare(b?.nome ?? '', 'pt', { sensitivity: 'base' }),
  );
  const isEmpty = alunos.length === 0;

  const todosAbertos =
    alunos.length > 0 && alunos.every((a) => expandidos[a.turma_aluno_id]);

  // ── 6. TODOS OS useEffect NO FINAL ─────────────────────────────
  useEffect(() => {
    const tipo =
      estaFinalizada || estaExpirada ? 'reabertura_edicao' : 'extensao_prazo';
    formSolicitacao.setData({
      ...formSolicitacao.data,
      tipo,
      periodo: parseInt(periodo),
    });
  }, [periodo, statusPeriodo]);

  useEffect(() => {
    const prazo = autorizacaoAte?.[periodo];
    if (!prazo) {
      setTempoRestante(null);
      return;
    }

    const calcular = () => {
      const diff = new Date(prazo) - new Date();
      if (diff <= 0) {
        setTempoRestante('Expirado');
        return;
      }
      const h = Math.floor(diff / 3600000);
      const m = Math.floor((diff % 3600000) / 60000);
      const s = Math.floor((diff % 60000) / 1000);
      setTempoRestante(`${h}h ${m}m ${s}s`);
    };

    calcular();
    const interval = setInterval(calcular, 1000);
    return () => clearInterval(interval);
  }, [periodo, autorizacaoAte]);

  // ── 7. FUNÇÕES ──────────────────────────────────────────────────
  const toggleTodos = () => {
    if (todosAbertos) {
      setExpandidos({});
    } else {
      const todos = {};
      alunos.forEach((a) => {
        todos[a.turma_aluno_id] = true;
      });
      setExpandidos(todos);
    }
  };

  const toggleAluno = (id) => {
    setExpandidos((prev) => ({ ...prev, [id]: !prev[id] }));
  };

  const submeterSolicitacao = () => {
    formSolicitacao.post('/dashboard/pautas/solicitar-edicao', {
      onSuccess: () => setModalSolicitacao(false),
    });
  };

  // Recolher os dados dos inputs para enviar
  const recolherDados = () => {
    const notas = {};
    alunos.forEach((aluno) => {
      notas[aluno.turma_aluno_id] = {
        mac:
          getValor(aluno.turma_aluno_id, periodo, 'mac') ??
          aluno.notas?.[periodo]?.mac ??
          '',
        npp:
          getValor(aluno.turma_aluno_id, periodo, 'npp') ??
          aluno.notas?.[periodo]?.nota_prova_professor ??
          '',
        npt:
          getValor(aluno.turma_aluno_id, periodo, 'npt') ??
          aluno.notas?.[periodo]?.nota_prova_trimestral ??
          '',
        faltas:
          getValor(aluno.turma_aluno_id, periodo, 'faltas') ??
          aluno.notas?.[periodo]?.faltas ??
          '',
      };
    });
    return {
      tdp_id: data?.tdp_id,
      periodo: parseInt(periodo),
      notas,
    };
  };

  return (
    <>
      <Dialog open={modalSolicitacao} onOpenChange={setModalSolicitacao}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {tipoSolicitacao === 'reabertura_edicao'
                ? 'Solicitar reabertura de edição'
                : 'Solicitar extensão de prazo'}
            </DialogTitle>
          </DialogHeader>

          <div className="space-y-2">
            <p className="text-sm text-muted-foreground">
              Explica o motivo pelo qual precisas editar esta pauta já
              finalizada.
            </p>
            <Textarea
              placeholder="Motivo da solicitação..."
              value={formSolicitacao.data.motivo}
              onChange={(e) =>
                formSolicitacao.setData('motivo', e.target.value)
              }
              rows={4}
            />
            {formSolicitacao.errors.motivo && (
              <p className="text-sm text-destructive">
                {formSolicitacao.errors.motivo}
              </p>
            )}
          </div>

          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setModalSolicitacao(false)}
            >
              Cancelar
            </Button>
            <Button
              onClick={submeterSolicitacao}
              disabled={
                formSolicitacao.processing || !formSolicitacao.data.motivo
              }
            >
              {formSolicitacao.processing ? (
                <Loader2 className="mr-2 size-4 animate-spin" />
              ) : null}
              Enviar pedido
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Card className="gap-0">
        <CardHeader className="border-b">
          <div>
            <CardTitle>{data?.disciplina?.nome}</CardTitle>

            <CardDescription>
              {finalizadaAutomaticamente
                ? 'Esta pauta foi encerrada automaticamente devido ao término do prazo estabelecido para o lançamento das notas.'
                : estaFinalizada
                  ? can?.notas?.overrideLockedPeriods
                    ? 'Esta pauta encontra-se encerrada. No entanto, possui permissão para efetuar alterações.'
                    : 'Esta pauta encontra-se encerrada. Para realizar alterações, é necessária a autorização da Direção.'
                  : !dentroDoPrazo?.[periodo] &&
                      !can?.notas?.overrideLockedPeriods
                    ? 'O período de lançamento das notas para este trimestre encontra-se encerrado.'
                    : 'Preencha as classificações dos alunos correspondentes ao trimestre selecionado.'}

              {tempoRestante && (
                <p className="mt-1 flex items-center gap-2 text-sm font-medium text-sky-600">
                  <Clock className="size-4" />{' '}
                  <strong>Tempo restante para edição:</strong> {tempoRestante}
                </p>
              )}
            </CardDescription>

            {errors?.periodo && (
              <p className="mt-2 text-sm text-destructive">{errors.periodo}</p>
            )}
          </div>

          <CardAction className="flex items-center gap-3">
            {!isEmpty && (
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
            )}
            <Select value={periodo} onValueChange={setPeriodo}>
              <SelectTrigger className="w-40">
                <SelectValue placeholder="Trimestre" />
              </SelectTrigger>

              <SelectContent>
                <SelectItem value="1">1º Trimestre</SelectItem>
                <SelectItem value="2" disabled={!periodosDisponiveis?.[2]}>
                  2º Trimestre
                </SelectItem>
                <SelectItem value="3" disabled={!periodosDisponiveis?.[3]}>
                  3º Trimestre
                </SelectItem>
              </SelectContent>
            </Select>
            <input type="hidden" name="tdp_id" value={data?.tdp_id ?? ''} />
            <input type="hidden" name="periodo" value={parseInt(periodo)} />
            {podeGuardar && (
              <Button
                type="button"
                variant="outline"
                disabled={isPending}
                onClick={() => onSubmit('guardar', recolherDados())}
              >
                Guardar rascunho
              </Button>
            )}
            {podeFinalizar && (
              <Button
                type="button"
                disabled={isPending}
                onClick={() => onSubmit('finalizar', recolherDados())}
              >
                Finalizar lançamento
              </Button>
            )}

            {podeSolicitarEdicao && !temSolicitacaoPendente?.[periodo] && (
              <Button
                type="button"
                variant="destructive"
                onClick={() => setModalSolicitacao(true)}
              >
                Solicitar edição ao director
              </Button>
            )}
            {podeSolicitarEdicao && temSolicitacaoPendente?.[periodo] && (
              <Badge className="bg-yellow-50 px-3 py-1 text-yellow-700">
                Solicitação pendente
              </Badge>
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
                  <TableHead className="w-8 px-2" />
                  <TableHead className="w-20 px-4 text-end">
                    Resultado
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {alunos.map((aluno, index) => {
                  const nota = aluno.notas?.[periodo] ?? {};
                  const aberto = Boolean(expandidos[aluno.turma_aluno_id]);

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
                      <TableCell className="px-4">{index + 1}</TableCell>
                      <TableCell className="px-4">{aluno.nome}</TableCell>

                      <TableCell>
                        {aberto ? (
                          <Input
                            type="number"
                            min={0}
                            max={20}
                            name={`notas[${aluno.turma_aluno_id}][mac]`}
                            value={mac}
                            disabled={isPending || periodoBloqueado}
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
                        ) : (
                          <span className="block text-center text-sm text-muted-foreground">
                            {mac !== '' ? mac : '-'}
                          </span>
                        )}
                      </TableCell>

                      <TableCell>
                        {aberto ? (
                          <Input
                            type="number"
                            min={0}
                            max={20}
                            name={`notas[${aluno.turma_aluno_id}][npp]`}
                            value={npp}
                            disabled={isPending || periodoBloqueado}
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
                        ) : (
                          <span className="block text-center text-sm text-muted-foreground">
                            {npp !== '' ? npp : '-'}
                          </span>
                        )}
                      </TableCell>

                      <TableCell>
                        {aberto ? (
                          <Input
                            type="number"
                            min={0}
                            max={20}
                            name={`notas[${aluno.turma_aluno_id}][npt]`}
                            value={npt}
                            disabled={isPending || periodoBloqueado}
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
                        ) : (
                          <span className="block text-center text-sm text-muted-foreground">
                            {npt !== '' ? npt : '-'}
                          </span>
                        )}
                      </TableCell>

                      <TableCell className="text-center font-medium">
                        {mt ?? '-'}
                      </TableCell>

                      <TableCell>
                        {aberto ? (
                          <Input
                            type="number"
                            min={0}
                            name={`notas[${aluno.turma_aluno_id}][faltas]`}
                            value={faltas}
                            disabled={isPending || periodoBloqueado}
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
                        ) : (
                          <span className="block text-center text-sm text-muted-foreground">
                            {faltas !== '' ? faltas : '-'}
                          </span>
                        )}
                      </TableCell>

                      <TableCell className="px-2">
                        <Button
                          type="button"
                          variant="ghost"
                          size="icon"
                          className="h-6 w-6"
                          onClick={() => toggleAluno(aluno.turma_aluno_id)}
                        >
                          {aberto ? (
                            <LockKeyholeOpen className="size-4" />
                          ) : (
                            <LockKeyhole className="size-4" />
                          )}
                        </Button>
                      </TableCell>

                      <TableCell className="px-4 text-end">
                        {nota?.is_rascunho && can?.overrideLockedPeriods && (
                          <Badge className="mr-1 bg-yellow-50 text-yellow-600">
                            Rascunho
                          </Badge>
                        )}
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
                          <span className="text-sm text-muted-foreground">
                            -
                          </span>
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
    </>
  );
}
