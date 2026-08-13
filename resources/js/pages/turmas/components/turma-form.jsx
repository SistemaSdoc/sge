import { router, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
} from '@/components/ui/field';
import { toast } from 'sonner';
import { index } from '@/actions/App/Http/Controllers/TurmaController';
import { store } from '@/actions/App/Http/Controllers/ClasseTurnoTurmaController';

export function TurmaForm({
  cursos = [],
  classes = [],
  onSuccess,
  closeDrawer,
  instituicaoId,
}) {
  const { anoLectivoActual, turnos = [], auth } = usePage().props;

  const { data, setData, post, processing, errors } = useForm({
    curso_tutelado_id: '',
    curso_classe_id: '',
    curso_classe_turno_id: '',
    nome: '',
    ano_lectivo_id: anoLectivoActual,
  });

  const classesFiltradas = data.curso_tutelado_id
    ? classes.filter((c) => c.curso_tutelado_id == data.curso_tutelado_id)
    : [];

  const handleCursoChange = (value) => {
    setData((prev) => ({
      ...prev,
      curso_tutelado_id: value,
      curso_classe_id: '',
      curso_classe_turno_id: '',
      nome: '',
    }));
  };

  const handleClasseChange = (value) => {
    setData((prev) => ({
      ...prev,
      curso_classe_id: value,
      curso_classe_turno_id: '',
      nome: '',
    }));

    router.visit(index().url, {
      data: { curso_classe_id: value },
      only: ['turnos'],
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleTurnoChange = (value) => {
    setData((prev) => ({
      ...prev,
      curso_classe_turno_id: value,
      nome: '',
    }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();

    post(
      store({
        instituicao: instituicaoId,
        cursoTutelado: data.curso_tutelado_id,
        cursoClasse: data.curso_classe_id,
        cursoClasseTurno: data.curso_classe_turno_id,
      }).url,
      {
        onSuccess: () => {
          toast.success('Turma criada com sucesso');
          onSuccess?.();
        },
        onError: () => {
          toast.error('Não foi possível criar a turma');
        },
      },
    );
  };

  const isFormValid =
    data.curso_tutelado_id &&
    data.curso_classe_id &&
    data.curso_classe_turno_id &&
    data.nome.trim();

  return (
    <form onSubmit={handleSubmit}>
      <FieldGroup className="@container/field-group">
        {/* Curso */}
        <Field data-invalid={!!errors.curso_tutelado_id}>
          <FieldLabel htmlFor="curso">Curso</FieldLabel>
          <Select
            value={data.curso_tutelado_id}
            onValueChange={handleCursoChange}
          >
            <SelectTrigger id="curso" aria-invalid={!!errors.curso_tutelado_id}>
              <SelectValue placeholder="Seleciona um curso" />
            </SelectTrigger>
            <SelectContent>
              {cursos.map((curso) => (
                <SelectItem key={curso.id} value={String(curso.id)}>
                  {curso.nome}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          {errors.curso_tutelado_id && (
            <FieldError>{errors.curso_tutelado_id}</FieldError>
          )}
        </Field>

        {/* Classe */}
        <Field data-invalid={!!errors.curso_classe_id}>
          <FieldLabel htmlFor="classe">Classe</FieldLabel>
          <Select
            value={data.curso_classe_id}
            onValueChange={handleClasseChange}
            disabled={!data.curso_tutelado_id}
          >
            <SelectTrigger id="classe" aria-invalid={!!errors.curso_classe_id}>
              <SelectValue
                placeholder={
                  data.curso_tutelado_id
                    ? 'Selecione uma classe'
                    : 'Selecione um curso primeiro'
                }
              />
            </SelectTrigger>
            <SelectContent>
              {classesFiltradas.map((classe) => (
                <SelectItem key={classe.id} value={String(classe.id)}>
                  {classe.nome}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          {errors.curso_classe_id && (
            <FieldError>{errors.curso_classe_id}</FieldError>
          )}
        </Field>

        {/* Turno */}
        <Field data-invalid={!!errors.curso_classe_turno_id}>
          <FieldLabel htmlFor="turno">Turno</FieldLabel>
          <Select
            value={data.curso_classe_turno_id}
            onValueChange={handleTurnoChange}
            disabled={!data.curso_classe_id}
          >
            <SelectTrigger
              id="turno"
              aria-invalid={!!errors.curso_classe_turno_id}
            >
              <SelectValue
                placeholder={
                  data.curso_classe_id
                    ? 'Selecione um turno'
                    : 'Selecione uma classe primeiro'
                }
              />
            </SelectTrigger>
            <SelectContent>
              {turnos.map((turno) => (
                <SelectItem key={turno.id} value={String(turno.id)}>
                  {turno.nome}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          {errors.curso_classe_turno_id && (
            <FieldError>{errors.curso_classe_turno_id}</FieldError>
          )}
        </Field>

        {/* Nome */}
        <Field data-invalid={!!errors.nome}>
          <FieldLabel htmlFor="nome">Nome da Turma</FieldLabel>
          <Input
            id="nome"
            type="text"
            placeholder="ex: ATI"
            value={data.nome}
            onChange={(e) => setData('nome', e.target.value)}
            disabled={!data.curso_classe_turno_id}
            aria-invalid={!!errors.nome}
          />
          {errors.nome && <FieldError>{errors.nome}</FieldError>}
        </Field>

        {/* Botões de acção */}
        <Field>
          <Button type="submit" disabled={!isFormValid || processing}>
            {processing ? 'A criar...' : 'Criar Turma'}
          </Button>

          <Button variant="outline" onClick={closeDrawer} disabled={processing}>
            Cancelar
          </Button>
        </Field>
      </FieldGroup>
    </form>
  );
}
