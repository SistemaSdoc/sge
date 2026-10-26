import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
  ArrowLeft,
  Upload,
  FileText,
  X,
  CheckCircle2,
  BookOpen,
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

export default function Create({
  cursos = [],
  disciplinas = [],
  classes = [],
  anosLectivos = [],
  periodos = [],
  anoAtivoId,
}) {
  const { data, setData, post, processing, errors } = useForm({
    ano_letivo_id: anoAtivoId ?? '',
    curso_id: '',
    disciplina_id: '',
    classe_id: '',
    periodo: '',
    titulo: '',
    descricao: '',
    ficheiro: null,
  });

  const [nomeArquivo, setNomeArquivo] = useState('');
  const [aArrastar, setAArrastar] = useState(false);

  const submit = (e) => {
    e.preventDefault();
    post('/dashboard/planificacoes', {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Planificação publicada', {
          description: 'Os professores e alunos foram notificados.',
        });
      },
      onError: (errs) => {
        const primeiro = Object.values(errs)[0];
        toast.error('Erro ao publicar', { description: primeiro });
      },
    });
  };

  const handleFile = (f) => {
    if (!f) return;
    setData('ficheiro', f);
    setNomeArquivo(f.name);
  };

  const onInputChange = (e) => {
    handleFile(e.target.files?.[0] ?? null);
  };

  const onDrop = (e) => {
    e.preventDefault();
    setAArrastar(false);
    handleFile(e.dataTransfer.files?.[0] ?? null);
  };

  const limparArquivo = () => {
    setData('ficheiro', null);
    setNomeArquivo('');
  };

  return (
    <>
      <Head title="Nova planificação" />

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
                        href="/dashboard/planificacoes"
                        className="line-clamp-2 text-sm font-semibold text-primary"
                      >
                        Planificações
                      </Link>
                    </BreadcrumbLink>
                  </BreadcrumbItem>
                  <BreadcrumbSeparator />
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                      Nova planificação
                    </BreadcrumbPage>
                  </BreadcrumbItem>
                </BreadcrumbList>
              </Breadcrumb>
              <CardDescription>
                Selecione os dados e anexe o arquivo. Se já existir uma
                planificação com os mesmos parâmetros, será criada uma nova
                versão.
              </CardDescription>
            </div>
            <CardAction>
              <Button variant="outline" size="sm" asChild>
                <Link href="/dashboard/planificacoes">
                  <ArrowLeft className="mr-1.5 size-4" />
                  Voltar
                </Link>
              </Button>
            </CardAction>
          </CardHeader>
        </Card>

        <form onSubmit={submit} className="space-y-4">
          {/* Contexto académico */}
          <Card className="gap-0 pb-0">
            <CardHeader className="border-b">
              <CardTitle className="flex items-center gap-2 text-base">
                Contexto académico
              </CardTitle>
              <CardDescription>
                Ano letivo, curso, disciplina, classe e período.
              </CardDescription>
            </CardHeader>

            <CardContent className="p-4 sm:p-6">
              <FieldGroup>
                <FieldSet>
                  <Field>
                    <FieldLabel htmlFor="ano_letivo_id">Ano letivo *</FieldLabel>
                    <Select
                      value={data.ano_letivo_id}
                      onValueChange={(v) => setData('ano_letivo_id', v)}
                      disabled={processing}
                    >
                      <SelectTrigger id="ano_letivo_id" className="w-full">
                        <SelectValue placeholder="Selecione o ano letivo" />
                      </SelectTrigger>
                      <SelectContent>
                        {anosLectivos.map((a) => (
                          <SelectItem key={a.id} value={a.id}>
                            {a.nome}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    {errors.ano_letivo_id && (
                      <FieldError>{errors.ano_letivo_id}</FieldError>
                    )}
                  </Field>

                  <Field>
                    <FieldLabel htmlFor="curso_id">Curso *</FieldLabel>
                    <Select
                      value={data.curso_id}
                      onValueChange={(v) => setData('curso_id', v)}
                      disabled={processing}
                    >
                      <SelectTrigger id="curso_id" className="w-full">
                        <SelectValue placeholder="Selecione o curso" />
                      </SelectTrigger>
                      <SelectContent>
                        {cursos.map((c) => (
                          <SelectItem key={c.id} value={c.id}>
                            {c.nome}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    {errors.curso_id && (
                      <FieldError>{errors.curso_id}</FieldError>
                    )}
                  </Field>

                  <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <Field>
                      <FieldLabel htmlFor="disciplina_id">
                        Disciplina *
                      </FieldLabel>
                      <Select
                        value={data.disciplina_id}
                        onValueChange={(v) => setData('disciplina_id', v)}
                        disabled={processing}
                      >
                        <SelectTrigger id="disciplina_id" className="w-full">
                          <SelectValue placeholder="Selecione" />
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

                    <Field>
                      <FieldLabel htmlFor="classe_id">Classe *</FieldLabel>
                      <Select
                        value={data.classe_id}
                        onValueChange={(v) => setData('classe_id', v)}
                        disabled={processing}
                      >
                        <SelectTrigger id="classe_id" className="w-full">
                          <SelectValue placeholder="Selecione" />
                        </SelectTrigger>
                        <SelectContent>
                          {classes.map((c) => (
                            <SelectItem key={c.id} value={c.id}>
                              {c.nome}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      {errors.classe_id && (
                        <FieldError>{errors.classe_id}</FieldError>
                      )}
                    </Field>
                  </div>

                  <Field>
                    <FieldLabel htmlFor="periodo">Período *</FieldLabel>
                    <Select
                      value={data.periodo}
                      onValueChange={(v) => setData('periodo', v)}
                      disabled={processing}
                    >
                      <SelectTrigger id="periodo" className="w-full">
                        <SelectValue placeholder="Selecione o período" />
                      </SelectTrigger>
                      <SelectContent>
                        {periodos.map((p) => (
                          <SelectItem key={p} value={p}>
                            {p}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    {errors.periodo && (
                      <FieldError>{errors.periodo}</FieldError>
                    )}
                  </Field>
                </FieldSet>
              </FieldGroup>
            </CardContent>
          </Card>

          {/* Detalhes */}
          <Card className="gap-0 pb-0">
            <CardHeader className="border-b">
              <CardTitle className="flex items-center gap-2 text-base">
                Detalhes
              </CardTitle>
              <CardDescription>
                Título e descrição são opcionais.
              </CardDescription>
            </CardHeader>

            <CardContent className="p-4 sm:p-6">
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
                      placeholder="Ex: Planificação 1º Trimestre 2026/27"
                      disabled={processing}
                    />
                    {errors.titulo && (
                      <FieldError>{errors.titulo}</FieldError>
                    )}
                  </Field>

                  <Field>
                    <FieldLabel htmlFor="descricao">
                      Descrição{' '}
                      <span className="text-xs font-normal text-muted-foreground">
                        (opcional)
                      </span>
                    </FieldLabel>
                    <Textarea
                      id="descricao"
                      rows={3}
                      value={data.descricao}
                      onChange={(e) => setData('descricao', e.target.value)}
                      placeholder="Notas sobre esta planificação…"
                      disabled={processing}
                    />
                    {errors.descricao && (
                      <FieldError>{errors.descricao}</FieldError>
                    )}
                  </Field>
                </FieldSet>
              </FieldGroup>
            </CardContent>
          </Card>

          {/* Arquivo */}
          <Card className="gap-0 pb-0">
            <CardHeader className="border-b">
              <CardTitle className="flex items-center gap-2 text-base">
                Arquivo
              </CardTitle>
              <CardDescription>PDF, DOC ou DOCX — até 20 MB.</CardDescription>
            </CardHeader>

            <CardContent className="p-4 sm:p-6">
              {!nomeArquivo ? (
                <label
                  htmlFor="arquivo"
                  onDragOver={(e) => {
                    e.preventDefault();
                    setAArrastar(true);
                  }}
                  onDragLeave={() => setAArrastar(false)}
                  onDrop={onDrop}
                  className={`flex cursor-pointer flex-col items-center justify-center gap-3 border-2 border-dashed p-10 text-center transition-all ${
                    aArrastar
                      ? 'border-muted-foreground bg-muted/50'
                      : 'border-muted-foreground/25 hover:bg-muted/30'
                  }`}
                >
                  <div
                    className={`flex size-14 items-center justify-center transition-colors ${
                      aArrastar
                        ? 'bg-muted-foreground/15 text-foreground'
                        : 'bg-muted text-muted-foreground'
                    }`}
                  >
                    <Upload className="size-6" />
                  </div>
                  <div className="space-y-1">
                    <p className="text-sm font-medium">
                      {aArrastar
                        ? 'Solte o arquivo aqui'
                        : 'Arraste e solte, ou clique para escolher'}
                    </p>
                    <p className="text-xs text-muted-foreground">
                      PDF, DOC, DOCX • máximo 20 MB
                    </p>
                  </div>
                  <input
                    id="arquivo"
                    type="file"
                    accept=".pdf,.doc,.docx"
                    onChange={onInputChange}
                    className="hidden"
                  />
                </label>
              ) : (
                <div className="flex items-center gap-3 border bg-muted/20 p-4">
                  <div className="flex size-11 shrink-0 items-center justify-center bg-muted text-muted-foreground">
                  
                  </div>
                  <div className="min-w-0 flex-1">
                    <p
                      className="truncate text-sm font-medium"
                      title={nomeArquivo}
                    >
                      {nomeArquivo}
                    </p>
                    <p className="text-xs text-muted-foreground">
                      Pronto para publicar
                    </p>
                  </div>
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={limparArquivo}
                    aria-label="Remover arquivo"
                    className="hover:cursor-pointer"
                  >
                    <X className="size-4" />
                  </Button>
                </div>
              )}

              {errors.ficheiro && (
                <FieldError className="mt-2">{errors.ficheiro}</FieldError>
              )}
            </CardContent>
          </Card>

          {/* Botões */}
          <div className="flex flex-col-reverse gap-2 border bg-muted/30 p-4 sm:flex-row sm:justify-end">
            <Button
              type="button"
              variant="outline"
              asChild
              disabled={processing}
              className="w-full hover:cursor-pointer sm:w-auto"
            >
              <Link href="/dashboard/planificacoes">Cancelar</Link>
            </Button>
            <Button
              type="submit"
              disabled={processing}
              className="w-full hover:cursor-pointer sm:w-auto"
            >
              {processing ? (
                <>
                  <Spinner />
                  Publicando...
                </>
              ) : (
                <>
                  Publicar planificação
                </>
              )}
            </Button>
          </div>
        </form>
      </div>
    </>
  );
}