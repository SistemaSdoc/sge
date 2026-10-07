import { Form, Head } from '@inertiajs/react';
import { update } from '@/actions/App/Http/Controllers/Tenant/CompletarPerfilController';
import { ArrowUpRight, BadgeCheck, CircleHelp } from 'lucide-react';
import { useState } from 'react';
import AddressSection from './components/address-section';
import IdentificationSection from './components/identification-section';
import LogoutConfirmationDialog from './components/logout-confirmation-dialog';
import PersonalDataSection from './components/personal-data-section';
import { Separator } from '@/components/ui/separator';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/spinner';

export default function Page({ profile }) {
  const [telefone, setTelefone] = useState(profile?.telefone ?? '');

  if (!profile) {
    return (
      <>
        <Head title="Dados do aluno" />
        <main className="mx-auto w-full max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
          <section className="grid gap-4 border-t-4 border-amber-500 py-6 sm:grid-cols-[auto_1fr]">
            <CircleHelp
              className="mt-1 size-5 text-amber-600"
              aria-hidden="true"
            />
            <div className="grid gap-2">
              <h1 className="text-xl font-semibold">
                Não encontrámos a sua matrícula
              </h1>
              <p className="max-w-2xl text-sm leading-6 text-muted-foreground">
                Os seus dados de aluno ainda não estão associados a esta conta.
                Contacte a secretaria da instituição para regularizar o registo.
              </p>
            </div>
          </section>
        </main>
      </>
    );
  }

  return (
    <>
      <Head title="Completar perfil" />

      <main className="mx-auto flex min-h-screen w-full max-w-6xl flex-col gap-7 px-4 py-7 sm:px-6 lg:px-8 lg:py-12">
        <header className="grid gap-5 border-b pb-7 sm:grid-cols-[1fr_auto] sm:items-end">
          <div className="grid gap-2">
            <h1 className="text-xl font-semibold tracking-normal">
              Complete os seus dados
            </h1>

            <p className="max-w text-sm leading-6 text-muted-foreground">
              Preencha os dados pessoais em falta para continuar a usar a
              plataforma. Este formulário é de carácter obrigatório e deve ser
              preenchido com informações corretas e actualizadas.
            </p>
          </div>
        </header>

        <Form
          {...update.form()}
          transform={(data) => ({ ...data, telefone })}
          options={{ preserveScroll: true }}
          className="grid flex-1 content-start gap-6 sm:gap-8"
        >
          {({ errors, processing }) => (
            <>
              <IdentificationSection profile={profile} />

              <Separator />

              <PersonalDataSection
                errors={errors}
                profile={profile}
                telefone={telefone}
                onTelefoneChange={(value) => setTelefone(value ?? '')}
              />

              <Separator />

              <AddressSection errors={errors} profile={profile} />

              <div className="flex flex-col gap-2 border-t pt-6 sm:flex-row sm:justify-end">
                <LogoutConfirmationDialog disabled={processing} />

                <Button
                  type="submit"
                  disabled={processing}
                  className="order-1 flex md:order-2"
                >
                  {processing ? (
                    <>
                      <Spinner />A salvar
                    </>
                  ) : (
                    'Salvar e continuar'
                  )}
                  <ArrowUpRight data-icon="inline-end" />
                </Button>
              </div>
            </>
          )}
        </Form>

        <footer className="mt-auto py-2 text-center">
          <p className="text-xs text-muted-foreground">
            Precisa de ajuda ou tem alguma dúvida? Contacte a direção da sua
            instituição.
          </p>
        </footer>
      </main>
    </>
  );
}

Page.layout = () => null;
