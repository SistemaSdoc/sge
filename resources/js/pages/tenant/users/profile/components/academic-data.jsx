import {
  FieldGroup,
  Field,
  FieldLabel,
  FieldLegend,
  FieldSet,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';

function ReadOnlyField({ label, name, value }) {
  return (
    <Field>
      <FieldLabel htmlFor={name}>{label}</FieldLabel>
      <Input
        id={name}
        name={name}
        value={value ?? ''}
        disabled
      />
    </Field>
  );
}

export function AcademicData({ data }) {
  return (
    <div className="w-full max-w-5xl">
      <FieldGroup>
        <FieldSet>
          <FieldLegend>Dados académicos</FieldLegend>
          <div className="grid gap-6 md:grid-cols-2">
            <ReadOnlyField label="Número de matrícula" name="matricula" value={data.academic.matricula} />
            <ReadOnlyField label="Número de processo" name="numero_processo" value={data.academic.numeroProcesso} />
            <ReadOnlyField label="Curso" name="curso" value={data.academic.curso} />
            <ReadOnlyField label="Classe" name="classe" value={data.academic.classe} />
            <ReadOnlyField label="Turno" name="turno" value={data.academic.turno} />
            <ReadOnlyField label="Turma" name="turma" value={data.academic.turma} />
            <ReadOnlyField label="Ano lectivo" name="ano_lectivo" value={data.academic.anoLectivo} />
          </div>
        </FieldSet>
      </FieldGroup>
    </div>
  );
}
