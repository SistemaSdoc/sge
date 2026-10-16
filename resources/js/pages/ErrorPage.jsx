import { Link } from '@inertiajs/react';

const ERROR_CONTENT = {
  401: {
    title: 'Precisa de iniciar sessão',
    description:
      'A sua sessão terminou ou ainda não iniciou sessão nesta conta. Não se preocupe — basta entrar novamente para continuar de onde parou.',
    action: { label: 'Iniciar sessão', href: '/login' },
  },
  403: {
    title: 'Sem permissão',
    description:
      'A página que tentou acessar não lhe é autorizada.',
    action: { label: 'Voltar ao início', href: '/' },
  },
  404: {
    title: 'Ops, não encontramos o que procura',
    description:
      ' O conteúdo que tentou acessar não está disponível.',
    action: { label: 'Voltar ao início', href: '/' },
  },
  422: {
    title: 'Algo nos dados não bate certo',
    description:
      'Alguns campos do formulário não foram preenchidos como esperado. Volte atrás, dê uma olhada e tente enviar novamente.',
    action: { label: 'Voltar', href: '/' },
  },
  429: {
    title: 'Calma, uma coisa de cada vez!',
    description:
      'Recebemos demasiados pedidos seus em pouco tempo. Aguarde uns segundos e tente novamente.',
    action: { label: 'Voltar ao início', href: '/' },
  },
  500: {
    title: 'Ups, algo correu mal do nosso lado',
    description:
      'Estamos trabalhando para resolver o problema.',
    action: { label: 'Voltar ao início', href: '/' },
  },
  503: {
    title: 'Voltamos já!',
    description:
      'Estamos fazendo uma manutenção rápida para melhorar o sistema.',
    action: { label: 'Voltar ao início', href: '/' },
  },
};

export default function ErrorPage({ status, homeUrl }) {
  const content = ERROR_CONTENT[status] ?? ERROR_CONTENT[404];
  const href = homeUrl ?? content.action.href;

  return (
    <div className="flex min-h-screen items-center justify-center bg-muted/30 px-6 py-16">
      <div className="w-full max-w-md rounded-2xl border border-border bg-background p-10 text-center shadow-lg">
        <div className="mx-auto mb-2 text-[72px] font-extrabold leading-none text-primary">
          {status}
        </div>

        <h1 className="mt-2 text-2xl font-semibold text-foreground">
          {content.title}
        </h1>

        <p className="mt-3 text-base leading-relaxed text-muted-foreground">
          {content.description}
        </p>

        <Link
          href={href}
          className="mt-8 inline-flex items-center justify-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90"
        >
          {content.action.label}
        </Link>
      </div>
    </div>
  );
}