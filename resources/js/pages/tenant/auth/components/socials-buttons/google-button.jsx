import { GoogleIcon } from '@/components/google-icon';
import { Spinner } from '@/components/spinner';
import { Button } from '@/components/ui/button';

/**
 * Botão de login com Google
 *
 * Redireciona para '/auth/google/redirect' do central domain para a autenticação OAuth Google. O central domain redireciona de volta para o tenant com um código allowlisted de sucesso ou falha.
 */
export function GoogleButton({ isLoading, onClick, disabled }) {
  return (
    <Button
      variant="outline"
      type="button"
      onClick={onClick}
      disabled={disabled || isLoading}
      className="w-full hover:cursor-pointer"
    >
      {isLoading && <Spinner className="mr-2" />}

      {/* Google Icon */}
      <GoogleIcon />

      <span className="truncate">Continuar com Google</span>
    </Button>
  );
}
