import type { InertiaLinkProps } from '@inertiajs/react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { Camera } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import {
  academic,
  security,
  show,
  updateAvatar,
} from '@/actions/App/Http/Controllers/Tenant/UserProfileController';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';

type ProfileNavItem = {
  key: string;
  title: string;
  href: NonNullable<InertiaLinkProps['href']>;
};

export default function UserProfileLayout({ children }: PropsWithChildren) {
  const avatarForm = useForm<{ avatar: File | null }>({ avatar: null });
  const { isCurrentUrl } = useCurrentUrl();
  const getInitials = useInitials();
  const { user } = usePage<{
    user: {
      id: string;
      nome: string;
      email: string;
      avatar?: string | null;
      avatarUrl?: string | null;
      isAluno?: boolean;
    };
  }>().props;

  const handleAvatarChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const avatar = event.target.files?.[0];

    if (!avatar) {
      return;
    }

    avatarForm.transform(() => ({ avatar }));

    avatarForm.post(updateAvatar(user.id).url, {
      forceFormData: true,
      preserveScroll: true,
    });
  };

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
    <div className="mx-auto w-full max-w-7xl px-4 py-6">
      <header className="mx-auto mb-10 w-full max-w-7xl overflow-hidden border bg-card">
        <div
          className="relative h-36 bg-muted bg-cover bg-center md:h-48"
          style={
            user.avatarUrl
              ? { backgroundImage: `url(${user.avatarUrl})` }
              : undefined
          }
        >
          <div className="absolute inset-0 bg-black/35" />
          <div className="absolute inset-x-0 bottom-0 h-20 bg-linear-to-t from-black/55 to-transparent" />
        </div>

        <div className="relative px-5 pb-5">
          <div className="-mt-10 flex flex-col gap-4 sm:-mt-12 sm:flex-row sm:items-end">
            <div className="relative z-10 rounded-full bg-card p-1">
              <div className="relative">
                <Avatar className="size-20">
                  <AvatarImage
                    src={user.avatarUrl ?? undefined}
                    alt={user.nome}
                  />
                  <AvatarFallback>{getInitials(user.nome)}</AvatarFallback>
                </Avatar>

                <label
                  htmlFor="profile-avatar"
                  className="absolute right-0 bottom-0 inline-flex size-8 cursor-pointer items-center justify-center rounded-full border-2 border-card bg-primary text-primary-foreground shadow-sm transition hover:bg-primary/90"
                  title="Mudar foto do perfil"
                >
                  <Camera className="size-4" />
                  <span className="sr-only">Mudar foto do perfil</span>
                </label>
                <input
                  id="profile-avatar"
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  className="sr-only"
                  onChange={handleAvatarChange}
                  disabled={avatarForm.processing}
                />
              </div>
            </div>

            <div className="pb-1 sm:pb-2">
              <p className="text-base font-semibold">{user.nome}</p>
              <p className="text-sm text-muted-foreground">{user.email}</p>
            </div>
          </div>
        </div>
      </header>

      <div className="mx-auto flex w-full max-w-7xl flex-col lg:flex-row lg:space-x-12">
        <aside className="order-1 mb-8 w-full max-w-xl lg:order-1 lg:mb-0 lg:w-48">
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

        <div className="order-2 mx-auto w-full flex-1 md:max-w-7xl lg:order-2">
          <section className="max-w-7xl space-y-12">{children}</section>
        </div>
      </div>
    </div>
  );
}
