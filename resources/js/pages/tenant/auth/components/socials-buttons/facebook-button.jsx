import { Spinner } from '@/components/spinner';
import { Button } from '@/components/ui/button';

/**
 * Botão de login com Facebook
 *
 * Redireciona '/auth/facebook/redirect' para OAuth
 */
export function FacebookButton({ isLoading, onClick, disabled }) {
  return (
    <Button
      variant="outline"
      type="button"
      onClick={onClick}
      disabled={disabled || isLoading}
      className="w-full hover:cursor-pointer"
    >
      {isLoading && <Spinner className="mr-2" />}

      {/* Facebook Icon */}
      <svg aria-hidden="true" viewBox="0 0 24 24" className="size-4 shrink-0">
        <path
          fill="#1877F2"
          d="M13.5 21v-8h2.7l.4-3.1h-3.1V8c0-.9.3-1.6 1.6-1.6H17V3.6c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3v2.1H7.5V13h2.8v8a9.5 9.5 0 1 1 3.2 0z"
        />
      </svg>

      <span className="truncate">Continuar com Facebook</span>
    </Button>
  );
}
