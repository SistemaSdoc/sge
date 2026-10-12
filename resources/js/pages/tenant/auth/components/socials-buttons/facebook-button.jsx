import { FacebookIcon } from '@/components/facebook-icon';
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
      <FacebookIcon />

      <span className="truncate">Continuar com Facebook</span>
    </Button>
  );
}
