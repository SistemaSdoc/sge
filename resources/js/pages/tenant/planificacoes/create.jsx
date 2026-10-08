import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
  ArrowLeft,
  Upload,
  FileText,
  X,
  Loader2,
  CheckCircle2,
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

export default function Create({
  disciplinas = [],
  classes = [],
  anosLectivos = [],
  periodos = [],
  anoAtivoId,
}) {
  const { data, setData, post, processing, errors } = useForm({
    ano_letivo_id: anoAtivoId ?? '',
    disciplina_id: '',
    classe_id: '',
    periodo: '',
    titulo: '',
    descricao: '',
    ficheiro: null,
  });

  const [nomeFicheiro, setNomeFicheiro] = useState('');

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

  const handleFile = (e) => {
    const f = e.target.files?.[0] ?? null;
    setData('ficheiro', f);
    setNomeFicheiro(f?.name ?? '');
  };

  const limparFicheiro = () => {
    setData('ficheiro', null);
    setNomeFicheiro('');
  };

  return (
    <>
      <Head title="Nova planificação" />

      <div className="mx-auto w-full max-w-3xl p-4 sm:p-6">
        <Button variant="ghost" size="sm" asChild className="mb-4">
          <Link href="/dashboard/planificacoes">
            <ArrowLeft className="mr-1 size-4" />
            Voltar
          </Link>
        </Button>

        <Card>
          <CardHeader className="border-b">
            <CardTitle className="text-xl">Publicar planificação</CardTitle>
            <CardDescription>
              Selecione o ano letivo, disciplina, classe e período. Se já existir,
              será criada uma nova versão.
            </CardDescription>
          </CardHeader>

          <form onSubmit={submit}>
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
                  <p className="text-sm text-destructive">{errors.ano_letivo_id}</p>
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
                    <p className="text-sm text-destructive">{errors.disciplina_id}</p>
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
                    <p className="text-sm text-destructive">{errors.classe_id}</p>
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

              {/* Título */}
              <div className="space-y-1.5">
                <Label htmlFor="titulo">Título (opcional)</Label>
                <Input
                  id="titulo"
                  value={data.titulo}
                  onChange={(e) => setData('titulo', e.target.value)}
                  placeholder="Ex: Planificação 1º Trimestre 2026/27"
                />
              </div>

              {/* Descrição */}
              <div className="space-y-1.5">
                <Label htmlFor="descricao">Descrição (opcional)</Label>
                <Textarea
                  id="descricao"
                  rows={3}
                  value={data.descricao}
                  onChange={(e) => setData('descricao', e.target.value)}
                  placeholder="Notas sobre esta planificação..."
                />
              </div>

              {/* Ficheiro */}
              <div className="space-y-1.5">
                <Label htmlFor="ficheiro">
                  Ficheiro <span className="text-destructive">*</span>
                </Label>

                {!nomeFicheiro ? (
                  <label
                    htmlFor="ficheiro"
                    className="flex cursor-pointer items-center justify-center gap-2 rounded-lg border-2 border-dashed border-muted-foreground/25 p-8 text-sm text-muted-foreground hover:border-primary/50 hover:bg-muted/30 transition-colors"
                  >
                    <Upload className="size-5" />
                    <span>Clique para escolher ficheiro (PDF, DOC, DOCX • até 20MB)</span>
                    <input
                      id="ficheiro"
                      type="file"
                      accept=".pdf,.doc,.docx"
                      onChange={handleFile}
                      className="hidden"
                    />
                  </label>
                ) : (
                  <div className="flex items-center justify-between rounded-lg border p-3">
                    <div className="flex min-w-0 items-center gap-2">
                      <FileText className="size-5 shrink-0 text-primary" />
                      <span className="truncate text-sm font-medium">
                        {nomeFicheiro}
                      </span>
                    </div>
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      onClick={limparFicheiro}
                    >
                      <X className="size-4" />
                    </Button>
                  </div>
                )}

                {errors.ficheiro && (
                  <p className="text-sm text-destructive">{errors.ficheiro}</p>
                )}
              </div>
            </CardContent>

            <div className="flex flex-col-reverse gap-2 border-t p-4 sm:flex-row sm:justify-end">
              <Button type="button" variant="outline" asChild>
                <Link href="/dashboard/planificacoes">Cancelar</Link>
              </Button>
              <Button type="submit" disabled={processing}>
                {processing ? (
                  <>
                    <Loader2 className="mr-1.5 size-4 animate-spin" />
                    A publicar...
                  </>
                ) : (
                  <>
                    <CheckCircle2 className="mr-1.5 size-4" />
                    Publicar
                  </>
                )}
              </Button>
            </div>
          </form>
        </Card>
      </div>
    </>
  );
}