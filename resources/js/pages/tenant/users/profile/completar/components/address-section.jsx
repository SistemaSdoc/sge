import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import FormSection from './form-section';

export default function AddressSection({ errors, profile }) {
  return (
    <FormSection
      id="endereco"
      title="Endereço"
      description="Preencha o município e a morada onde reside actualmente."
    >
      <Field data-invalid={Boolean(errors.municipio)}>
        <FieldLabel htmlFor="municipio">Município</FieldLabel>
        <Input
          id="municipio"
          name="municipio"
          autoComplete="address-level2"
          placeholder="Ex.: Belas"
          defaultValue={profile.municipio ?? ''}
          aria-invalid={Boolean(errors.municipio)}
          required
        />
        <FieldError>{errors.municipio}</FieldError>
      </Field>

      <Field data-invalid={Boolean(errors.morada)}>
        <FieldLabel htmlFor="morada">Morada</FieldLabel>
        <Input
          id="morada"
          name="morada"
          autoComplete="street-address"
          placeholder="Rua, bairro e número da casa"
          defaultValue={profile.morada ?? ''}
          aria-invalid={Boolean(errors.morada)}
          required
        />
        <FieldError>{errors.morada}</FieldError>
      </Field>
    </FormSection>
  );
}
