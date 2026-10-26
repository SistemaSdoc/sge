import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { PhoneInput } from '@/components/ui/phone-input';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import FormSection from './form-section';

export default function PersonalDataSection({
  errors,
  profile,
  telefone,
  onTelefoneChange,
}) {
  return (
    <FormSection
      id="dados-pessoais"
      title="Dados pessoais"
      description="Complete os dados usados nos seus registos e documentos escolares."
    >
      <Field data-invalid={Boolean(errors.data_nascimento)}>
        <FieldLabel htmlFor="data_nascimento">Data de nascimento</FieldLabel>
        <Input
          id="data_nascimento"
          name="data_nascimento"
          type="date"
          autoComplete="bday"
          defaultValue={profile.dataNascimento ?? ''}
          aria-invalid={Boolean(errors.data_nascimento)}
          required
        />
        <FieldError>{errors.data_nascimento}</FieldError>
      </Field>

      <Field data-invalid={Boolean(errors.genero)}>
        <FieldLabel htmlFor="genero">Género</FieldLabel>

        <Select
          name="genero"
          defaultValue={profile.genero || undefined}
          required
        >
          <SelectTrigger
            id="genero"
            aria-invalid={Boolean(errors.genero)}
            className="w-full"
          >
            <SelectValue placeholder="Selecione" />
          </SelectTrigger>
          <SelectContent>
            <SelectGroup>
              <SelectItem value="F">Feminino</SelectItem>
              <SelectItem value="M">Masculino</SelectItem>
            </SelectGroup>
          </SelectContent>
        </Select>

        <FieldError>{errors.genero}</FieldError>
      </Field>

      <Field data-invalid={Boolean(errors.nome_pai)}>
        <FieldLabel htmlFor="nome_pai">Filho de</FieldLabel>
        <Input
          id="nome_pai"
          name="nome_pai"
          defaultValue={profile.nomePai ?? ''}
          placeholder="Nome do pai"
          aria-invalid={Boolean(errors.nome_pai)}
          required
        />
        <FieldError>{errors.nome_pai}</FieldError>
      </Field>

      <Field data-invalid={Boolean(errors.nome_mae)}>
        <FieldLabel htmlFor="nome_mae">E de</FieldLabel>
        <Input
          id="nome_mae"
          name="nome_mae"
          defaultValue={profile.nomeMae ?? ''}
          placeholder="Nome da mãe"
          aria-invalid={Boolean(errors.nome_mae)}
          required
        />
        <FieldError>{errors.nome_mae}</FieldError>
      </Field>

      <Field data-invalid={Boolean(errors.nacionalidade)}>
        <FieldLabel htmlFor="nacionalidade">Nacionalidade</FieldLabel>
        <Input
          id="nacionalidade"
          name="nacionalidade"
          autoComplete="country-name"
          placeholder="Ex.: Angolana"
          defaultValue={profile.nacionalidade ?? ''}
          aria-invalid={Boolean(errors.nacionalidade)}
          required
        />
        <FieldError>{errors.nacionalidade}</FieldError>
      </Field>

      <Field data-invalid={Boolean(errors.naturalidade)}>
        <FieldLabel htmlFor="naturalidade">Naturalidade</FieldLabel>
        <Input
          id="naturalidade"
          name="naturalidade"
          placeholder="Ex.: Luanda"
          defaultValue={profile.naturalidade ?? ''}
          aria-invalid={Boolean(errors.naturalidade)}
          required
        />
        <FieldError>{errors.naturalidade}</FieldError>
      </Field>

      <Field className="sm:col-span-2" data-invalid={Boolean(errors.telefone)}>
        <FieldLabel htmlFor="telefone">Telefone</FieldLabel>
        <PhoneInput
          id="telefone"
          type="tel"
          autoComplete="tel"
          defaultCountry="AO"
          value={telefone}
          onChange={onTelefoneChange}
          placeholder="923 000 000"
          aria-invalid={Boolean(errors.telefone)}
          required
        />
        <FieldError>{errors.telefone}</FieldError>
      </Field>
    </FormSection>
  );
}
