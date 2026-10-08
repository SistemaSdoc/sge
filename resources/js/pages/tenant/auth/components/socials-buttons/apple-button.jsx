import { Spinner } from '@/components/spinner';
import { Button } from '@/components/ui/button';

/**
 * Botão de login com Apple
 *
 * Redireciona '/auth/apple/redirect' para OAuth
 */
export function AppleButton({ isLoading, onClick, disabled }) {
  return (
    <Button
      variant="outline"
      type="button"
      onClick={onClick}
      disabled={disabled || isLoading}
      className="w-full hover:cursor-pointer"
    >
      {isLoading && <Spinner className="mr-2" />}

      {/* Apple Icon */}
      <svg aria-hidden="true" viewBox="0 0 24 24" className="size-4 shrink-0">
        <path
          fill="currentColor"
          d="M12.15 6.9c-.95 0-2.42-1.08-3.96-1.04-2.04.03-3.91 1.18-4.96 3.01-2.12 3.68-.55 9.1 1.52 12.09 1.01 1.45 2.21 3.09 3.79 3.04 1.52-.07 2.09-.99 3.94-.99 1.83 0 2.35.99 3.96.95 1.64-.03 2.68-1.48 3.68-2.95 1.16-1.69 1.64-3.33 1.66-3.42-.04-.01-3.18-1.22-3.22-4.86-.03-3.04 2.48-4.49 2.6-4.56-1.43-2.09-3.62-2.32-4.39-2.38-2-.16-3.68 1.09-4.61 1.09zM15.53 3.83c.84-1.01 1.4-2.43 1.25-3.83-1.21.05-2.66.81-3.53 1.82-.78.9-1.45 2.34-1.27 3.71 1.34.1 2.72-.69 3.56-1.7"
        />
      </svg>

      <span className="truncate">Continuar com a Apple</span>
    </Button>
  );
}
