import { useForm } from '@inertiajs/react';
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
import MultipleSelector from '@/components/ui/multiselect';
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
    periodo: '',          // ← agora string singular
    data_inicio: '',
    data_limite: '',
    observacoes: '',
    permite_reenvio: true,
  });

  const disciplinaOptions = disciplinas?.map((d) => ({ value: d.id, label: d.nome })) || [];
  const classeOptions = classes?.map((c) => ({ value: c.id, label: c.nome })) || [];
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
      {/* Título */}
      <div className="space-y-1.5">
        <Label htmlFor="titulo">Título (opcional)</Label>
        <Input
          id="titulo"
          type="text"
          placeholder="Ex: Exame Final 1º Trimestre"
          value={data.titulo}
          onChange={(e) => setData('titulo', e.target.value)}
        />
      </div>

      {/* Tipo de Prova */}
      <div className="space-y-1.5">
        <Label htmlFor="tipo_prova">
          Tipo de Prova <span className="text-destructive"></span>
        </Label>
        <Select
          value={data.tipo_prova}
          onValueChange={(value) => setData('tipo_prova', value)}
        >
          <SelectTrigger id="tipo_prova" className="w-full">
            <SelectValue placeholder="Selecione o tipo" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="Prova-Trimestral">Prova Trimestral</SelectItem>
            <SelectItem value="Recurso">Recurso</SelectItem>
            <SelectItem value="Exame-especial">Exame Especial</SelectItem>
          </SelectContent>
        </Select>
        {errors.tipo_prova && (
          <p className="text-sm text-destructive">{errors.tipo_prova}</p>
        )}
      </div>

      {/* Disciplinas */}
      <div className="space-y-1.5">
        <Label>
          Disciplinas <span className="text-destructive"></span>
        </Label>
        <MultipleSelector
          value={data.disciplina_ids}
          onChange={(newSelected) => setData('disciplina_ids', newSelected)}
          options={disciplinaOptions}
          placeholder="Selecione pelo menos uma disciplina..."
          emptyIndicator="Nenhuma disciplina encontrada"
          className="w-full"
        />
        {data.disciplina_ids.length === 0 && (
          <p className="text-xs text-muted-foreground">
            Obrigatório — selecione pelo menos uma disciplina
          </p>
        )}
        {errors.disciplina_ids && (
          <p className="text-sm text-destructive">{errors.disciplina_ids}</p>
        )}
      </div>

      {/* Classes */}
      <div className="space-y-1.5">
        <Label>
          Classes <span className="text-destructive"></span>
        </Label>
        <MultipleSelector
          value={data.classe_ids}
          onChange={(newSelected) => setData('classe_ids', newSelected)}
          options={classeOptions}
          placeholder="Selecione pelo menos uma classe..."
          emptyIndicator="Nenhuma classe encontrada"
          className="w-full"
        />
        {data.classe_ids.length === 0 && (
          <p className="text-xs text-muted-foreground">
            Obrigatório — selecione pelo menos uma classe
          </p>
        )}
        {errors.classe_ids && <p className="text-sm text-destructive">{errors.classe_ids}</p>}
      </div>

      {/* Período — DROPDOWN SIMPLES */}
      <div className="space-y-1.5">
        <Label htmlFor="periodo">
          Período <span className="text-destructive"></span>
        </Label>
        <Select
          value={data.periodo}
          onValueChange={(value) => setData('periodo', value)}
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
        {!data.periodo && (
          <p className="text-xs text-muted-foreground">
            Obrigatório — selecione um período
          </p>
        )}
        {errors.periodo && <p className="text-sm text-destructive">{errors.periodo}</p>}
      </div>

      {/* Datas */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div className="space-y-1.5">
          <Label htmlFor="data_inicio">
            Data de Início <span className="text-destructive"></span>
          </Label>
          <Input
            id="data_inicio"
            type="datetime-local"
            value={data.data_inicio}
            onChange={(e) => setData('data_inicio', e.target.value)}
            required
          />
          {errors.data_inicio && (
            <p className="text-sm text-destructive">{errors.data_inicio}</p>
          )}
        </div>

        <div className="space-y-1.5">
          <Label htmlFor="data_limite">
            Data Limite <span className="text-destructive">*</span>
          </Label>
          <Input
            id="data_limite"
            type="datetime-local"
            value={data.data_limite}
            onChange={(e) => setData('data_limite', e.target.value)}
            required
          />
          {errors.data_limite && (
            <p className="text-sm text-destructive">{errors.data_limite}</p>
          )}
        </div>
      </div>

      {/* Observações */}
      <div className="space-y-1.5">
        <Label htmlFor="observacoes">Observações</Label>
        <Textarea
          id="observacoes"
          rows={3}
          value={data.observacoes}
          onChange={(e) => setData('observacoes', e.target.value)}
        />
      </div>

      {/* Permite reenvio */}
      <div className="flex items-center space-x-2">
        <Switch
          id="permiteReenvio"
          checked={data.permite_reenvio}
          onCheckedChange={(checked) => setData('permite_reenvio', checked)}
        />
        <Label htmlFor="permiteReenvio" className="text-sm font-normal">
          Permite reenvio
        </Label>
      </div>

      {/* Botões */}
      <div className="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
        <Button
          type="button"
          variant="outline"
          onClick={onCancel}
          className="w-full sm:w-auto"
        >
          Cancelar
        </Button>
        <Button
          type="submit"
          disabled={processing}
          className="w-full sm:w-auto"
        >
          {processing ? 'Salvando...' : 'Criar Prazo'}
        </Button>
      </div>
    </form>
  );
}
