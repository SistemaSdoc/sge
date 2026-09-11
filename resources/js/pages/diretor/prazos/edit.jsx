import { Head, useForm, Link } from '@inertiajs/react';
import { ArrowLeft, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';

export default function Edit({ prazo, disciplinas, classes }) {
  const { data, setData, put, processing, errors } = useForm({
    titulo: prazo.titulo || '',
    tipo_prova: prazo.tipo_prova,
    disciplina_id: prazo.disciplina_id || '',
    classe_id: prazo.classe_id || '',
    data_inicio: prazo.data_inicio,
    data_limite: prazo.data_limite,
    periodo: prazo.periodo,
    observacoes: prazo.observacoes || '',
    permite_reenvio: prazo.permite_reenvio,
  });

  const submit = (e) => {
    e.preventDefault();
    put(`/dashboard/diretor/prazos/${prazo.id}`, {
      onSuccess: () => {
        // redireciona para os detalhes
      },
    });
  };

  const hasErrors = Object.keys(errors).length > 0;

  return (
    <>
      <Head title={`Editar: ${prazo.titulo}`} />

      <div className="w-full space-y-6 p-6">
        {/* Cabeçalho */}
        <div className="flex flex-wrap items-center justify-between gap-4">
          <h1 className="text-2xl font-bold text-foreground">Editar Prazo</h1>
          <Button variant="outline" asChild>
            <Link href={`/dashboard/diretor/prazos/${prazo.id}`}>
              <ArrowLeft className="mr-1.5 size-4" />
              Cancelar
            </Link>
          </Button>
        </div>

        {/* Formulário */}
        <Card className="shadow-sm border-border">
          <CardHeader>
            <CardTitle>Editar Prazo de Provas</CardTitle>
            <CardDescription>
              Atualize as informações do prazo.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={submit} className="space-y-6">
              {hasErrors && (
                <Alert variant="destructive">
                  <AlertDescription>
                    <ul className="list-disc pl-4 space-y-1 text-sm">
                      {Object.values(errors).map((msg, i) => (
                        <li key={i}>{msg}</li>
                      ))}
                    </ul>
                  </AlertDescription>
                </Alert>
              )}

              <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                {/* Título */}
                <div className="space-y-2 md:col-span-2">
                  <Label htmlFor="titulo">Título (opcional)</Label>
                  <Input
                    id="titulo"
                    value={data.titulo}
                    onChange={(e) => setData('titulo', e.target.value)}
                    placeholder="Ex: Exame Final 1º Trimestre"
                  />
                </div>

                {/* Tipo de Prova */}
                <div className="space-y-2">
                  <Label htmlFor="tipo_prova" className="after:content-['*'] after:ml-0.5 after:text-destructive">
                    Tipo de Prova
                  </Label>
                  <Select
                    value={data.tipo_prova}
                    onValueChange={(value) => setData('tipo_prova', value)}
                  >
                    <SelectTrigger id="tipo_prova">
                      <SelectValue placeholder="Selecione o tipo" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="teste">Teste</SelectItem>
                      <SelectItem value="exame">Exame</SelectItem>
                      <SelectItem value="ficha">Ficha</SelectItem>
                      <SelectItem value="recuperacao">Recuperação</SelectItem>
                    </SelectContent>
                  </Select>
                  {errors.tipo_prova && <p className="text-sm text-destructive">{errors.tipo_prova}</p>}
                </div>

                {/* Disciplina */}
                <div className="space-y-2">
                  <Label htmlFor="disciplina_id">Disciplina (opcional)</Label>
                  <Select
                    value={data.disciplina_id}
                    onValueChange={(value) => setData('disciplina_id', value)}
                  >
                    <SelectTrigger id="disciplina_id">
                      <SelectValue placeholder="Todas" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="">Todas</SelectItem>
                      {disciplinas?.map((d) => (
                        <SelectItem key={d.id} value={d.id}>
                          {d.nome}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                {/* Classe */}
                <div className="space-y-2">
                  <Label htmlFor="classe_id">Classe (opcional)</Label>
                  <Select
                    value={data.classe_id}
                    onValueChange={(value) => setData('classe_id', value)}
                  >
                    <SelectTrigger id="classe_id">
                      <SelectValue placeholder="Todas" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="">Todas</SelectItem>
                      {classes?.map((c) => (
                        <SelectItem key={c.id} value={c.id}>
                          {c.nome}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                {/* Data Início */}
                <div className="space-y-2">
                  <Label htmlFor="data_inicio" className="after:content-['*'] after:ml-0.5 after:text-destructive">
                    Data de Início
                  </Label>
                  <Input
                    id="data_inicio"
                    type="datetime-local"
                    value={data.data_inicio}
                    onChange={(e) => setData('data_inicio', e.target.value)}
                    required
                  />
                  {errors.data_inicio && <p className="text-sm text-destructive">{errors.data_inicio}</p>}
                </div>

                {/* Data Limite */}
                <div className="space-y-2">
                  <Label htmlFor="data_limite" className="after:content-['*'] after:ml-0.5 after:text-destructive">
                    Data Limite
                  </Label>
                  <Input
                    id="data_limite"
                    type="datetime-local"
                    value={data.data_limite}
                    onChange={(e) => setData('data_limite', e.target.value)}
                    required
                  />
                  {errors.data_limite && <p className="text-sm text-destructive">{errors.data_limite}</p>}
                </div>

                {/* Período */}
                <div className="space-y-2">
                  <Label htmlFor="periodo" className="after:content-['*'] after:ml-0.5 after:text-destructive">
                    Período
                  </Label>
                  <Input
                    id="periodo"
                    value={data.periodo}
                    onChange={(e) => setData('periodo', e.target.value)}
                    placeholder="Ex: 1º Trimestre"
                    required
                  />
                  {errors.periodo && <p className="text-sm text-destructive">{errors.periodo}</p>}
                </div>

                {/* Observações */}
                <div className="space-y-2 md:col-span-2">
                  <Label htmlFor="observacoes">Observações</Label>
                  <Textarea
                    id="observacoes"
                    rows={3}
                    value={data.observacoes}
                    onChange={(e) => setData('observacoes', e.target.value)}
                  />
                </div>

                {/* Permite reenvio (Switch) */}
                <div className="flex items-center space-x-2 md:col-span-2">
                  <Switch
                    id="permiteReenvio"
                    checked={data.permite_reenvio}
                    onCheckedChange={(checked) => setData('permite_reenvio', checked)}
                  />
                  <Label htmlFor="permiteReenvio" className="text-sm font-normal">
                    Permite reenvio
                  </Label>
                </div>
              </div>

              {/* Botões */}
              <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:space-x-2">
                <Button variant="outline" asChild className="w-full sm:w-auto">
                  <Link href={`/dashboard/diretor/prazos/${prazo.id}`}>Cancelar</Link>
                </Button>
                <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                  {processing ? (
                    <>
                      <Loader2 className="mr-1.5 size-4 animate-spin" />
                      Salvando...
                    </>
                  ) : (
                    'Salvar Alterações'
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