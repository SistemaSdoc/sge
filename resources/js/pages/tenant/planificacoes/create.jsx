import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
  ArrowLeft,
  Upload,
  FileText,
  X,
  Loader2,
  CheckCircle2,
  Sparkles,
  CalendarDays,
  BookOpen,
  GraduationCap,
  Clock,
  Info,
} from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription,
} from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';

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

  const [nomeFicheiro, setNomeFicheiro] = useState('');
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
        console.error('=== ERROS ===', errs);
        const primeiro = Object.values(errs)[0];
        toast.error('Erro ao publicar', { description: primeiro });
      },
    });
  };

  const handleFile = (f) => {
    if (!f) return;
    setData('ficheiro', f);
    setNomeFicheiro(f.name);
  };

  const onInputChange = (e) => {
    handleFile(e.target.files?.[0] ?? null);
  };

  const onDrop = (e) => {
    e.preventDefault();
    setAArrastar(false);
    handleFile(e.dataTransfer.files?.[0] ?? null);
  };

  const limparFicheiro = () => {
    setData('ficheiro', null);
    setNomeFicheiro('');
  };

  const temErro = Object.keys(errors).length > 0;

  return (
    <>
      <Head title="Nova planificação" />

      <div className="mx-auto w-full max-w-3xl space-y-6 p-4 sm:p-6">
        <Button variant="ghost" size="sm" asChild className="-ml-2">
          <Link href="/dashboard/planificacoes">
            <ArrowLeft className="mr-1 size-4" />
            Voltar às planificações
          </Link>
        </Button>

        {/* Hero */}
        <div className="relative overflow-hidden border bg-gradient-to-br from-primary/5 via-background to-background p-6 sm:p-8">
          <div
            className="pointer-events-none absolute -top-20 -right-20 size-64 bg-primary/10 blur-3xl"
            aria-hidden
          />
          <div className="relative flex items-start gap-4">
            <div className="flex size-14 shrink-0 items-center justify-center bg-primary/15 ring-1 ring-primary/20">
              <Sparkles className="size-7 text-primary" />
            </div>
            <div className="space-y-1">
              <p className="text-xs font-medium tracking-wider text-muted-foreground uppercase">
                Publicar
              </p>
              <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
                Nova planificação
              </h1>
              <p className="text-sm text-muted-foreground">
                Selecione os dados e anexe o ficheiro. Se já existir uma
                planificação com os mesmos parâmetros, será criada uma nova
                versão.
              </p>
            </div>
          </div>
        </div>

        <form onSubmit={submit} className="space-y-6">
          {/* Contexto académico */}
          <Card>
            <CardHeader className="border-b">
              <div className="flex items-center gap-3">
                <div className="flex size-10 items-center justify-center bg-muted">
                  <CalendarDays className="size-5 text-muted-foreground" />
                </div>
                <div>
                  <CardTitle className="text-base">
                    Contexto académico
                  </CardTitle>
                  <CardDescription>
                    Ano letivo, curso, disciplina, classe e período.
                  </CardDescription>
                </div>
              </div>
            </CardHeader>

            <CardContent className="space-y-5 pt-6">
              {/* Ano letivo */}
              <div className="space-y-1.5">
                <Label htmlFor="ano_letivo_id">
                  Ano letivo <span className="text-destructive">*</span>
                </Label>
                <Select
                  value={data.ano_letivo_id}
                  onValueChange={(v) => setData('ano_letivo_id', v)}
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
                  <p className="text-sm text-destructive">
                    {errors.ano_letivo_id}
                  </p>
                )}
              </div>

              {/* Curso */}
              <div className="space-y-1.5">
                <Label htmlFor="curso_id">
                  Curso <span className="text-destructive">*</span>
                </Label>
                <Select
                  value={data.curso_id}
                  onValueChange={(v) => setData('curso_id', v)}
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
                  <p className="text-sm text-destructive">{errors.curso_id}</p>
                )}
              </div>

              {/* Disciplina + Classe */}
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div className="space-y-1.5">
                  <Label htmlFor="disciplina_id">
                    Disciplina <span className="text-destructive">*</span>
                  </Label>
                  <Select
                    value={data.disciplina_id}
                    onValueChange={(v) => setData('disciplina_id', v)}
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
                    <p className="text-sm text-destructive">
                      {errors.disciplina_id}
                    </p>
                  )}
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="classe_id">
                    Classe <span className="text-destructive">*</span>
                  </Label>
                  <Select
                    value={data.classe_id}
                    onValueChange={(v) => setData('classe_id', v)}
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
                    <p className="text-sm text-destructive">
                      {errors.classe_id}
                    </p>
                  )}
                </div>
              </div>

              {/* Período */}
              <div className="space-y-1.5">
                <Label htmlFor="periodo">
                  Período <span className="text-destructive">*</span>
                </Label>
                <Select
                  value={data.periodo}
                  onValueChange={(v) => setData('periodo', v)}
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
                  <p className="text-sm text-destructive">{errors.periodo}</p>
                )}
              </div>
            </CardContent>
          </Card>

          {/* Detalhes */}
          <Card>
            <CardHeader className="border-b">
              <div className="flex items-center gap-3">
                <div className="flex size-10 items-center justify-center bg-muted">
                  <BookOpen className="size-5 text-muted-foreground" />
                </div>
                <div>
                  <CardTitle className="text-base">Detalhes</CardTitle>
                  <CardDescription>
                    Título e descrição são opcionais.
                  </CardDescription>
                </div>
              </div>
            </CardHeader>

            <CardContent className="space-y-5 pt-6">
              <div className="space-y-1.5">
                <Label htmlFor="titulo">
                  Título{' '}
                  <span className="text-xs font-normal text-muted-foreground">
                    (opcional)
                  </span>
                </Label>
                <Input
                  id="titulo"
                  value={data.titulo}
                  onChange={(e) => setData('titulo', e.target.value)}
                  placeholder="Ex: Planificação 1º Trimestre 2026/27"
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="descricao">
                  Descrição{' '}
                  <span className="text-xs font-normal text-muted-foreground">
                    (opcional)
                  </span>
                </Label>
                <Textarea
                  id="descricao"
                  rows={3}
                  value={data.descricao}
                  onChange={(e) => setData('descricao', e.target.value)}
                  placeholder="Notas sobre esta planificação…"
                />
              </div>
            </CardContent>
          </Card>

          {/* Ficheiro */}
          <Card>
            <CardHeader className="border-b">
              <div className="flex items-center gap-3">
                <div className="flex size-10 items-center justify-center bg-muted">
                  <FileText className="size-5 text-muted-foreground" />
                </div>
                <div>
                  <CardTitle className="text-base">Ficheiro</CardTitle>
                  <CardDescription>
                    PDF, DOC ou DOCX — até 20 MB.
                  </CardDescription>
                </div>
              </div>
            </CardHeader>

            <CardContent className="pt-6">
              {!nomeFicheiro ? (
                <label
                  htmlFor="ficheiro"
                  onDragOver={(e) => {
                    e.preventDefault();
                    setAArrastar(true);
                  }}
                  onDragLeave={() => setAArrastar(false)}
                  onDrop={onDrop}
                  className={`flex cursor-pointer flex-col items-center justify-center gap-3 border-2 border-dashed p-10 text-center transition-all ${
                    aArrastar
                      ? 'scale-[1.01] border-primary bg-primary/5'
                      : 'border-muted-foreground/25 hover:border-primary/50 hover:bg-muted/30'
                  }`}
                >
                  <div
                    className={`flex size-14 items-center justify-center transition-colors ${
                      aArrastar
                        ? 'bg-primary/15 text-primary'
                        : 'bg-muted text-muted-foreground'
                    }`}
                  >
                    <Upload className="size-6" />
                  </div>
                  <div className="space-y-1">
                    <p className="text-sm font-medium">
                      {aArrastar
                        ? 'Solte o ficheiro aqui'
                        : 'Arraste e solte, ou clique para escolher'}
                    </p>
                    <p className="text-xs text-muted-foreground">
                      PDF, DOC, DOCX • máximo 20 MB
                    </p>
                  </div>
                  <input
                    id="ficheiro"
                    type="file"
                    accept=".pdf,.doc,.docx"
                    onChange={onInputChange}
                    className="hidden"
                  />
                </label>
              ) : (
                <div className="flex items-center gap-3 border bg-muted/20 p-4">
                  <div className="flex size-11 shrink-0 items-center justify-center bg-primary/15 text-primary">
                    <FileText className="size-5" />
                  </div>
                  <div className="min-w-0 flex-1">
                    <p
                      className="truncate text-sm font-medium"
                      title={nomeFicheiro}
                    >
                      {nomeFicheiro}
                    </p>
                    <p className="text-xs text-muted-foreground">
                      Pronto para publicar
                    </p>
                  </div>
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={limparFicheiro}
                    aria-label="Remover ficheiro"
                  >
                    <X className="size-4" />
                  </Button>
                </div>
              )}

              {errors.ficheiro && (
                <p className="mt-2 text-sm text-destructive">
                  {errors.ficheiro}
                </p>
              )}
            </CardContent>
          </Card>

          {/* Footer sticky-like com resumo + ações */}
          <div className="flex flex-col-reverse gap-2 border bg-muted/30 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center gap-2 text-xs text-muted-foreground">
              <Info className="size-3.5 shrink-0" />
              <span>
                Campos com <span className="text-destructive">*</span> são
                obrigatórios.
              </span>
            </div>
            <div className="flex flex-col-reverse gap-2 sm:flex-row">
              <Button type="button" variant="outline" asChild>
                <Link href="/dashboard/planificacoes">Cancelar</Link>
              </Button>
              <Button type="submit" disabled={processing} className="shadow-sm">
                {processing ? (
                  <>
                    <Loader2 className="mr-1.5 size-4 animate-spin" />A
                    publicar…
                  </>
                ) : (
                  <>
                    <CheckCircle2 className="mr-1.5 size-4" />
                    Publicar planificação
                  </>
                )}
              </Button>
            </div>
          </div>
        </form>
      </div>
    </>
  );
}
