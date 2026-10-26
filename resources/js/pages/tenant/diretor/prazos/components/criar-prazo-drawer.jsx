import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
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
import MultipleSelector from '@/components/ui/multiselect';
import { Spinner } from '@/components/spinner';
import { toast } from 'sonner';

export default function CriarPrazoDrawer({
  disciplinas,
  classes,
  periodos,
  onSuccess,
  onCancel,
}) {
  const { data, setData, post, processing, errors } = useForm({
    titulo: '',
    tipo_prova: 'Prova-Trimestral',
    disciplina_ids: [],
    classe_ids: [],
    periodo: '',
    data_inicio: '',
    data_limite: '',
    observacoes: '',
    permite_reenvio: true,
  });

  const disciplinaOptions =
    disciplinas?.map((d) => ({ value: d.id, label: d.nome })) || [];
  const classeOptions =
    classes?.map((c) => ({ value: c.id, label: c.nome })) || [];
  const periodoOptions = periodos ?? [];

  const submit = (e) => {
    e.preventDefault();

    if (data.disciplina_ids.length === 0) {
      toast.error('Selecione pelo menos uma disciplina.');
      return;
    }
    if (data.classe_ids.length === 0) {
      toast.error('Selecione pelo menos uma classe.');
      return;
    }
    if (!data.periodo) {
      toast.error('Selecione um período.');
      return;
    }

    const payload = {
      ...data,
      disciplina_ids: data.disciplina_ids.map((item) => item.value),
      classe_ids: data.classe_ids.map((item) => item.value),
    };

    onSuccess?.();

    post('/dashboard/diretor/prazos', payload, {
      preserveState: false,
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Prazo(s) criado(s) com sucesso!');
      },
      onError: (errs) => {
        toast.error('Erro ao criar prazo. Verifique os campos.');
        console.error(errs);
      },
    });
  };

  return (
    <form onSubmit={submit} className="space-y-4">
      <FieldGroup>
        <FieldSet>
          {/* Título */}
          <Field>
            <FieldLabel htmlFor="titulo">Título (opcional)</FieldLabel>
            <Input
              id="titulo"
              type="text"
              placeholder="Ex: Exame Final 1º Trimestre"
              value={data.titulo}
              onChange={(e) => setData('titulo', e.target.value)}
              disabled={processing}
            />
            {errors.titulo && <FieldError>{errors.titulo}</FieldError>}
          </Field>

          {/* Tipo de Prova */}
          <Field>
            <FieldLabel htmlFor="tipo_prova">Tipo de Prova *</FieldLabel>
            <Select
              value={data.tipo_prova}
              onValueChange={(value) => setData('tipo_prova', value)}
              disabled={processing}
            >
              <SelectTrigger id="tipo_prova" className="w-full">
                <SelectValue placeholder="Selecione o tipo" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Prova-Trimestral">
                  Prova Trimestral
                </SelectItem>
                <SelectItem value="Recurso">Recurso</SelectItem>
                <SelectItem value="Exame-especial">Exame Especial</SelectItem>
              </SelectContent>
            </Select>
            {errors.tipo_prova && (
              <FieldError>{errors.tipo_prova}</FieldError>
            )}
          </Field>

          {/* Disciplinas */}
          <Field>
            <FieldLabel>Disciplinas *</FieldLabel>
            <MultipleSelector
              value={data.disciplina_ids}
              onChange={(newSelected) => setData('disciplina_ids', newSelected)}
              options={disciplinaOptions}
              placeholder="Selecione pelo menos uma disciplina..."
              emptyIndicator="Nenhuma disciplina encontrada"
              className="w-full"
              disabled={processing}
            />
            {errors.disciplina_ids && (
              <FieldError>{errors.disciplina_ids}</FieldError>
            )}
          </Field>

          {/* Classes */}
          <Field>
            <FieldLabel>Classes *</FieldLabel>
            <MultipleSelector
              value={data.classe_ids}
              onChange={(newSelected) => setData('classe_ids', newSelected)}
              options={classeOptions}
              placeholder="Selecione pelo menos uma classe..."
              emptyIndicator="Nenhuma classe encontrada"
              className="w-full"
              disabled={processing}
            />
            {errors.classe_ids && (
              <FieldError>{errors.classe_ids}</FieldError>
            )}
          </Field>

          {/* Período */}
          <Field>
            <FieldLabel htmlFor="periodo">Período *</FieldLabel>
            <Select
              value={data.periodo}
              onValueChange={(value) => setData('periodo', value)}
              disabled={processing}
            >
              <SelectTrigger id="periodo" className="w-full">
                <SelectValue placeholder="Selecione o período" />
              </SelectTrigger>
              <SelectContent>
                {periodoOptions.map((p) => (
                  <SelectItem key={p.value} value={p.value}>
                    {p.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {errors.periodo && <FieldError>{errors.periodo}</FieldError>}
          </Field>

          {/* Datas */}
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <Field>
              <FieldLabel htmlFor="data_inicio">Data de Início *</FieldLabel>
              <Input
                id="data_inicio"
                type="datetime-local"
                value={data.data_inicio}
                onChange={(e) => setData('data_inicio', e.target.value)}
                disabled={processing}
                required
              />
              {errors.data_inicio && (
                <FieldError>{errors.data_inicio}</FieldError>
              )}
            </Field>

            <Field>
              <FieldLabel htmlFor="data_limite">Data Limite *</FieldLabel>
              <Input
                id="data_limite"
                type="datetime-local"
                value={data.data_limite}
                onChange={(e) => setData('data_limite', e.target.value)}
                disabled={processing}
                required
              />
              {errors.data_limite && (
                <FieldError>{errors.data_limite}</FieldError>
              )}
            </Field>
          </div>

          {/* Observações */}
          <Field>
            <FieldLabel htmlFor="observacoes">Observações</FieldLabel>
            <Textarea
              id="observacoes"
              rows={3}
              value={data.observacoes}
              onChange={(e) => setData('observacoes', e.target.value)}
              disabled={processing}
            />
            {errors.observacoes && (
              <FieldError>{errors.observacoes}</FieldError>
            )}
          </Field>

          {/* Permite reenvio */}
          <Field>
            <div className="flex items-center gap-2">
              <Switch
                id="permiteReenvio"
                checked={data.permite_reenvio}
                onCheckedChange={(checked) =>
                  setData('permite_reenvio', checked)
                }
                disabled={processing}
              />
              <FieldLabel
                htmlFor="permiteReenvio"
                className="text-sm font-normal"
              >
                Permite reenvio
              </FieldLabel>
            </div>
          </Field>
        </FieldSet>
      </FieldGroup>

      {/* Botões */}
      <div className="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
        <Button
          type="button"
          variant="outline"
          onClick={onCancel}
          disabled={processing}
          className="w-full hover:cursor-pointer sm:w-auto"
        >
          Cancelar
        </Button>
        <Button
          type="submit"
          disabled={processing}
          className="w-full hover:cursor-pointer sm:w-auto"
        >
          {processing ? (
            <>
              <Spinner />
              Criando ...
            </>
          ) : (
            'Criar Prazo'
          )}
        </Button>
      </div>
    </form>
  );
}