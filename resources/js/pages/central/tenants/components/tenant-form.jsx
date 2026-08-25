import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectLabel,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  InputGroup,
  InputGroupAddon,
  InputGroupInput,
} from '@/components/ui/input-group';
import { Spinner } from '@/components/spinner';
import { ArrowUpLeft } from 'lucide-react';

export function TenantForm({
  title,
  description,
  data,
  setData,
  errors,
  processing,
  submitFn,
  submitLabel = 'Adicionar',
  processingLabel = 'Processando...',
  can = {},
}) {
  const canSubmit = Boolean(can.create ?? true);

  const tipoOptions = [
    { value: 'colegio', label: 'Colégio' },
    { value: 'instituto', label: 'Instituto' },
  ];

  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-2xl">
      <form onSubmit={submitFn}>
        <Card className="overflow-visible">
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>
            <CardDescription>{description}</CardDescription>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                {/* Nome & Sigla */}
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <Field>
                    <FieldLabel htmlFor="nome">Nome da Instituição</FieldLabel>
                    <Input
                      id="nome"
                      type="text"
                      placeholder="Ex.: Escola Secundária de Luanda"
                      value={data.nome}
                      onChange={(e) => setData('nome', e.target.value)}
                    />
                    {errors.nome && <FieldError>{errors.nome}</FieldError>}
                  </Field>

                  <Field>
                    <FieldLabel htmlFor="sigla">Sigla</FieldLabel>
                    <Input
                      id="sigla"
                      type="text"
                      placeholder="Ex.: ESL"
                      value={data.sigla}
                      onChange={(e) =>
                        setData('sigla', e.target.value.toUpperCase())
                      }
                      maxLength="10"
                    />
                    {errors.sigla && <FieldError>{errors.sigla}</FieldError>}
                  </Field>
                </div>
                {/* Tipo & Subdomínio */}
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <Field>
                    <FieldLabel htmlFor="tipo">Tipo</FieldLabel>
                    <Select
                      value={data.tipo}
                      onValueChange={(value) => setData('tipo', value)}
                    >
                      <SelectTrigger className="w-full">
                        <SelectValue placeholder="Selecione o tipo" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectGroup>
                          <SelectLabel>Tipo de instituição</SelectLabel>
                          {tipoOptions.map((opt) => (
                            <SelectItem key={opt.value} value={opt.value}>
                              {opt.label}
                            </SelectItem>
                          ))}
                        </SelectGroup>
                      </SelectContent>
                    </Select>
                    {errors.tipo && <FieldError>{errors.tipo}</FieldError>}
                  </Field>

                  <Field>
                    <FieldLabel htmlFor="domain">Subdomínio</FieldLabel>
                    <InputGroup className="max-w-xs">
                      <InputGroupAddon className="font-normal text-foreground">
                        https://
                      </InputGroupAddon>
                      <InputGroupInput
                        id="domain"
                        type="text"
                        value={data.domain}
                        placeholder="Ex.: imcl"
                        onChange={(e) => setData('domain', e.target.value)}
                      />
                      <InputGroupAddon
                        align="inline-end"
                        className="font-normal text-foreground"
                      >
                        .sge.localhost
                      </InputGroupAddon>
                    </InputGroup>

                    {errors.domain && <FieldError>{errors.domain}</FieldError>}
                  </Field>
                </div>

                {/* Nome & Email do User */}
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <Field>
                    <FieldLabel htmlFor="user_nome">
                      Nome do usuário (Director)
                    </FieldLabel>
                    <Input
                      id="user_nome"
                      type="text"
                      placeholder="Ex.: João Silva"
                      value={data.user_nome}
                      onChange={(e) => setData('user_nome', e.target.value)}
                    />
                    {errors.user_nome && (
                      <FieldError>{errors.user_nome}</FieldError>
                    )}
                  </Field>

                  <Field>
                    <FieldLabel htmlFor="user_email">
                      Email do usuário (Director)
                    </FieldLabel>
                    <Input
                      id="user_email"
                      type="email"
                      placeholder="Ex.: email@example.com"
                      value={data.user_email}
                      onChange={(e) => setData('user_email', e.target.value)}
                    />
                    {errors.user_email && (
                      <FieldError>{errors.user_email}</FieldError>
                    )}
                  </Field>
                </div>

                {/* Submit & Back Button */}
                <Field>
                  <Button
                    type="submit"
                    disabled={processing || !canSubmit}
                    className="w-full"
                  >
                    {processing ? (
                      <>
                        <Spinner />
                        {processingLabel}
                      </>
                    ) : (
                      <>{submitLabel}</>
                    )}
                  </Button>

                  <Button
                    type="button"
                    disabled={processing}
                    variant={'outline'}
                    className="w-full"
                    onClick={() => window.history.back()}
                  >
                    <ArrowUpLeft />
                    Voltar
                  </Button>
                </Field>
              </FieldSet>
            </FieldGroup>
          </CardContent>
        </Card>
      </form>
    </div>
  );
}
