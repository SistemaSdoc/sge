import { Link } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { useIsMobile } from '@/hooks/use-mobile';
import {
  Empty,
  EmptyContent,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty';

/**
 * EmptyState Component - Super Responsivo
 *
 * Componente reutilizável e elegante para exibir estados vazios em:
 * - Tabelas e listas
 * - Cards e grids
 * - Seções sem conteúdo
 *
 * Responsividade automática:
 * - Desktop: exibe completo com animações
 * - Mobile: compacto e otimizado para tela pequena
 *
 * @param {React.ReactNode} icon - Ícone a exibir (lucide-react)
 * @param {string} title - Título principal do empty state
 * @param {string} description - Descrição/mensagem adicional
 * @param {Object} action - Configuração do botão de ação
 * @param {string} action.label - Texto do botão
 * @param {string} action.href - Link do botão (para Link)
 * @param {function} action.onClick - Função callback (para button simples)
 * @param {string} action.variant - Variante do botão (default, outline, ghost, etc)
 * @param {Object[]} secondaryActions - Array de ações secundárias (links)
 * @param {string} variant - Variante do componente (default, compact, minimal, table)
 * @param {string} className - Classes CSS adicionais
 */
export function EmptyState({
  icon: Icon,
  title = 'Nenhum dado disponível',
  description = 'Parece que ainda não há nada por aqui',
  action,
  secondaryActions = [],
  variant = 'default',
  className,
}) {
  const isMobile = useIsMobile();
  const hasAction = action && (action.href || action.onClick);

  // Ajusta variante no mobile para ser sempre compacta
  const effectiveVariant =
    isMobile && variant !== 'minimal' ? 'compact' : variant;

  const variantClasses = {
    default: isMobile ? 'py-12 gap-5' : 'py-16 gap-6',
    compact: isMobile ? 'py-6 gap-3' : 'py-8 gap-4',
    minimal: 'py-4 gap-3',
    table: isMobile ? 'py-8 gap-3' : 'py-12 gap-4',
  };

  const contentGapClasses = {
    default: 'gap-3',
    compact: 'gap-2',
    minimal: 'gap-1.5',
    table: 'gap-2',
  };

  const titleClasses = {
    default: isMobile ? 'text-sm font-semibold' : 'text-base font-semibold',
    compact: 'text-sm font-medium',
    minimal: 'text-xs font-medium',
    table: isMobile ? 'text-xs font-medium' : 'text-sm font-medium',
  };

  const descriptionClasses = {
    default: isMobile ? 'text-xs' : 'text-sm',
    compact: 'text-xs',
    minimal: 'text-xs',
    table: 'text-xs',
  };

  const iconSizeClasses = {
    default: isMobile ? 'size-5' : 'size-6',
    compact: 'size-5',
    minimal: 'size-4',
    table: isMobile ? 'size-4' : 'size-5',
  };

  const buttonSizeClasses = {
    default: isMobile ? 'xs' : 'sm',
    compact: isMobile ? 'xs' : 'sm',
    minimal: 'xs',
    table: isMobile ? 'xs' : 'sm',
  };

  return (
    <Empty className={`${variantClasses[effectiveVariant]} ${className || ''}`}>
      <style jsx>{`
        @keyframes slideInFade {
          from {
            opacity: 0;
            transform: translateY(12px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }

        @keyframes float {
          0%,
          100% {
            transform: translateY(0px);
          }
          50% {
            transform: translateY(-8px);
          }
        }

        [data-slot='empty'] {
          animation: slideInFade 0.5s ease-out;
        }

        [data-slot='empty-icon'] {
          animation: float 3s ease-in-out infinite;
        }

        @media (max-width: 767px) {
          [data-slot='empty-icon'] {
            animation: none;
          }
        }
      `}</style>

      <EmptyHeader>
        {Icon && (
          <EmptyMedia variant="icon">
            <div className="text-secondary">
              <Icon
                className={`${iconSizeClasses[effectiveVariant]} transition-all`}
                strokeWidth={1.5}
              />
            </div>
          </EmptyMedia>
        )}
        <EmptyContent className={contentGapClasses[effectiveVariant]}>
          <EmptyTitle className={titleClasses[effectiveVariant]}>
            {title}
          </EmptyTitle>

          <EmptyDescription className={descriptionClasses[effectiveVariant]}>
            {description}
          </EmptyDescription>
        </EmptyContent>
      </EmptyHeader>

      {(hasAction || secondaryActions.length > 0) && (
        <div
          className={`flex flex-col items-center gap-3 ${effectiveVariant === 'minimal' ? 'gap-2' : ''}`}
          style={{
            animation: 'slideInFade 0.5s ease-out 0.2s both',
          }}
        >
          {hasAction &&
            (action.href ? (
              <Button
                asChild
                variant={action.variant || 'default'}
                size={buttonSizeClasses[effectiveVariant]}
                className="w-full max-w-xs md:w-auto"
              >
                <Link href={action.href}>{action.label}</Link>
              </Button>
            ) : (
              <Button
                onClick={action.onClick}
                variant={action.variant || 'default'}
                size={buttonSizeClasses[effectiveVariant]}
                className="w-full max-w-xs md:w-auto"
              >
                {action.label}
              </Button>
            ))}

          {secondaryActions.length > 0 && (
            <div className="flex max-w-xs flex-wrap items-center justify-center gap-2 md:max-w-none">
              {secondaryActions.map((secondary, idx) => (
                <Button
                  key={idx}
                  asChild
                  variant="ghost"
                  size={buttonSizeClasses[effectiveVariant]}
                  className="text-center text-muted-foreground hover:text-foreground"
                >
                  <Link href={secondary.href}>{secondary.label}</Link>
                </Button>
              ))}
            </div>
          )}
        </div>
      )}
    </Empty>
  );
}
