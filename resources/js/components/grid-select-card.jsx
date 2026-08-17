import { cn } from '@/lib/utils';

/**
 * GridSelectGrid
 * Wrapper para grids de cards com borda consistente
 *
 * @param {React.ReactNode} children - Os cards dentro do grid
 * @param {string} cols - Classes de grid (default: grid-cols-2 sm:grid-cols-3 md:grid-cols-4...)
 */
export function GridSelectGrid({
  children,
  cols = 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-7',
}) {
  return (
    <div className="overflow-hidden border border-foreground/10">
      <div className={cn('-mr-px -mb-px grid', cols)}>{children}</div>
    </div>
  );
}

/**
 * GridSelectCard
 * Card individual dentro do grid
 * Suporta tanto botões (onClick) como links (href via Link do Inertia)
 *
 * @param {boolean} active - Se o card está ativo
 * @param {boolean} dashed - Se a borda deve ser tracejada (ex: botão de adicionar)
 * @param {React.ReactNode} children - Conteúdo do card
 * @param {string} className - Classes adicionais
 * @param {...any} props - Atributos adicionais (onClick, etc)
 */
export function GridSelectCard({
  active,
  dashed,
  children,
  className,
  ...props
}) {
  return (
    <button
      type="button"
      className={cn(
        'cursor-pointer border-r border-b border-foreground/10 bg-card px-3 py-3 text-left text-card-foreground transition-colors active:bg-accent sm:px-4 sm:py-4',
        dashed && 'border-dashed text-muted-foreground',
        active
          ? 'bg-accent text-secondary'
          : 'hover:bg-accent hover:text-secondary',
        className,
      )}
      {...props}
    >
      {children}
    </button>
  );
}

/**
 * GridSelectLink
 * Card que funciona como Link do Inertia
 * Mantém a mesma estilização mas permitindo navegação via Link
 *
 * @param {React.Component} Link - Componente Link do Inertia
 * @param {boolean} active - Se o card está ativo
 * @param {string} href - URL de destino
 * @param {React.ReactNode} children - Conteúdo do card
 * @param {string} className - Classes adicionais
 * @param {...any} props - Atributos adicionais
 */
export function GridSelectLinkWrapper(Link) {
  return function GridSelectLink({
    active,
    href,
    children,
    className,
    ...props
  }) {
    return (
      <Link href={href} {...props}>
        <div
          className={cn(
            'cursor-pointer border-r border-b border-foreground/10 bg-card px-3 py-3 text-left text-card-foreground transition-colors hover:bg-accent hover:text-secondary active:bg-accent sm:px-4 sm:py-4',
            active
              ? 'bg-accent text-secondary'
              : 'hover:bg-accent hover:text-secondary',
            className,
          )}
        >
          {children}
        </div>
      </Link>
    );
  };
}
