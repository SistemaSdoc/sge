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

export default function GrupoPapForm({
  errors,
  processing,
  cursosTutelados = [],
  classes = [],
  turnos = [],
  turmas = [],
  cursoTuteladoId,
  setCursoTuteladoId,
  cursoClasseId,
  setCursoClasseId,
  cursoClasseTurnoId,
  setCursoClasseTurnoId,
  turmaId,
  setTurmaId,
  nomeGrupo,
  setNomeGrupo,
  professores = [],
  alunos = [],
  professorTutorId,
  setProfessorTutorId,
  alunoIds,
  setAlunoIds,
  closeDrawer,
}) {
  const turmaSeleccionada = Boolean(turmaId);

  const emptyOption = (
    <div className="px-2 py-1.5 text-sm text-muted-foreground">
      Nenhuma opção disponível
    </div>
  );

  return (
    <FieldGroup className="@container/field-group px-4 py-8">
      {/* Curso */}
      <Field data-invalid={!!errors.curso_tutelado_id}>
        <FieldLabel htmlFor="curso">Curso</FieldLabel>
        <Select
          key={`curso-${cursoTuteladoId ?? 'empty'}`}
          value={cursoTuteladoId || undefined}
          onValueChange={setCursoTuteladoId}
          disabled={processing || cursosTutelados.length === 0}
        >
          <SelectTrigger id="curso">
            <SelectValue
              placeholder={
                cursosTutelados.length === 0
                  ? 'Nenhuma opção disponível'
                  : 'Seleciona um curso'
              }
            />
          </SelectTrigger>
          <SelectContent>
            {cursosTutelados.length === 0
              ? emptyOption
              : cursosTutelados.map((c) => (
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

      {/* Classe */}
      <Field data-invalid={!!errors.curso_classe_id}>
        <FieldLabel htmlFor="classe">Classe</FieldLabel>
        <Select
          key={`classe-${cursoTuteladoId ?? 'empty'}-${cursoClasseId ?? 'empty'}`}
          value={cursoClasseId || undefined}
          onValueChange={setCursoClasseId}
          disabled={processing || !cursoTuteladoId || classes.length === 0}
        >
          <SelectTrigger id="classe">
            <SelectValue
              placeholder={
                !cursoTuteladoId
                  ? 'Selecione um curso primeiro'
                  : classes.length === 0
                    ? 'Nenhuma opção disponível'
                    : 'Selecione uma classe'
              }
            />
          </SelectTrigger>
          <SelectContent>
            {classes.length === 0
              ? emptyOption
              : classes.map((c) => (
                  <SelectItem key={c.id} value={String(c.id)}>
                    {c.nome}
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
          key={`turno-${cursoClasseId ?? 'empty'}-${cursoClasseTurnoId ?? 'empty'}`}
          value={cursoClasseTurnoId || undefined}
          onValueChange={setCursoClasseTurnoId}
          disabled={processing || !cursoClasseId || turnos.length === 0}
        >
          <SelectTrigger id="turno">
            <SelectValue
              placeholder={
                !cursoClasseId
                  ? 'Selecione uma classe primeiro'
                  : turnos.length === 0
                    ? 'Nenhuma opção disponível'
                    : 'Selecione um turno'
              }
            />
          </SelectTrigger>
          <SelectContent>
            {turnos.length === 0
              ? emptyOption
              : turnos.map((t) => (
                  <SelectItem key={t.id} value={String(t.id)}>
                    {t.nome}
                  </SelectItem>
                ))}
          </SelectContent>
        </Select>
        {errors.curso_classe_turno_id && (
          <FieldError>{errors.curso_classe_turno_id}</FieldError>
        )}
      </Field>

      {/* Turma */}
      <Field data-invalid={!!errors.turma_id}>
        <FieldLabel htmlFor="turma">Turma</FieldLabel>
        <Select
          key={`turma-${cursoClasseTurnoId ?? 'empty'}-${turmaId ?? 'empty'}`}
          value={turmaId || undefined}
          onValueChange={setTurmaId}
          disabled={processing || !cursoClasseTurnoId || turmas.length === 0}
        >
          <SelectTrigger id="turma">
            <SelectValue
              placeholder={
                !cursoClasseTurnoId
                  ? 'Selecione um turno primeiro'
                  : turmas.length === 0
                    ? 'Nenhuma opção disponível'
                    : 'Selecione uma turma'
              }
            />
          </SelectTrigger>
          <SelectContent>
            {turmas.length === 0
              ? emptyOption
              : turmas.map((t) => (
                  <SelectItem key={t.id} value={String(t.id)}>
                    {t.nome}
                  </SelectItem>
                ))}
          </SelectContent>
        </Select>
        {errors.turma_id && <FieldError>{errors.turma_id}</FieldError>}
      </Field>

      {/* Campos pós-turma */}
      {turmaSeleccionada && (
        <>
          <Field>
            <FieldLabel htmlFor="nome_grupo">Nome do grupo</FieldLabel>
            <Input
              id="nome_grupo"
              disabled={processing}
              placeholder="Ex.: Grupo Alpha"
              value={nomeGrupo ?? ''}
              onChange={(e) => setNomeGrupo(e.target.value)}
            />
            {errors.nome_grupo && <FieldError>{errors.nome_grupo}</FieldError>}
          </Field>

          <Field>
            <FieldLabel>Alunos</FieldLabel>
            <MultipleSelect
              placeholder={
                alunos.length === 0
                  ? 'Nenhuma opção disponível'
                  : 'Selecione os alunos'
              }
              disabled={alunos.length === 0}
              items={alunos.map((a) => ({ value: a.id, label: a.nome }))}
              onChange={(opts) => setAlunoIds(opts.map((o) => o.value))}
              value={alunoIds.map((id) => ({
                value: id,
                label: alunos.find((a) => a.id === id)?.nome ?? id,
              }))}
            />
            {Object.keys(errors)
              .filter((k) => k.startsWith('alunos'))
              .map((k) => (
                <FieldError key={k}>{errors[k]}</FieldError>
              ))}
          </Field>

          <Field>
            <FieldLabel htmlFor="professor_tutor">Professor tutor</FieldLabel>
            <Select
              value={professorTutorId || undefined}
              onValueChange={setProfessorTutorId}
              disabled={processing || professores.length === 0}
            >
              <SelectTrigger id="professor_tutor">
                <SelectValue
                  placeholder={
                    professores.length === 0
                      ? 'Nenhuma opção disponível'
                      : 'Selecione o professor tutor'
                  }
                />
              </SelectTrigger>
              <SelectContent>
                {professores.length === 0
                  ? emptyOption
                  : professores.map((p) => (
                      <SelectItem key={p.id} value={String(p.id)}>
                        {p.nome}
                      </SelectItem>
                    ))}
              </SelectContent>
            </Select>
            {errors.professor_tutor_id && (
              <FieldError>{errors.professor_tutor_id}</FieldError>
            )}
          </Field>
        </>
      )}

      {/* Acções */}
      <Field>
        <Button type="submit" disabled={processing || !turmaSeleccionada}>
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
  );
}
