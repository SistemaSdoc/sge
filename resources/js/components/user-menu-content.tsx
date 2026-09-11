import { Link, router, usePage } from '@inertiajs/react';
import { LogOut, Settings2 } from 'lucide-react';
import { destroy as centralLogout } from '@/actions/App/Http/Controllers/Central/Auth/AuthenticatedSessionController';
import { edit as centralProfileEdit } from '@/actions/App/Http/Controllers/Central/Settings/ProfileController';
import { edit } from '@/actions/App/Http/Controllers/Tenant/Settings/ProfileController';
import {
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { logout as tenantLogout } from '@/routes/tenant';
import type { User } from '@/types';

type Props = {
  user: User;
};

export function UserMenuContent({ user }: Props) {
  const cleanup = useMobileNavigation();
  const { isTenant } = usePage<{ isTenant: boolean }>().props;
  const profileEdit = isTenant ? edit : centralProfileEdit;
  const logout = isTenant ? tenantLogout : centralLogout;

  const handleLogout = () => {
    cleanup();
    router.flushAll();
  };

  return (
    <>
      <DropdownMenuLabel className="p-0 font-normal">
        <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
          <UserInfo user={user} showEmail={true} />
        </div>
      </DropdownMenuLabel>

      <DropdownMenuSeparator />

      <DropdownMenuGroup>
        <DropdownMenuItem asChild>
          <Link
            className="block w-full cursor-pointer"
            href={profileEdit()}
            prefetch
            onClick={cleanup}
          >
            <Settings2 className="mr-2" />
            Configurações
          </Link>
        </DropdownMenuItem>
      </DropdownMenuGroup>

      <DropdownMenuSeparator />

      <DropdownMenuItem asChild>
        <Link
          className="block w-full cursor-pointer"
          href={logout()}
          as="button"
          onClick={handleLogout}
          data-test="logout-button"
        >
          <LogOut className="mr-2" />
          Terminar sessão
        </Link>
      </DropdownMenuItem>
    </>
  );
}
