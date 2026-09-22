import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
} from '@/components/ui/field';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import MultipleSelect from '@/components/multiple-select';
import {
  storeIndependente,
  classes as classesRoute,
  turnos as turnosRoute,
  turmas as turmasRoute,
  formOptions as formOptionsRoute,
} from '@/actions/App/Http/Controllers/Tenant/GrupoPapController';

export default function GrupoPapForm({
  instituicao,
  cursosTutelados = [],
  closeDrawer,
  onSuccess,
}) {
  const [data, setData] = useState({
    curso_tutelado_id: '',
    curso_classe_id: '',
    curso_classe_turno_id: '',
    turma_id: '',
    nome_grupo: '',
    alunos: [],
  });
  const [classes, setClasses] = useState([]);
  const [turnos, setTurnos] = useState([]);
  const [turmas, setTurmas] = useState([]);
  const [formOptions, setFormOptions] = useState({ professores: [], alunos: [] });
  const [classesLoaded, setClassesLoaded] = useState(false);
  const [turnosLoaded, setTurnosLoaded] = useState(false);
  const [turmasLoaded, setTurmasLoaded] = useState(false);
  const [errors, setErrors] = useState({});
  const [processing, setProcessing] = useState(false);

  const handleCursoChange = async (value) => {
    setData((prev) => ({
      ...prev,
      curso_tutelado_id: value,
      curso_classe_id: '',
      curso_classe_turno_id: '',
      turma_id: '',
      alunos: [],
    }));
    setClasses([]);
    setTurnos([]);
    setTurmas([]);
    setFormOptions({ professores: [], alunos: [] });
    setClassesLoaded(false);
    setTurnosLoaded(false);
    setTurmasLoaded(false);
    if (!value) return;
    try {
      const res = await fetch(
        `${classesRoute(instituicao.id).url}?curso_tutelado_id=${value}`,
      );
      setClasses(await res.json());
    } catch {
      setClasses([]);
    } finally {
      setClassesLoaded(true);
    }
  };

  const handleClasseChange = async (value) => {
    setData((prev) => ({
      ...prev,
      curso_classe_id: value,
      curso_classe_turno_id: '',
      turma_id: '',
      alunos: [],
    }));
    setTurnos([]);
    setTurmas([]);
    setFormOptions({ professores: [], alunos: [] });
    setTurnosLoaded(false);
    setTurmasLoaded(false);
    if (!value) return;
    try {
      const res = await fetch(
        `${turnosRoute(instituicao.id).url}?curso_classe_id=${value}`,
      );
      setTurnos(await res.json());
    } catch {
      setTurnos([]);
    } finally {
      setTurnosLoaded(true);
    }
  };

  const handleTurnoChange = async (value) => {
    if (!value) return;
    setData((prev) => ({
      ...prev,
      curso_classe_turno_id: value,
      turma_id: '',
      alunos: [],
    }));
    setTurmas([]);
    setFormOptions({ professores: [], alunos: [] });
    setTurmasLoaded(false);
    try {
      const res = await fetch(
        `${turmasRoute(instituicao.id).url}?curso_classe_turno_id=${value}`,
      );
      setTurmas(await res.json());
    } catch {
      setTurmas([]);
    } finally {
      setTurmasLoaded(true);
    }
  };

  const handleTurmaChange = async (value) => {
    setData((prev) => ({ ...prev, turma_id: value, alunos: [] }));
    setFormOptions({ professores: [], alunos: [] });
    if (!value) return;
    try {
      const res = await fetch(
        `${formOptionsRoute(instituicao.id).url}?curso_tutelado_id=${data.curso_tutelado_id}&turma_id=${value}`,
      );
      setFormOptions(await res.json());
    } catch {
      setFormOptions({ professores: [], alunos: [] });
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    setProcessing(true);
    router.post(storeIndependente(instituicao.id).url, data, {
      onSuccess: () => onSuccess?.(),
      onError: (err) => setErrors(err),
      onFinish: () => setProcessing(false),
    });
  };

  return (
    <form onSubmit={handleSubmit}>
      <FieldGroup className="@container/field-group">

        <Field data-invalid={!!errors.curso_tutelado_id}>
          <FieldLabel>Curso</FieldLabel>
          <Select
            value={data.curso_tutelado_id || undefined}
            onValueChange={handleCursoChange}
            disabled={processing}
          >
            <SelectTrigger>
              <SelectValue placeholder="Selecione um curso" />
            </SelectTrigger>
            <SelectContent>
              {cursosTutelados.map((c) => (
                <SelectItem key={c.id} value={String(c.id)}>
                  {c.nome}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          {errors.curso_tutelado_id && (
            <FieldError>{errors.curso_tutelado_id}</FieldError>
          )}
        </Field>

        <Field data-invalid={!!errors.curso_classe_id}>
          <FieldLabel>Classe</FieldLabel>
          <Select
            key={data.curso_classe_id || 'empty-classe'}
            value={data.curso_classe_id || undefined}
            onValueChange={handleClasseChange}
            disabled={processing || !data.curso_tutelado_id}
          >
            <SelectTrigger>
              <SelectValue
                placeholder={
                  !data.curso_tutelado_id
                    ? 'Selecione um curso primeiro'
                    : 'Selecione uma classe'
                }
              />
            </SelectTrigger>
            <SelectContent>
              {classes.map((c) => (
                <SelectItem key={c.id} value={String(c.id)}>
                  {c.nome}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          {classesLoaded && classes.length === 0 && (
            <p className="text-sm text-muted-foreground">
              Nenhuma classe disponível para este curso.
            </p>
          )}
          {errors.curso_classe_id && (
            <FieldError>{errors.curso_classe_id}</FieldError>
          )}
        </Field>

        <Field data-invalid={!!errors.curso_classe_turno_id}>
          <FieldLabel>Turno</FieldLabel>
          <Select
            key={data.curso_classe_turno_id || 'empty-turno'}
            value={data.curso_classe_turno_id || undefined}
            onValueChange={handleTurnoChange}
            disabled={processing || !data.curso_classe_id}
          >
            <SelectTrigger>
              <SelectValue
                placeholder={
                  !data.curso_classe_id
                    ? 'Selecione uma classe primeiro'
                    : 'Selecione um turno'
                }
              />
            </SelectTrigger>
            <SelectContent>
              {turnos.map((t) => (
                <SelectItem key={t.id} value={String(t.id)}>
                  {t.nome}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          {turnosLoaded && turnos.length === 0 && (
            <p className="text-sm text-muted-foreground">
              Nenhum turno disponível para esta classe.
            </p>
          )}
          {errors.curso_classe_turno_id && (
            <FieldError>{errors.curso_classe_turno_id}</FieldError>
          )}
        </Field>

        <Field data-invalid={!!errors.turma_id}>
          <FieldLabel>Turma</FieldLabel>
          <Select
            key={data.turma_id || 'empty-turma'}
            value={data.turma_id || undefined}
            onValueChange={handleTurmaChange}
            disabled={processing || !data.curso_classe_turno_id}
          >
            <SelectTrigger>
              <SelectValue
                placeholder={
                  !data.curso_classe_turno_id
                    ? 'Selecione um turno primeiro'
                    : 'Selecione uma turma'
                }
              />
            </SelectTrigger>
            <SelectContent>
              {turmas.map((t) => (
                <SelectItem key={t.id} value={String(t.id)}>
                  {t.nome}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          {turmasLoaded && turmas.length === 0 && (
            <p className="text-sm text-muted-foreground">
              Nenhuma turma disponível para este turno.
            </p>
          )}
          {errors.turma_id && <FieldError>{errors.turma_id}</FieldError>}
        </Field>

        {data.turma_id && (
          <>
            <Field data-invalid={!!errors.nome_grupo}>
              <FieldLabel>Nome do grupo</FieldLabel>
              <Input
                placeholder="Ex.: Grupo Alpha"
                value={data.nome_grupo}
                onChange={(e) =>
                  setData((prev) => ({ ...prev, nome_grupo: e.target.value }))
                }
                disabled={processing}
              />
              {errors.nome_grupo && (
                <FieldError>{errors.nome_grupo}</FieldError>
              )}
            </Field>

            <Field>
              <FieldLabel>Alunos</FieldLabel>
              <MultipleSelect
                placeholder={
                  formOptions.alunos.length === 0
                    ? 'Nenhuma opção disponível'
                    : 'Selecione os alunos'
                }
                disabled={formOptions.alunos.length === 0}
                items={formOptions.alunos.map((a) => ({
                  value: a.id,
                  label: a.nome,
                }))}
                value={data.alunos.map((id) => ({
                  value: id,
                  label: formOptions.alunos.find((a) => a.id === id)?.nome ?? id,
                }))}
                onChange={(opts) =>
                  setData((prev) => ({
                    ...prev,
                    alunos: opts.map((o) => o.value),
                  }))
                }
              />
              {Object.keys(errors)
                .filter((k) => k.startsWith('alunos'))
                .map((k) => (
                  <FieldError key={k}>{errors[k]}</FieldError>
                ))}
            </Field>
          </>
        )}

        <Field>
          <Button type="submit" disabled={processing || !data.turma_id}>
            {processing ? (
              <>
                <Loader2 className="animate-spin" /> A guardar...
              </>
            ) : (
              'Guardar'
            )}
          </Button>
          <Button
            type="button"
            variant="outline"
            onClick={closeDrawer}
            disabled={processing}
          >
            Cancelar
          </Button>
        </Field>

      </FieldGroup>
    </form>
  );
}