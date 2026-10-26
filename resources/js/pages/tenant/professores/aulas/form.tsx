import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
  ArrowLeft,
  CheckCircle2,
  Clock,
  Info,
  Lock,
  Save,
  Send,
  Users,
  XCircle,
} from 'lucide-react';
import { toast } from 'sonner';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from '@/components/ui/field';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/spinner';

interface Slot {
  id: string;
  dia_semana: number;
  hora_inicio: string;
  hora_fim: string;
  turma_id: string;
  turma_nome: string;
  disciplina_id: string;
  disciplina_nome: string;
  disciplina_sigla?: string;
}

interface Aula {
  id: string;
  data: string;
  data_humana: string;
  hora_inicio: string;
  hora_fim: string;
  turma_id: string;
  turma_nome: string;
  disciplina_id: string;
  disciplina_nome: string;
  tipo: 'normal' | 'exame' | 'extra' | 'substituicao';
  tipo_label: string;
  titulo: string | null;
  conteudo_previsto: string | null;
  sumario: string | null;
  tpc: string | null;
  observacoes: string | null;
  estado: 'rascunho' | 'publicado' | 'cancelado';
  estado_label: string;
  publicado_em: string | null;
  esta_editavel: boolean;
  horas_restantes_edicao: number | null;
  tem_sumario: boolean;
}

interface Turma {
  id: string;
  nome: string;
}

interface Disciplina {
  id: string;
  nome: string;
}

interface Props {
  slot?: Slot | null;
  aula_existente?: Aula | null;
  aula?: Aula | null;
  base?: Aula | null;
  data_sugerida?: string | null;
  turmas: Turma[];
  disciplinas?: Disciplina[];
}

