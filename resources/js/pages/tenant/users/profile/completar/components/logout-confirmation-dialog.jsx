import { router } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import { logout } from '@/routes/tenant';
import { Button } from '@/components/ui/button';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog';

export default function LogoutConfirmationDialog({ disabled = false }) {
  function endSession() {
    router.flushAll();
    router.post(logout().url);
  }

  return (
    <AlertDialog>
      <AlertDialogTrigger asChild>
        <Button
          type="button"
          variant="outline"
          disabled={disabled}
          className="order-2 flex w-full sm:w-auto md:order-1"
        >
          Terminar sessão
        </Button>
      </AlertDialogTrigger>

      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Terminar sessão?</AlertDialogTitle>

          <AlertDialogDescription>
            Os dados que ainda não foram guardados serão perdidos.
          </AlertDialogDescription>
        </AlertDialogHeader>

        <AlertDialogFooter>
          <AlertDialogCancel>Continuar a preencher</AlertDialogCancel>

          <AlertDialogAction variant="destructive" onClick={endSession}>
            Terminar sessão
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}
