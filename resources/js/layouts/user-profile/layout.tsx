import { Link, usePage } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import {
  academic,
  security,
  show,
} from '@/actions/App/Http/Controllers/Tenant/UserProfileController';
import { Button } from '@/components/ui/button';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';

type ProfileNavItem = {
  key: string;
  title: string;
  href: NonNullable<InertiaLinkProps['href']>;
};

export default function UserProfileLayout({ children }: PropsWithChildren) {
  const { isCurrentUrl } = useCurrentUrl();
  const { user } = usePage<{
    user: { id: string; isAluno?: boolean };
  }>().props;

  const sidebarNavItems: ProfileNavItem[] = [
    {
      key: 'personal-data',
      title: 'Dados pessoais',
      href: show(user.id),
    },
    ...(user.isAluno
      ? [
          {
            key: 'academic-data',
            title: 'Dados académicos',
            href: academic(user.id),
          },
        ]
      : []),
    {
      key: 'security',
      title: 'Segurança',
      href: security(user.id),
    },
  ];

  return (
    <div className="px-4 py-6">
      <div className="flex flex-col lg:flex-row lg:space-x-12">
        <aside className="order-2 w-full max-w-xl lg:order-1 lg:w-48">
          <h2 className="mt-6 mb-2 text-sm font-medium">Navegação</h2>

          <nav
            className="flex flex-col space-y-1 space-x-0"
            aria-label="Perfil"
          >
            {sidebarNavItems.map((item) => (
              <Button
                key={item.key}
                size="sm"
                variant="ghost"
                asChild
                className={cn('w-full justify-start', {
                  'bg-muted': isCurrentUrl(item.href),
                })}
              >
                <Link href={item.href}>{item.title}</Link>
              </Button>
            ))}
          </nav>
        </aside>

        <div className="order-1 mx-auto w-full flex-1 md:max-w-7xl lg:order-2">
          <section className="max-w-7xl space-y-12">{children}</section>
        </div>
      </div>
    </div>
  );
}
