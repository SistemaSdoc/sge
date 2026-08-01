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
import {
  Loader2,
  ClipboardListIcon,
  ChevronDown,
  ChevronUp,
  LockKeyhole,
  LockKeyholeOpen,
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
}) {
  const [periodo, setPeriodo] = useState('1');
  const [modalSolicitacao, setModalSolicitacao] = useState(false);
  const [expandidos, setExpandidos] = useState({});

  const { getValor, setValor } = useNotasLocais(data?.tdp_id);
  const formSolicitacao = useForm({
    tdp_id: data?.tdp_id,
    periodo,
    motivo: '',
  });

  const statusPeriodo = pautaStatus?.[periodo]?.status ?? 'rascunho';
  const finalizadaAutomaticamente =
    pautaStatus?.[periodo]?.finalizada_automaticamente ?? false;
  const estaFinalizada = statusPeriodo === 'finalizada';
  const podeOverride = Boolean(can?.overrideLockedPeriods);

  const periodoBloqueado =
    !podeOverride && (estaFinalizada || !dentroDoPrazo?.[periodo]);

  const podeGuardar =
    can?.create &&
    (podeOverride || !estaFinalizada) &&
    (dentroDoPrazo?.[periodo] || podeOverride);

  const podeFinalizar =
    can?.finalizar &&
    (podeOverride || !estaFinalizada) &&
    (dentroDoPrazo?.[periodo] || podeOverride);

  const podeSolicitarEdicao = can?.solicitarEdicao && estaFinalizada;

  const alunos = [...(data?.alunos?.data ?? [])].sort((alunoA, alunoB) =>
    (alunoA?.nome ?? '').localeCompare(alunoB?.nome ?? '', 'pt', {
      sensitivity: 'base',
    }),
  );
  const isEmpty = alunos.length === 0;

  // ── toggle global ──────────────────────────────────────────────
  const todosAbertos =
    alunos.length > 0 && alunos.every((a) => expandidos[a.turma_aluno_id]);

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

  // ── toggle individual ──────────────────────────────────────────
  const toggleAluno = (id) => {
    setExpandidos((prev) => ({ ...prev, [id]: !prev[id] }));
  };

  const submeterSolicitacao = () => {
    formSolicitacao.post('/dashboard/pautas/solicitar-edicao', {
      onSuccess: () => setModalSolicitacao(false),
    });
  };

  return (
    <>
      <Dialog open={modalSolicitacao} onOpenChange={setModalSolicitacao}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Solicitar edição ao director</DialogTitle>
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
                ? 'Esta pauta foi finalizada automaticamente por expiração do prazo.'
                : estaFinalizada
                  ? can?.overrideLockedPeriods
                    ? 'Esta pauta já foi finalizada, mas tens permissão para a alterar.'
                    : 'Esta pauta já foi finalizada. Para editar, solicite autorização ao director.'
                  : !dentroDoPrazo?.[periodo] && !can?.overrideLockedPeriods
                    ? 'O prazo de lançamento para este trimestre terminou.'
                    : 'Preencha as notas dos alunos para o trimestre seleccionado.'}
            </CardDescription>

            {errors?.periodo && (
              <p className="mt-2 text-sm text-destructive">{errors.periodo}</p>
            )}
          </div>

          <CardAction className="flex items-center gap-3">
            {/* ── Toggle global ── */}
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

            {/* hidden inputs para tdp_id e periodo */}
            <input type="hidden" name="tdp_id" value={data?.tdp_id ?? ''} />
            <input type="hidden" name="periodo" value={parseInt(periodo)} />

            {podeGuardar && (
              <Button
                type="submit"
                name="accao"
                value="guardar"
                variant="outline"
                disabled={isPending}
              >
                {isPending ? (
                  <Loader2 className="mr-2 size-4 animate-spin" />
                ) : null}
                Guardar rascunho
              </Button>
            )}

            {podeFinalizar && (
              <Button
                type="submit"
                name="accao"
                value="finalizar"
                disabled={isPending}
              >
                {isPending ? (
                  <Loader2 className="mr-2 size-4 animate-spin" />
                ) : null}
                Finalizar lançamento
              </Button>
            )}

            {podeSolicitarEdicao && (
              <Button
                type="button"
                variant="destructive"
                onClick={() => setModalSolicitacao(true)}
              >
                Solicitar edição ao director
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
                  {/* coluna do chevron individual */}

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
                      <TableCell className="px-4">{index + 1}</TableCell>
                      <TableCell className="px-4">{aluno.nome}</TableCell>

                      {/* ── MAC ── */}
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

                      {/* ── NPP ── */}
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

                      {/* ── NPT ── */}
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

                      {/* ── MT (calculado, sempre visível) ── */}
                      <TableCell className="text-center font-medium">
                        {mt ?? '-'}
                      </TableCell>

                      {/* ── Faltas ── */}
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

                      {/* ── Chevron individual ── */}
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

                      {/* ── Resultado (sempre visível) ── */}
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
