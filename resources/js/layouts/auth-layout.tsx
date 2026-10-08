import { Link, usePage } from '@inertiajs/react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

const dotTexture = {
  backgroundColor: 'var(--background)',
  backgroundImage:
    'radial-gradient(color-mix(in oklab, var(--foreground) 14%, transparent) 0.7px, transparent 0.9px), radial-gradient(color-mix(in oklab, var(--foreground) 8%, transparent) 0.6px, transparent 0.8px)',
  backgroundSize: '3px 3px, 5px 5px',
  backgroundPosition: '0 0, 1px 2px',
};

const cornerMarkOffset = 'calc(var(--auth-gap) - 3px)';
const cornerMarks = [
  { key: 'top-left', top: cornerMarkOffset, left: cornerMarkOffset },
  { key: 'top-right', top: cornerMarkOffset, right: cornerMarkOffset },
  { key: 'bottom-left', bottom: cornerMarkOffset, left: cornerMarkOffset },
  { key: 'bottom-right', bottom: cornerMarkOffset, right: cornerMarkOffset },
];

const mobileFormStyles = `
  .auth-split-page {
    --auth-gap: clamp(12px, 3.5vw, 32px);
    padding: var(--auth-gap);
  }

  .auth-split-form {
    padding: 32px 32px calc(var(--auth-gap) + 33px);
    min-height: calc(100vh - var(--auth-gap) - 1px);
    min-height: calc(100dvh - var(--auth-gap) - 1px);
  }

  @media (min-width: 768px) {
    .auth-split-form {
      padding: 32px;
      min-height: 0;
    }
  }
`;

export default function AuthLayout({
  children,
  title,
  description,
}: AuthLayoutProps) {
  const { instituicao } = usePage().props as {
    instituicao?: { logo_url?: string; nome?: string };
  };

  return (
    <div className="auth-split-page relative flex min-h-screen w-full overflow-hidden bg-background text-foreground">
      <style>{mobileFormStyles}</style>
      <span
        aria-hidden="true"
        className="absolute inset-x-0 h-px bg-border"
        style={{ top: 'var(--auth-gap)' }}
      />
      <span
        aria-hidden="true"
        className="absolute inset-x-0 h-px bg-border"
        style={{ bottom: 'var(--auth-gap)' }}
      />
      <span
        aria-hidden="true"
        className="absolute inset-y-0 w-px bg-border"
        style={{ left: 'var(--auth-gap)' }}
      />
      <span
        aria-hidden="true"
        className="absolute inset-y-0 w-px bg-border"
        style={{ right: 'var(--auth-gap)' }}
      />

      <aside className="relative z-10 flex flex-1 flex-col border border-border bg-background md:flex-row">
        <section
          className="auth-split-form flex items-center justify-center"
          style={{ flex: '1 1 auto', minWidth: 0 }}
        >
          <div className="w-full max-w-90">
            <Link
              href={home()}
              className="mb-5 flex min-h-8 items-center justify-center gap-2 rounded-sm outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-4 focus-visible:ring-offset-background"
              aria-label={instituicao?.nome ?? 'SGE'}
            >
              {instituicao?.logo_url ? (
                <img
                  src={instituicao.logo_url}
                  alt={instituicao.nome ?? 'Logo da instituição'}
                  className="max-h-8 max-w-36 object-contain"
                />
              ) : (
                <>
                  <span className="flex size-6 items-center justify-center rounded-md bg-primary text-xs font-semibold text-primary-foreground">
                    {instituicao?.nome?.charAt(0) ?? 'S'}
                  </span>
                  <span className="text-xs font-medium text-foreground">
                    {instituicao?.nome ?? 'SGE'}
                  </span>
                </>
              )}
            </Link>

            <header className="mb-5 space-y-1 text-center">
              <h1 className="text-sm font-semibold text-foreground">{title}</h1>
              {description && (
                <p className="text-xs leading-5 text-muted-foreground">
                  {description}
                </p>
              )}
            </header>
            {children}
          </div>
        </section>

        <aside
          className="flex shrink-0 flex-col gap-10 border-t border-border p-12 md:w-[31%] md:gap-0 md:border-t-0 md:border-l"
          style={dotTexture}
        >
          <div className="flex flex-1 items-center justify-center">
            <figure className="max-w-xs text-center">
              <div
                className="mb-4 text-lg leading-none text-foreground"
                aria-label="5 de 5 estrelas"
              >
                <span aria-hidden="true">★★★★★</span>
              </div>
              <blockquote className="text-lg leading-7 font-semibold text-foreground">
                A gestão da escola começa com informação clara.
              </blockquote>
              <figcaption className="mt-5 flex items-center justify-center gap-2.5 text-left">
                <span
                  aria-hidden="true"
                  className="flex size-7 items-center justify-center rounded-full bg-primary text-[10px] font-semibold text-primary-foreground"
                >
                  T
                </span>
                <span>
                  <span className="block text-xs font-medium text-foreground">
                    Tunganetu
                  </span>
                  <span className="block text-[10px] text-muted-foreground">
                    Gestão escolar
                  </span>
                </span>
              </figcaption>
            </figure>
          </div>

          <footer className="text-left">
            <p className="mb-3 text-xs text-muted-foreground">
              Pensado para a comunidade escolar
            </p>
            <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs font-semibold text-muted-foreground">
              <span>Alunos</span>
              <span>Turmas</span>
              <span>Avaliações</span>
              <span>Gestão</span>
            </div>
          </footer>
        </aside>
      </aside>

      {cornerMarks.map(({ key, ...position }) => (
        <span
          key={key}
          aria-hidden="true"
          className="absolute z-20 rounded-full border border-border bg-background"
          style={{ width: 7, height: 7, ...position }}
        />
      ))}
    </div>
  );
}
