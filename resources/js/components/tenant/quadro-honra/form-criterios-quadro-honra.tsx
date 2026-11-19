import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { atualizarCriterios } from '@/actions/App/Http/Controllers/Tenant/QuadroHonraController';
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

type FormCriterios = {
  media_minima: string;
  limite_alunos: string;
};

type FormCriteriosQuadroHonraProps = {
  criterios: {
    media_minima: number;
    limite_alunos: number;
  };
};

/** Permite à Direcção ajustar a elegibilidade e actualiza os quadros existentes. */
export function FormCriteriosQuadroHonra({
  criterios,
}: FormCriteriosQuadroHonraProps) {
  const form = useForm<FormCriterios>({
    media_minima: String(criterios.media_minima),
    limite_alunos: String(criterios.limite_alunos),
  });

  const submeter = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    form.patch(atualizarCriterios().url, {
      preserveScroll: true,
    });
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Critérios do Quadro de Honra</CardTitle>
        <CardDescription>
          Ao guardar, os quadros do último trimestre gerado são recalculados
          imediatamente.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={submeter} className="grid gap-4 sm:grid-cols-3">
          <div className="space-y-2">
            <Label htmlFor="quadro-honra-media">Média mínima</Label>
            <Input
              id="quadro-honra-media"
              name="media_minima"
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
              <p className="text-sm text-destructive">
                {form.errors.media_minima}
              </p>
            )}
          </div>
          <div className="space-y-2">
            <Label htmlFor="quadro-honra-limite">Limite de alunos</Label>
            <Input
              id="quadro-honra-limite"
              name="limite_alunos"
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
              <p className="text-sm text-destructive">
                {form.errors.limite_alunos}
              </p>
            )}
          </div>
          <div className="flex items-end">
            <Button type="submit" disabled={form.processing}>
              {form.processing ? 'A guardar…' : 'Guardar critérios'}
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}
