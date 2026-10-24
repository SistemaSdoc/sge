import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowUpLeft } from 'lucide-react';
import {
  index,
  updatePassword,
} from '@/actions/App/Http/Controllers/Tenant/UserController';
import { Spinner } from '@/components/spinner';
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

export default function Password({ user, isSelf }) {
  const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
  });

  const { data, setData, errors, processing } = form;

  const submit = (event) => {
    event.preventDefault();

    form.put(updatePassword(user.id).url, {
      preserveScroll: true,
      onFinish: () =>
        form.reset('current_password', 'password', 'password_confirmation'),
    });
  };

  return (
    <>
      <Head title="Alterar palavra-passe" />

      <div className="mx-auto w-full max-w-sm p-4 md:max-w-md md:p-6 lg:max-w-2xl">
        <form onSubmit={submit}>
          <Card>
            <CardHeader className="border-b">
              <CardTitle>Alterar palavra-passe</CardTitle>
              <CardDescription>
                Defina uma nova palavra-passe para {user.nome} ({user.email}).
              </CardDescription>
            </CardHeader>

            <CardContent>
              <FieldGroup>
                <FieldSet>
                  {isSelf && (
                    <Field>
                      <FieldLabel htmlFor="current_password">
                        Palavra-passe actual
                      </FieldLabel>
                      <Input
                        id="current_password"
                        type="password"
                        autoComplete="current-password"
                        disabled={processing}
                        value={data.current_password}
                        onChange={(e) =>
                          setData('current_password', e.target.value)
                        }
                      />
                      {errors.current_password && (
                        <FieldError>{errors.current_password}</FieldError>
                      )}
                    </Field>
                  )}

                  <Field>
                    <FieldLabel htmlFor="password">
                      Nova palavra-passe
                    </FieldLabel>
                    <Input
                      id="password"
                      type="password"
                      autoComplete="new-password"
                      disabled={processing}
                      value={data.password}
                      onChange={(e) => setData('password', e.target.value)}
                    />
                    {errors.password && (
                      <FieldError>{errors.password}</FieldError>
                    )}
                  </Field>

                  <Field>
                    <FieldLabel htmlFor="password_confirmation">
                      Confirmar nova palavra-passe
                    </FieldLabel>
                    <Input
                      id="password_confirmation"
                      type="password"
                      autoComplete="new-password"
                      disabled={processing}
                      value={data.password_confirmation}
                      onChange={(e) =>
                        setData('password_confirmation', e.target.value)
                      }
                    />
                  </Field>

                  <Field>
                    <Button
                      type="submit"
                      disabled={processing}
                      className="hover:cursor-pointer"
                    >
                      {processing ? (
                        <>
                          <Spinner />
                          Salvando a palavra-passe
                        </>
                      ) : (
                        'Salvar palavra-passe'
                      )}
                    </Button>

                    <Button asChild variant="outline" disabled={processing}>
                      <Link href={index().url}>
                        <ArrowUpLeft />
                        Voltar a lista de usuários
                      </Link>
                    </Button>
                  </Field>
                </FieldSet>
              </FieldGroup>
            </CardContent>
          </Card>
        </form>
      </div>
    </>
  );
}