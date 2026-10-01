import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import FormSection from './form-section';

export default function IdentificationSection({ profile }) {
  return (
    <FormSection
      id="identificacao"
      title="Identificação"
      description="Dados registados pela instituição. O número de estudante é atribuído automaticamente."
    >
      <Field>
        <FieldLabel htmlFor="nome">Nome completo</FieldLabel>
        <Input id="nome" defaultValue={profile.nome} readOnly />
      </Field>

      <Field>
        <FieldLabel htmlFor="bi">Bilhete de Identidade</FieldLabel>
        <Input id="bi" defaultValue={profile.bi} readOnly />
      </Field>
    </FormSection>
  );
}
