import { useRef } from 'react';
import { useForm } from '@inertiajs/react';
import { FileUp, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { store } from '@/actions/App/Http/Controllers/Central/CalendarioAnualController';

export default function CalendarioForm({ onSuccess }) {
  const form = useForm({ ano: '', ficheiro: null });
  const { processing } = form;
  const uploadInput = useRef(null);

  const submit = (event) => {
    event.preventDefault();

    form.post(store().url, {
      forceFormData: true,
      onSuccess: () => {
        form.reset();
        form.clearErrors();

        if (uploadInput.current) {
          uploadInput.current.value = '';
        }

        onSuccess?.();
      },
    });
  };

  return (
    <form onSubmit={submit}>
      <FieldGroup>
        <FieldSet>
          <div className="grid gap-4">
            <Field>
              <FieldLabel htmlFor="ano">Ano lectivo</FieldLabel>
              <Input
                id="ano"
                name="ano"
                type="text"
                inputMode="numeric"
                pattern="[0-9]{4}/[0-9]{4}"
                placeholder="2026/2027"
                value={form.data.ano}
                onChange={(event) => form.setData('ano', event.target.value)}
                required
              />
              {form.errors.ano && <FieldError>{form.errors.ano}</FieldError>}
            </Field>

            <Field>
              <FieldLabel htmlFor="ficheiro">Ficheiro PDF ou DOCX</FieldLabel>
              <Input
                ref={uploadInput}
                id="ficheiro"
                name="ficheiro"
                type="file"
                accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                className="h-10 bg-muted/30 px-2 file:mr-3 file:h-7 file:bg-primary file:px-3 file:text-primary-foreground hover:file:bg-primary/90"
                onChange={(event) => {
                  form.clearErrors('ficheiro');
                  form.setData('ficheiro', event.target.files?.[0] ?? null);
                }}
                required
              />
              <p className="text-xs text-muted-foreground">
                PDF ou DOCX, máximo 100 MB.
              </p>
              {form.errors.ficheiro && (
                <FieldError>{form.errors.ficheiro}</FieldError>
              )}
            </Field>

            <Button type="submit" disabled={processing}>
              {processing ? (
                <>
                  <Loader2 className="animate-spin" /> A adicionar...
                </>
              ) : (
                'Adicionar'
              )}
            </Button>
          </div>
        </FieldSet>
      </FieldGroup>
    </form>
  );
}