export default function Form({
  slot,
  aula_existente,
  base,
  data_sugerida,
  turmas = [],
  disciplinas = [],
}: Props) {
  const aula = aula_existente ?? null;
  const editando = !!aula;

  const [cancelarDialog, setCancelarDialog] = useState(false);
  const [motivoCancelamento, setMotivoCancelamento] = useState('');

  const hoje = new Date().toISOString().slice(0, 10);

  const { data, setData, post, put, processing, errors } = useForm({
    classe_turno_disciplina_horario_id: slot?.id ?? null,

    data: aula?.data ?? data_sugerida ?? hoje,
    turma_id: aula?.turma_id ?? slot?.turma_id ?? '',
    disciplina_id: aula?.disciplina_id ?? slot?.disciplina_id ?? '',

    hora_inicio: aula?.hora_inicio ?? slot?.hora_inicio ?? '',
    hora_fim: aula?.hora_fim ?? slot?.hora_fim ?? '',

    tipo: aula?.tipo ?? 'normal',

    titulo: aula?.titulo ?? base?.titulo ?? '',
    conteudo_previsto: aula?.conteudo_previsto ?? base?.conteudo_previsto ?? '',
    sumario: aula?.sumario ?? base?.sumario ?? '',
    tpc: aula?.tpc ?? base?.tpc ?? '',
    observacoes: aula?.observacoes ?? base?.observacoes ?? '',

    professor_original_id: null,
  });

  const guardar = (e: React.FormEvent) => {
    e.preventDefault();

    if (editando && aula) {
      put(`/dashboard/professor/aulas/${aula.id}`, {
        preserveScroll: true,
        onSuccess: () => toast.success('Sumário salvo.'),
        onError: () => toast.error('Erro ao salvar.'),
      });
    } else {
      post('/dashboard/professor/aulas', {
        preserveScroll: true,
        onSuccess: () => toast.success('Rascunho criado.'),
        onError: (errs) => {
          const primeiro = Object.values(errs)[0];
          toast.error('Erro ao criar', { description: primeiro });
        },
      });
    }
  };

  const publicar = () => {
    if (!aula) {
      toast.error('Salve o rascunho primeiro antes de publicar.');
      return;
    }

    post(`/dashboard/professor/aulas/${aula.id}/publicar`, {
      preserveScroll: true,
      onSuccess: () =>
        toast.success('Aula publicada. Os alunos podem consultar.'),
      onError: () => toast.error('Erro ao publicar.'),
    });
  };

  const confirmarCancelamento = () => {
    if (!aula) return;

    router.post(
      `/dashboard/professor/aulas/${aula.id}/cancelar`,
      { observacoes: motivoCancelamento },
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success('Aula cancelada.');
          setCancelarDialog(false);
          setMotivoCancelamento('');
        },
        onError: () => toast.error('Erro ao cancelar.'),
      },
    );
  };

  const bloqueado = editando && aula && !aula.esta_editavel;
  const cancelado = aula?.estado === 'cancelado';
  const publicado = aula?.estado === 'publicado';
  const podePublicar =
    editando && aula && aula.estado === 'rascunho' && !bloqueado;

  const disciplinaNome =
    slot?.disciplina_nome ?? aula?.disciplina_nome ?? 'Aula';
  const turmaNome = slot?.turma_nome ?? aula?.turma_nome;
  const horaInicio = slot?.hora_inicio ?? aula?.hora_inicio;
  const horaFim = slot?.hora_fim ?? aula?.hora_fim;

  return (
    <>
      <Head title={editando ? 'Editar aula' : 'Nova aula'} />

      <div className="mx-auto w-full max-w-3xl space-y-4 p-4 sm:p-6">
        {/* Header com breadcrumb + estado */}
        <Card className="gap-0! overflow-visible pb-0">
          <CardHeader className="border-b border-foreground/10">
            <div className="min-w-0 space-y-1">
              <Breadcrumb>
                <BreadcrumbList className="flex-wrap">
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbLink asChild text-primary>
                      <Link
                        href="/dashboard/professor/aulas"
                        className="line-clamp-2 text-sm font-semibold" 
                      >
                        As minhas aulas
                      </Link>
                    </BreadcrumbLink>
                  </BreadcrumbItem>
                  <BreadcrumbSeparator />
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                      {editando ? 'Editar aula' : 'Nova aula'}
                    </BreadcrumbPage>
                  </BreadcrumbItem>
                </BreadcrumbList>
              </Breadcrumb>
              <CardDescription>
                {disciplinaNome}
                {turmaNome && ` · ${turmaNome}`}
                {horaInicio && ` · ${horaInicio}–${horaFim}`}
              </CardDescription>
            </div>

            <CardAction className="flex flex-wrap items-center gap-2 self-stretch sm:self-start">
              {turmaNome && (
                <Badge variant="secondary" className="gap-1 text-[10px]">
                  <Users className="size-3" />
                  {turmaNome}
                </Badge>
              )}
              {horaInicio && (
                <Badge
                  variant="outline"
                  className="gap-1 font-mono text-[10px]"
                >
                  <Clock className="size-3" />
                  {horaInicio}–{horaFim}
                </Badge>
              )}
              {editando && aula && (
                <Badge variant="outline" className="text-[10px]">
                  {aula.estado_label}
                </Badge>
              )}
            </CardAction>
          </CardHeader>

          <div className="flex items-center justify-between gap-2 px-4 py-2">
            <Button variant="ghost" size="sm" asChild className="hover:cursor-pointer">
              <Link href="/dashboard/professor/aulas">
                <ArrowLeft className="mr-1 size-4" />
                Voltar
              </Link>
            </Button>
            {editando && aula?.publicado_em && (
              <div className="flex items-center gap-1.5 text-[11px] text-muted-foreground">
                <Info className="size-3 shrink-0" />
                <span>
                  Publicado em {aula.publicado_em}
                  {aula.horas_restantes_edicao !== null &&
                    ` · ${aula.horas_restantes_edicao}h para editar`}
                </span>
              </div>
            )}
          </div>
        </Card>

        {/* Aviso bloqueado — neutro */}
        {bloqueado && (
          <Card className="gap-0 border pb-0">
            <CardContent className="flex items-start gap-3 p-4">
              <Lock className="size-5 shrink-0 text-muted-foreground" />
              <div className="text-sm">
                <p className="font-medium">Fora da janela de edição.</p>
                <p className="mt-1 text-muted-foreground">
                  Esta aula foi publicada há mais de 48 horas. Não pode mais
                  ser editada.
                </p>
              </div>
            </CardContent>
          </Card>
        )}

        {/* Formulário */}
        <form onSubmit={guardar} className="space-y-4">
          {/* Contexto — sem ícone */}
          <Card className="gap-0 pb-0">
            <CardHeader className="border-b px-5 py-4 sm:px-6">
              <CardTitle className="text-base">Contexto</CardTitle>
              <CardDescription>
                Dados da aula — alguns vêm do horário.
              </CardDescription>
            </CardHeader>

            <CardContent className="p-5 sm:p-6">
              <FieldGroup>
                <FieldSet>
                  <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <Field>
                      <FieldLabel htmlFor="data">Data</FieldLabel>
                      <Input
                        id="data"
                        type="date"
                        value={data.data}
                        onChange={(e) => setData('data', e.target.value)}
                        disabled={editando}
                      />
                      {errors.data && <FieldError>{errors.data}</FieldError>}
                    </Field>

                    <Field>
                      <FieldLabel htmlFor="tipo">Tipo de aula</FieldLabel>
                      <Select
                        value={data.tipo}
                        onValueChange={(v) => setData('tipo', v as any)}
                        disabled={editando}
                      >
                        <SelectTrigger id="tipo" className="w-full">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="normal">Normal</SelectItem>
                          <SelectItem value="substituicao">
                            Substituição
                          </SelectItem>
                          <SelectItem value="extra">Extra</SelectItem>
                          <SelectItem value="exame">Exame</SelectItem>
                        </SelectContent>
                      </Select>
                    </Field>

                    {!slot && !editando && (
                      <>
                        <Field className="sm:col-span-2">
                          <FieldLabel htmlFor="turma_id">Turma</FieldLabel>
                          <Select
                            value={data.turma_id}
                            onValueChange={(v) => setData('turma_id', v)}
                          >
                            <SelectTrigger id="turma_id" className="w-full">
                              <SelectValue placeholder="Selecione a turma" />
                            </SelectTrigger>
                            <SelectContent>
                              {turmas.map((t) => (
                                <SelectItem key={t.id} value={t.id}>
                                  {t.nome}
                                </SelectItem>
                              ))}
                            </SelectContent>
                          </Select>
                          {errors.turma_id && (
                            <FieldError>{errors.turma_id}</FieldError>
                          )}
                        </Field>

                        <Field className="sm:col-span-2">
                          <FieldLabel htmlFor="disciplina_id">
                            Disciplina
                          </FieldLabel>
                          <Select
                            value={data.disciplina_id}
                            onValueChange={(v) => setData('disciplina_id', v)}
                          >
                            <SelectTrigger id="disciplina_id" className="w-full">
                              <SelectValue placeholder="Selecione a disciplina" />
                            </SelectTrigger>
                            <SelectContent>
                              {disciplinas.map((d) => (
                                <SelectItem key={d.id} value={d.id}>
                                  {d.nome}
                                </SelectItem>
                              ))}
                            </SelectContent>
                          </Select>
                          {errors.disciplina_id && (
                            <FieldError>{errors.disciplina_id}</FieldError>
                          )}
                        </Field>
                      </>
                    )}

                    {!slot && (
                      <>
                        <Field>
                          <FieldLabel htmlFor="hora_inicio">
                            Hora início
                          </FieldLabel>
                          <Input
                            id="hora_inicio"
                            type="time"
                            value={data.hora_inicio}
                            onChange={(e) =>
                              setData('hora_inicio', e.target.value)
                            }
                          />
                        </Field>
                        <Field>
                          <FieldLabel htmlFor="hora_fim">Hora fim</FieldLabel>
                          <Input
                            id="hora_fim"
                            type="time"
                            value={data.hora_fim}
                            onChange={(e) =>
                              setData('hora_fim', e.target.value)
                            }
                          />
                          {errors.hora_fim && (
                            <FieldError>{errors.hora_fim}</FieldError>
                          )}
                        </Field>
                      </>
                    )}
                  </div>
                </FieldSet>
              </FieldGroup>
            </CardContent>
          </Card>

          {/* Conteúdo — sem ícone */}
          <Card className="gap-0 pb-0">
            <CardHeader className="border-b px-5 py-4 sm:px-6">
              <CardTitle className="text-base">Conteúdo da aula</CardTitle>
              <CardDescription>
                Escreva o que foi dado. O aluno só vê depois de publicar.
              </CardDescription>
            </CardHeader>

            <CardContent className="p-5 sm:p-6">
              <FieldGroup>
                <FieldSet>
                  <Field>
                    <FieldLabel htmlFor="titulo">
                      Título{' '}
                      <span className="text-xs font-normal text-muted-foreground">
                        (opcional)
                      </span>
                    </FieldLabel>
                    <Input
                      id="titulo"
                      value={data.titulo}
                      onChange={(e) => setData('titulo', e.target.value)}
                      placeholder="Ex: Equações do 2º grau"
                      disabled={bloqueado || cancelado}
                    />
                  </Field>

                  <Field>
                    <FieldLabel htmlFor="conteudo_previsto">
                      Conteúdo previsto{' '}
                      <span className="text-xs font-normal text-muted-foreground">
                        (visível ao aluno antes da aula)
                      </span>
                    </FieldLabel>
                    <Textarea
                      id="conteudo_previsto"
                      rows={2}
                      value={data.conteudo_previsto}
                      onChange={(e) =>
                        setData('conteudo_previsto', e.target.value)
                      }
                      placeholder="O que planeja dar nesta aula…"
                      disabled={bloqueado || cancelado}
                    />
                  </Field>

                  <Field>
                    <FieldLabel htmlFor="sumario">
                      Sumário{' '}
                      <span className="text-xs font-normal text-muted-foreground">
                        (o que foi efetivamente dado)
                      </span>
                    </FieldLabel>
                    <Textarea
                      id="sumario"
                      rows={5}
                      value={data.sumario}
                      onChange={(e) => setData('sumario', e.target.value)}
                      placeholder="Descreva o que foi dado nesta aula…"
                      disabled={bloqueado || cancelado}
                    />
                    {errors.sumario && (
                      <FieldError>{errors.sumario}</FieldError>
                    )}
                  </Field>

                  <Field>
                    <FieldLabel htmlFor="tpc">
                      Trabalho para casa{' '}
                      <span className="text-xs font-normal text-muted-foreground">
                        (opcional)
                      </span>
                    </FieldLabel>
                    <Textarea
                      id="tpc"
                      rows={2}
                      value={data.tpc}
                      onChange={(e) => setData('tpc', e.target.value)}
                      placeholder="Ex: Exercícios 1-10 da página 42…"
                      disabled={bloqueado || cancelado}
                    />
                  </Field>

                  <Field>
                    <FieldLabel htmlFor="observacoes">
                      Observações internas{' '}
                      <span className="text-xs font-normal text-muted-foreground">
                        (não visível ao aluno)
                      </span>
                    </FieldLabel>
                    <Textarea
                      id="observacoes"
                      rows={2}
                      value={data.observacoes}
                      onChange={(e) => setData('observacoes', e.target.value)}
                      placeholder="Notas só para você ou para a direção…"
                      disabled={bloqueado}
                    />
                  </Field>
                </FieldSet>
              </FieldGroup>
            </CardContent>
          </Card>

          {/* Ações */}
          <Card className="gap-0 pb-0">
            <CardContent className="flex flex-col-reverse gap-2 p-3 sm:flex-row sm:items-center sm:justify-between">
              <div className="flex items-center gap-2 text-xs text-muted-foreground">
                <Info className="size-3.5 shrink-0" />
                {editando && aula?.publicado_em ? (
                  <span>
                    Publicado em {aula.publicado_em}
                    {aula.horas_restantes_edicao !== null &&
                      ` · ${aula.horas_restantes_edicao}h para editar`}
                  </span>
                ) : (
                  <span>Rascunho visível apenas para você.</span>
                )}
              </div>

              <div className="flex flex-col-reverse gap-2 sm:flex-row sm:items-center">
               

                {editando && !cancelado && !bloqueado && (
                  <Button
                    type="button"
                    variant="ghost"
                    onClick={() => setCancelarDialog(true)}
                    className="hover:cursor-pointer"
                  >
                    <XCircle className="mr-1.5 size-4" />
                    Cancelar aula
                  </Button>
                )}

                {!bloqueado && !cancelado && (
                  <Button
                    type="submit"
                    variant="outline"
                    disabled={processing}
                    className="hover:cursor-pointer"
                  >
                    {processing ? (
                      <>
                        <Spinner />
                        Salvando...
                      </>
                    ) : (
                      <>
                        <Save className="mr-1.5 size-4" />
                        {editando ? 'Salvar' : 'Salvar rascunho'}
                      </>
                    )}
                  </Button>
                )}

                {podePublicar && (
                  <Button
                    type="button"
                    onClick={publicar}
                    disabled={processing}
                    className="hover:cursor-pointer"
                  >
                    {processing ? (
                      <>
                        <Spinner />
                        Publicando...
                      </>
                    ) : (
                      <>
                        <Send className="mr-1.5 size-4" />
                        Publicar para alunos
                      </>
                    )}
                  </Button>
                )}

                {publicado && (
                  <Badge variant="secondary" className="justify-center">
                    <CheckCircle2 className="mr-1 size-3" />
                    Publicado
                  </Badge>
                )}
              </div>
            </CardContent>
          </Card>
        </form>
      </div>

      {/* Dialog cancelar */}
      <Dialog open={cancelarDialog} onOpenChange={setCancelarDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <XCircle className="size-4 text-muted-foreground" />
              Cancelar esta aula?
            </DialogTitle>
            <DialogDescription>
              A aula será marcada como cancelada. Os alunos não a verão nos
              sumários. Esta ação não pode ser revertida.
            </DialogDescription>
          </DialogHeader>

          <Field>
            <FieldLabel htmlFor="motivo">Motivo (opcional)</FieldLabel>
            <Textarea
              id="motivo"
              rows={3}
              value={motivoCancelamento}
              onChange={(e) => setMotivoCancelamento(e.target.value)}
              placeholder="Ex: Feriado, falta do professor, greve…"
            />
          </Field>

          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setCancelarDialog(false)}
              className="hover:cursor-pointer"
            >
              Voltar
            </Button>
            <Button
              variant="outline"
              onClick={confirmarCancelamento}
              disabled={processing}
              className="hover:cursor-pointer"
            >
              {processing ? (
                <>
                  <Spinner />
                  Cancelando...
                </>
              ) : (
                'Cancelar aula'
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}