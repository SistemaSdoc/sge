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

export default function Preencher({
  aluno,
  classesFaltando,
  anosLectivos,
  onCancel,
  onSuccess,
}) {
  const { turnos = [], turmasPorTurno = [] } = usePage().props;
  const { data, setData, post, processing, errors } = useForm({
    ano_lectivo_id: '',
    curso_classe_id: '',
    curso_classe_turno_id: '',
    turma_id: '',
  });

  const handleAnoOuClasseChange = (field, value) => {
    const next = {
      ...data,
      [field]: value,
      curso_classe_turno_id: '',
      turma_id: '',
    };
    setData(next);

    if (next.ano_lectivo_id && next.curso_classe_id) {
      router.visit(window.location.href, {
        data: {
          ano_lectivo_id: next.ano_lectivo_id,
          curso_classe_id: next.curso_classe_id,
        },
        only: ['turnos'],
        preserveState: true,
        preserveScroll: true,
      });
    }
  };

  const handleTurnoChange = (value) => {
    setData((prev) => ({
      ...prev,
      curso_classe_turno_id: value,
      turma_id: '',
    }));

    router.visit(window.location.href, {
      data: {
        ano_lectivo_id: data.ano_lectivo_id,
        curso_classe_turno_id: value,
      },
      only: ['turmasPorTurno'],
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleConfirm = () => {
    post(route('historico.confirmar', { aluno: aluno.id }), {
      preserveScroll: true,
      onSuccess,
    });
  };

  return (
    <div className="space-y-6">
      {/* Info */}
      <div className="space-y-1 rounded-lg bg-slate-50 p-3 dark:bg-slate-900/30">
        <div className="text-sm font-medium">{aluno.nome}</div>
        <div className="text-xs text-muted-foreground">
          Matrícula: {aluno.matricula}
        </div>
      </div>

      <FieldGroup>
        <div className="space-y-4">
          {/* Ano Lectivo */}
          <Field>
            <FieldLabel htmlFor="ano">
              Ano Lectivo <span className="text-red-500">*</span>
            </FieldLabel>
            <Select
              value={data.ano_lectivo_id}
              onValueChange={(val) =>
                handleAnoOuClasseChange('ano_lectivo_id', val)
              }
              disabled={processing}
            >
              <SelectTrigger id="ano">
                <SelectValue placeholder="Selecione um ano..." />
              </SelectTrigger>
              <SelectContent>
                {anosLectivos.map((ano) => (
                  <SelectItem key={ano.id} value={String(ano.id)}>
                    {ano.nome}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Field>

          {/* Classe */}
          <Field>
            <FieldLabel htmlFor="classe">
              Classe <span className="text-red-500">*</span>
            </FieldLabel>
            <Select
              value={data.curso_classe_id}
              onValueChange={(val) =>
                handleAnoOuClasseChange('curso_classe_id', val)
              }
              disabled={!data.ano_lectivo_id || processing}
            >
              <SelectTrigger id="classe">
                <SelectValue placeholder="Selecione uma classe..." />
              </SelectTrigger>
              <SelectContent>
                {classesFaltando.map((classe) => (
                  <SelectItem
                    key={classe.curso_classe_id}
                    value={String(classe.curso_classe_id)}
                  >
                    {classe.classe}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Field>

          {/* Turno */}
          <Field>
            <FieldLabel htmlFor="turno">
              Turno <span className="text-red-500">*</span>
            </FieldLabel>
            <Select
              value={data.curso_classe_turno_id}
              onValueChange={handleTurnoChange}
              disabled={
                !data.curso_classe_id || turnos.length === 0 || processing
              }
            >
              <SelectTrigger id="turno">
                <SelectValue
                  placeholder={
                    !data.curso_classe_id
                      ? 'Selecione uma classe primeiro...'
                      : turnos.length === 0
                        ? 'Nenhum turno disponível'
                        : 'Selecione um turno...'
                  }
                />
              </SelectTrigger>
              <SelectContent>
                {turnos.map((turno) => (
                  <SelectItem key={turno.id} value={String(turno.id)}>
                    {turno.turno_nome}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Field>

          {/* Turma */}
          <Field>
            <FieldLabel htmlFor="turma">
              Turma <span className="text-red-500">*</span>
            </FieldLabel>
            <Select
              value={data.turma_id}
              onValueChange={(val) => setData('turma_id', val)}
              disabled={
                !data.curso_classe_turno_id ||
                turmasPorTurno.length === 0 ||
                processing
              }
            >
              <SelectTrigger id="turma">
                <SelectValue
                  placeholder={
                    !data.curso_classe_turno_id
                      ? 'Selecione um turno primeiro...'
                      : turmasPorTurno.length === 0
                        ? 'Nenhuma turma disponível'
                        : 'Selecione uma turma...'
                  }
                />
              </SelectTrigger>
              <SelectContent>
                {turmasPorTurno.map((turma) => (
                  <SelectItem key={turma.id} value={String(turma.id)}>
                    {turma.nome}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {errors.turma_id && <FieldError>{errors.turma_id}</FieldError>}
            <FieldDescription>
              {turmasPorTurno.length === 0 && data.curso_classe_turno_id ? (
                <span className="text-amber-600">Nenhuma turma disponível</span>
              ) : (
                `${turmasPorTurno.length} turma(s) disponível(eis)`
              )}
            </FieldDescription>
          </Field>
        </div>
      </FieldGroup>

      {/* Botões */}
      <div className="flex justify-end gap-3 pt-4">
        <Button variant="outline" onClick={onCancel} disabled={processing}>
          Cancelar
        </Button>
        
        <Button onClick={handleConfirm} disabled={!data.turma_id || processing}>
          {processing && <Loader2 className="mr-2 size-4 animate-spin" />}
          Confirmar
        </Button>
      </div>
    </div>
  );
}
