import {
  Field,
  FieldError,
  FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';

export function ProfileField({
  label,
  name,
  value,
  onChange,
  error,
  type = 'text',
}) {
  return (
    <Field data-invalid={Boolean(error)}>
      <FieldLabel htmlFor={name}>{label}</FieldLabel>

      <Input
        id={name}
        name={name}
        type={type}
        value={value ?? ''}
        onChange={onChange}
        aria-invalid={Boolean(error)}
      />
      
      <FieldError>{error}</FieldError>
    </Field>
  );
}
