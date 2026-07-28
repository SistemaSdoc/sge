import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Field,
  FieldDescription,
  FieldGroup,
  FieldLabel,
  FieldLegend,
  FieldSet,
} from '@/components/ui/field';
import {
  InputGroup,
  InputGroupAddon,
  InputGroupInput,
} from '@/components/ui/input-group';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { MailIcon } from 'lucide-react';
import { PhoneInput } from '@/components/ui/phone-input';

export default function FormLayout() {
  return (
    <>
      <div className="mx-auto flex h-screen max-w-6xl items-center space-y-10 px-4 py-8 sm:px-6 lg:px-8">
        <form>
          {/* Informações Básicas */}
          <FieldSet className="grid grid-cols-1 gap-10 md:grid-cols-3">
            <div>
              <FieldLegend className="mb-1.5 font-semibold">
                Informações Básicas
              </FieldLegend>
              <FieldDescription>
                Insira suas informações básicas.
              </FieldDescription>
            </div>

            <FieldGroup className="grid grid-cols-1 gap-6 sm:grid-cols-2 md:col-span-2">
              <Field className="gap-2 sm:col-span-2">
                <FieldLabel htmlFor="first-name">Nome completo</FieldLabel>
                <Input id="first-name" placeholder="Marq Silva" />
                <FieldDescription className="text-xs">
                  Seu nome conforme registrado em documentos oficiais
                </FieldDescription>
              </Field>

              <Field className="gap-2">
                <FieldLabel htmlFor="email">Endereço de E-mail</FieldLabel>
                <InputGroup>
                  <InputGroupInput
                    id="email"
                    type="email"
                    placeholder="Endereço de e-mail"
                  />
                  <InputGroupAddon align="inline-end">
                    <MailIcon className="size-4" />
                    <span className="sr-only">Email</span>
                  </InputGroupAddon>
                </InputGroup>
                <FieldDescription className="text-xs">
                  We&apos;ll never share your email with anyone else
                </FieldDescription>
              </Field>

              <Field className="gap-2">
                <FieldLabel htmlFor="mobile">Telefone</FieldLabel>
                <PhoneInput id="phone" placeholder="Enter contact number" />
                <FieldDescription className="text-xs">
                  Inclua o código do país
                </FieldDescription>
              </Field>
            </FieldGroup>
          </FieldSet>

          <Separator className="my-10" />

          {/* Documentos  */}
          <FieldSet className="grid grid-cols-1 gap-10 md:grid-cols-3">
            <div>
              <FieldLegend className="mb-1.5 font-semibold">
                Documentos Necessários
              </FieldLegend>
              <FieldDescription>
                Faça upload dos documentos necessários para o processo de
                candidatura. Os documentos incluem a BI, Certificado...
              </FieldDescription>
            </div>

            <FieldGroup className="grid grid-cols-1 gap-6 sm:grid-cols-2 md:col-span-2">
              <Field className="gap-2 sm:col-span-2">
                <FieldLabel htmlFor="workspace-description">
                  Workspace Description
                </FieldLabel>
                <Textarea
                  placeholder="Describe your workspace purpose and goals..."
                  id="workspace-description"
                  rows={4}
                />
                <FieldDescription className="text-xs">
                  This description is for internal use and won&apos;t be
                  displayed publicly.
                </FieldDescription>
              </Field>
            </FieldGroup>
          </FieldSet>

          <div className="flex justify-end gap-3">
            <Button type="submit" className="w-full sm:w-auto">
              Enviar Candidatura
            </Button>
          </div>
        </form>
      </div>
    </>
  );
}
