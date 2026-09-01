import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
  Empty,
  EmptyContent,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty';
import { Spinner } from '@/components/spinner';
import { Sparkles } from 'lucide-react';

const states = {
  pending: {
    label: 'A preparar',
    title: 'O seu espaço está quase pronto',
    description:
      'Estamos a preparar tudo para que possa começar a utilizar a sua conta. Não precisa de fazer nada por agora.',
    eta: 'A configuração começa em breve',
  },
  provisioning: {
    label: 'Em preparação',
    title: 'Estamos a preparar o seu espaço',
    description:
      'Está tudo a decorrer normalmente. Pode fechar esta página e voltar mais tarde — o seu espaço ficará disponível assim que estiver pronto.',
    eta: 'Normalmente fica pronto em poucos minutos',
  },
  active: {
    label: 'Pronto',
    title: 'Está tudo pronto',
    description:
      'O seu espaço foi preparado com sucesso. Já pode começar a utilizar a sua conta.',
    eta: 'Pode começar agora',
  },
};

export default function Page() {
  const [state, setState] = useState('pending');
  const current = states[state];
  const ready = state === 'active';

  return (
    <section className="mx-auto flex min-h-[calc(100vh-65px)] w-full max-w-3xl items-center justify-center px-4 py-10 sm:px-6 sm:py-14">
      <Empty className="gap-6 p-0">
        <EmptyHeader className="max-w-xl gap-4">
          <EmptyMedia variant="icon">
            <Sparkles aria-hidden="true" />
          </EmptyMedia>
          <EmptyTitle>{current.title}</EmptyTitle>
          <EmptyDescription>{current.description}</EmptyDescription>
        </EmptyHeader>
        <EmptyContent className="max-w-md gap-5">
          <div className="w-full border border-border bg-card px-5 py-4 text-left">
            <p className="text-sm font-medium">{current.eta}</p>
            <p className="mt-1 text-xs leading-5 text-muted-foreground">
              Pode voltar a esta página quando quiser.
            </p>
          </div>

          <Button asChild className="h-11 w-full sm:w-auto">
            <a href="mailto:suporte@stanclay.app">Falar com o suporte</a>
          </Button>

          <p className="pt-5 text-xs text-muted-foreground">
            {/** acme.stanclay.app */}
          </p>
        </EmptyContent>
      </Empty>
    </section>
  );
}
