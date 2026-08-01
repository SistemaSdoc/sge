import { useForm, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Field,
  FieldDescription,
  FieldError,
  FieldGroup,
  FieldLabel,
} from '@/components/ui/field';
import { Loader2 } from 'lucide-react';
import {
  index,
  store,
} from '@/actions/App/Http/Controllers/ConfirmacaoMatriculaController';

export function ConfirmarMatriculaModal({
  aluno,
  params,
  onCancel,
  onSuccess,
}) {
  // anosLectivos e turmasPorAno vêm como props do servidor
  const { anosLectivos = [], turmasPorAno = [] } = usePage().props;

  const { data, setData, post, processing, errors } = useForm({
    aluno_id: aluno.id,
    ano_lectivo_id: '',
    turma_nova_id: '',
  });

  // Quando o ano muda, faz partial reload para buscar as turmas desse ano
  const handleAnoChange = (anoId) => {
    setData((prev) => ({ ...prev, ano_lectivo_id: anoId, turma_nova_id: '' }));

    router.visit(index(params).url, {
      data: { ano_id: anoId },
      only: ['turmasPorAno'],
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleConfirm = () => {
    post(store(params).url, {
      preserveScroll: true,
      onSuccess: () => onSuccess(),
    });
  };

  return (
    <div className="space-y-6">
      {/* Info do Aluno */}
      <div className="space-y-2">
        <div className="flex items-center justify-between">
          <span className="text-sm text-muted-foreground">Curso:</span>
          <span className="text-sm font-medium">{aluno.curso}</span>
        </div>
        <div className="flex items-center justify-between">
          <span className="text-sm text-muted-foreground">Classe Actual:</span>
          <span className="text-sm font-medium">{aluno.classe_actual}</span>
        </div>
        <div className="flex items-center justify-between">
          <span className="text-sm text-muted-foreground">Próxima Classe:</span>
          <span className="text-sm font-medium text-green-600">
            {aluno.classe_proximo_ano}
          </span>
        </div>
        <div className="flex items-center justify-between">
          <span className="text-sm text-muted-foreground">Status:</span>
          <span className="inline-flex items-center text-xs font-medium text-blue-700 uppercase dark:text-blue-400">
            {aluno.status}
          </span>
        </div>
      </div>

      {/* Form */}
      <FieldGroup>
        <div className="grid grid-cols-2 gap-4">
          {/* Ano Lectivo */}
          <Field>
            <FieldLabel htmlFor="ano-lectivo">
              Ano Lectivo <span className="text-red-500">*</span>
            </FieldLabel>
            <Select
              value={data.ano_lectivo_id}
              onValueChange={handleAnoChange}
              disabled={processing}
            >
              <SelectTrigger id="ano-lectivo">
                <SelectValue placeholder="Selecione um ano..." />
              </SelectTrigger>
              <SelectContent>
                {anosLectivos.map((ano) => (
                  <SelectItem key={ano.id} value={String(ano.id)}>
                    <div className="flex items-center gap-2">
                      <span>{ano.nome}</span>
                      {ano.activo && (
                        <span className="text-xs font-medium text-green-600">
                          (Activo)
                        </span>
                      )}
                    </div>
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {errors.ano_lectivo_id && (
              <FieldError>{errors.ano_lectivo_id}</FieldError>
            )}
            <FieldDescription>
              Escolha o ano lectivo para inscrição
            </FieldDescription>
          </Field>

          {/* Turma */}
          <Field>
            <FieldLabel htmlFor="turma">
              Turma <span className="text-red-500">*</span>
            </FieldLabel>
            <Select
              value={data.turma_nova_id}
              onValueChange={(val) => setData('turma_nova_id', val)}
              disabled={
                processing || !data.ano_lectivo_id || turmasPorAno.length === 0
              }
            >
              <SelectTrigger id="turma">
                <SelectValue
                  placeholder={
                    !data.ano_lectivo_id
                      ? 'Selecione um ano primeiro...'
                      : 'Selecione uma turma...'
                  }
                />
              </SelectTrigger>
              <SelectContent>
                {turmasPorAno.map((turma) => (
                  <SelectItem key={turma.id} value={String(turma.id)}>
                    <div className="flex items-center gap-2">
                      <span className="font-medium">{turma.nome}</span>
                      <span className="text-xs text-muted-foreground">
                        {turma.turno}
                      </span>
                    </div>
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {errors.turma_nova_id && (
              <FieldError>{errors.turma_nova_id}</FieldError>
            )}
            <FieldDescription>
              {turmasPorAno.length === 0 && data.ano_lectivo_id ? (
                <span className="text-amber-600">Nenhuma turma disponível</span>
              ) : (
                `${turmasPorAno.length} turma(s) disponível(eis)`
              )}
            </FieldDescription>
          </Field>
        </div>
      </FieldGroup>

      {/* Botões */}
      <div className="flex justify-end gap-3">
        <Button variant="outline" onClick={onCancel} disabled={processing}>
          Cancelar
        </Button>
        <Button
          onClick={handleConfirm}
          disabled={processing || !data.ano_lectivo_id || !data.turma_nova_id}
        >
          {processing && <Loader2 className="mr-2 size-4 animate-spin" />}
          Confirmar Matrícula
        </Button>
      </div>
    </div>
  );
}
