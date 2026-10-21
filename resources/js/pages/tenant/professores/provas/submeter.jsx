import { Head, Link, useForm } from '@inertiajs/react';
import {
  ArrowLeft,
  Users,
  Lock,
  Upload,
  FileText,
} from 'lucide-react';
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
import { Spinner } from '@/components/spinner';

export default function Submeter({
  prazo,
  turmas = [],
  turma_selecionada = null,
}) {
  const { data, setData, post, processing, errors } = useForm({
    turma_id: turma_selecionada || '',
    arquivo_prova: null,
    arquivo_chave: null,
    comentario: '',
  });

  const turmaVemDoCard = !!turma_selecionada;
  const turmaInfo = turmas.find((t) => t.id === turma_selecionada);

  const submit = (e) => {
    e.preventDefault();
    post(`/dashboard/professor/provas/submeter/${prazo.id}`, {
      forceFormData: true,
      onSuccess: () => {
        window.location.href = '/dashboard/professor/provas';
      },
    });
  };

  return (
    <>
      <Head title={`Submeter: ${prazo.titulo}`} />

      <div className="mx-auto w-full max-w-3xl space-y-4 p-4 sm:p-6">
        {/* Header */}
        <Card className="gap-0 pb-0">
          <CardHeader className="flex flex-col gap-3 border-b border-foreground/10 md:flex-row md:items-center">
            <div className="min-w-0 md:flex-1">
              <Breadcrumb>
                <BreadcrumbList className="flex-wrap">
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbLink asChild>
                      <Link
                        href="/dashboard/professor/provas"
                        className="line-clamp-2 text-sm font-semibold text-primary"
                      >
                        Submissão de Provas
                      </Link>
                    </BreadcrumbLink>
                  </BreadcrumbItem>
                  <BreadcrumbSeparator />
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                      Submeter
                    </BreadcrumbPage>
                  </BreadcrumbItem>
                </BreadcrumbList>
              </Breadcrumb>
              <CardDescription>
                {prazo.disciplina?.nome || 'Todas as disciplinas'} • Limite:{' '}
                {prazo.data_limite}
              </CardDescription>
            </div>
            <CardAction>
              <Button variant="outline" size="sm" asChild>
                <Link href="/dashboard/professor/provas">
                  <ArrowLeft className="mr-1.5 size-4" />
                  Voltar
                </Link>
              </Button>
            </CardAction>
          </CardHeader>

          <CardContent className="p-4 sm:p-6">
            <form onSubmit={submit} encType="multipart/form-data">
              <FieldGroup>
                <FieldSet>
                  {/* Turma */}
                  <Field>
                    <FieldLabel
                      htmlFor="turma_id"
                      className="flex items-center gap-2"
                    >
                      <Users className="size-4" />
                      Turma <span className="text-destructive">*</span>
                    </FieldLabel>

                    {turmaVemDoCard ? (
                      <div className="flex items-center gap-2 border bg-muted/40 p-3">
                        <Lock className="size-4 text-muted-foreground" />
                        <span className="font-medium">
                          {turmaInfo?.nome || 'Turma selecionada'}
                        </span>
                        <Badge
                          variant="outline"
                          className="ml-auto text-[10px]"
                        >
                          Fixa
                        </Badge>
                      </div>
                    ) : (
                      <Select
                        value={data.turma_id}
                        onValueChange={(value) =>
                          setData('turma_id', value)
                        }
                        disabled={processing}
                      >
                        <SelectTrigger id="turma_id" className="w-full">
                          <SelectValue placeholder="Selecione a turma..." />
                        </SelectTrigger>
                        <SelectContent>
                          {turmas.length === 0 ? (
                            <SelectItem value="-" disabled>
                              Nenhuma turma disponível
                            </SelectItem>
                          ) : (
                            turmas.map((t) => (
                              <SelectItem key={t.id} value={t.id}>
                                {t.nome}
                              </SelectItem>
                            ))
                          )}
                        </SelectContent>
                      </Select>
                    )}

                    <p className="text-xs text-muted-foreground">
                      {turmaVemDoCard
                        ? 'A turma foi selecionada a partir do card do prazo.'
                        : 'Selecione a turma para a qual está a submeter.'}
                    </p>

                    {errors.turma_id && (
                      <FieldError>{errors.turma_id}</FieldError>
                    )}
                  </Field>

                  {/* Arquivo da Prova */}
                  <Field>
                    <FieldLabel
                      htmlFor="arquivo_prova"
                      className="flex items-center gap-2"
                    >
                      <Upload className="size-4" />
                      Arquivo da Prova <span className="text-destructive">*</span>
                    </FieldLabel>
                    <Input
                      id="arquivo_prova"
                      type="file"
                      accept=".pdf,.doc,.docx"
                      onChange={(e) =>
                        setData('arquivo_prova', e.target.files[0])
                      }
                      required
                      disabled={processing}
                      className="cursor-pointer file:mr-3 file:cursor-pointer file:border-0 file:bg-muted file:px-3 file:py-1 file:text-xs file:font-medium file:text-foreground"
                    />
                    <p className="text-xs text-muted-foreground">
                      PDF, DOC ou DOCX • máximo 10 MB
                    </p>
                    {errors.arquivo_prova && (
                      <FieldError>{errors.arquivo_prova}</FieldError>
                    )}
                  </Field>

                  {/* Chave / Gabarito */}
                  <Field>
                    <FieldLabel
                      htmlFor="arquivo_chave"
                      className="flex items-center gap-2"
                    >
                      <FileText className="size-4" />
                      Chave / Gabarito{' '}
                      <span className="text-destructive">*</span>
                    </FieldLabel>
                    <Input
                      id="arquivo_chave"
                      type="file"
                      accept=".pdf,.doc,.docx"
                      onChange={(e) =>
                        setData('arquivo_chave', e.target.files[0])
                      }
                      required
                      disabled={processing}
                      className="cursor-pointer file:mr-3 file:cursor-pointer file:border-0 file:bg-muted file:px-3 file:py-1 file:text-xs file:font-medium file:text-foreground"
                    />
                    <p className="text-xs text-muted-foreground">
                      PDF, DOC ou DOCX • máximo 10 MB
                    </p>
                    {errors.arquivo_chave && (
                      <FieldError>{errors.arquivo_chave}</FieldError>
                    )}
                  </Field>

                  {/* Comentário */}
                  <Field>
                    <FieldLabel htmlFor="comentario">
                      Comentário{' '}
                      <span className="text-xs font-normal text-muted-foreground">
                        (opcional)
                      </span>
                    </FieldLabel>
                    <Textarea
                      id="comentario"
                      rows={3}
                      value={data.comentario}
                      onChange={(e) =>
                        setData('comentario', e.target.value)
                      }
                      placeholder="Observações sobre a prova..."
                      disabled={processing}
                      className="resize-none"
                    />
                    {errors.comentario && (
                      <FieldError>{errors.comentario}</FieldError>
                    )}
                  </Field>
                </FieldSet>
              </FieldGroup>

              {/* Botões */}
              <div className="mt-6 flex flex-col-reverse gap-2 border-t pt-4 sm:flex-row sm:justify-end">
                <Button
                  type="button"
                  variant="outline"
                  asChild
                  disabled={processing}
                  className="w-full hover:cursor-pointer sm:w-auto"
                >
                  <Link href="/dashboard/professor/provas">Cancelar</Link>
                </Button>
                <Button
                  type="submit"
                  disabled={processing || !data.turma_id}
                  className="w-full hover:cursor-pointer sm:w-auto"
                >
                  {processing ? (
                    <>
                      <Spinner />
                      A enviar...
                    </>
                  ) : (
                    'Submeter prova'
                  )}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </>
  );
}