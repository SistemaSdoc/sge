import { useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import type { FormEvent } from 'react';
import { gerar } from '@/actions/App/Http/Controllers/Tenant/QuadroHonraController';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import type { AnoLectivoOption, CursoOption } from '@/types/quadro-honra';

type FormularioQuadroHonra = {
  tipo: 'geral' | 'curso';
  ano_lectivo_id: string;
  curso_tutelado_id: string;
  media_minima: string;
  limite_alunos: string;
};

type FormGerarQuadroHonraProps = {
  anosLectivos: AnoLectivoOption[];
  anoAtivoId: string | null;
  cursos: CursoOption[];
  podeGerarGeral: boolean;
  podeGerarCurso: boolean;
};

/** Permite iniciar uma geração com os critérios validados pelo servidor. */
export function FormGerarQuadroHonra({
  anosLectivos,
  anoAtivoId,
  cursos,
  podeGerarGeral,
  podeGerarCurso,
}: FormGerarQuadroHonraProps) {
  const tipoInicial = podeGerarGeral ? 'geral' : 'curso';
  const form = useForm<FormularioQuadroHonra>({
    tipo: tipoInicial,
    ano_lectivo_id: anoAtivoId ?? '',
    curso_tutelado_id: '',
    media_minima: '14',
    limite_alunos: '10',
  });

  const submeter = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    form.post(gerar().url, { preserveScroll: true });
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Gerar Quadro de Honra</CardTitle>
        <CardDescription>
          A média mínima de elegibilidade é 14 valores. Alunos que foram a
          recurso são excluídos.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form
          onSubmit={submeter}
          className="grid gap-4 md:grid-cols-2 xl:grid-cols-5"
        >
          {(podeGerarGeral || podeGerarCurso) && (
            <div className="space-y-2">
              <Label htmlFor="tipo-quadro">Tipo de quadro</Label>
              <Select
                value={form.data.tipo}
                onValueChange={(tipo: string) => {
                  form.setData((anterior) => ({
                    ...anterior,
                    tipo: tipo as FormularioQuadroHonra['tipo'],
                    curso_tutelado_id:
                      tipo === 'geral' ? '' : anterior.curso_tutelado_id,
                  }));
                }}
                disabled={!podeGerarGeral || !podeGerarCurso}
              >
                <SelectTrigger id="tipo-quadro" className="w-full">
                  <SelectValue placeholder="Selecione o quadro" />
                </SelectTrigger>
                <SelectContent>
                  {podeGerarGeral && (
                    <SelectItem value="geral">Geral</SelectItem>
                  )}
                  {podeGerarCurso && (
                    <SelectItem value="curso">Por curso</SelectItem>
                  )}
                </SelectContent>
              </Select>
              {form.errors.tipo && (
                <p className="text-xs text-destructive">{form.errors.tipo}</p>
              )}
            </div>
          )}

          <div className="space-y-2">
            <Label htmlFor="ano-lectivo">Ano lectivo</Label>
            <Select
              value={form.data.ano_lectivo_id}
              onValueChange={(ano) => form.setData('ano_lectivo_id', ano)}
            >
              <SelectTrigger id="ano-lectivo" className="w-full">
                <SelectValue placeholder="Selecione o ano lectivo" />
              </SelectTrigger>
              <SelectContent>
                {anosLectivos.map((ano) => (
                  <SelectItem key={ano.id} value={ano.id}>
                    {ano.nome}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {form.errors.ano_lectivo_id && (
              <p className="text-xs text-destructive">
                {form.errors.ano_lectivo_id}
              </p>
            )}
          </div>

          {form.data.tipo === 'curso' && (
            <div className="space-y-2">
              <Label htmlFor="curso-tutelado">Curso</Label>
              <Select
                value={form.data.curso_tutelado_id}
                onValueChange={(curso) =>
                  form.setData('curso_tutelado_id', curso)
                }
              >
                <SelectTrigger id="curso-tutelado" className="w-full">
                  <SelectValue placeholder="Selecione o curso" />
                </SelectTrigger>
                <SelectContent>
                  {cursos.map((curso) => (
                    <SelectItem key={curso.id} value={curso.id}>
                      {curso.nome}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {form.errors.curso_tutelado_id && (
                <p className="text-xs text-destructive">
                  {form.errors.curso_tutelado_id}
                </p>
              )}
            </div>
          )}

          <div className="space-y-2">
            <Label htmlFor="media-minima">Média mínima</Label>
            <Input
              id="media-minima"
              type="number"
              min={14}
              max={20}
              step="0.1"
              value={form.data.media_minima}
              onChange={(event) =>
                form.setData('media_minima', event.target.value)
              }
              aria-invalid={Boolean(form.errors.media_minima)}
            />
            {form.errors.media_minima && (
              <p className="text-xs text-destructive">
                {form.errors.media_minima}
              </p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="limite-alunos">Limite de alunos</Label>
            <Input
              id="limite-alunos"
              type="number"
              min={1}
              max={65535}
              step={1}
              value={form.data.limite_alunos}
              onChange={(event) =>
                form.setData('limite_alunos', event.target.value)
              }
              aria-invalid={Boolean(form.errors.limite_alunos)}
            />
            {form.errors.limite_alunos && (
              <p className="text-xs text-destructive">
                {form.errors.limite_alunos}
              </p>
            )}
          </div>

          <div className="md:col-span-2 xl:col-span-5">
            <Button
              type="submit"
              disabled={
                form.processing ||
                anosLectivos.length === 0 ||
                (form.data.tipo === 'curso' && cursos.length === 0)
              }
            >
              {form.processing && (
                <LoaderCircle className="size-4 animate-spin" />
              )}
              {form.processing ? 'A gerar…' : 'Gerar quadro'}
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}
