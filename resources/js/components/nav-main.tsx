import { Link } from '@inertiajs/react';
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { resolveIcon } from '@/lib/icon-registry';
import { cn } from '@/lib/utils';
import type { NavItem } from '@/types';

interface NavGroup {
  label: string;
  items: NavItem[];
}

export function NavMain({ groups = [] }: { groups: NavGroup[] }) {
  const { isCurrentUrl } = useCurrentUrl();

  return (
    <>
      {groups.map((group) => (
        <SidebarGroup key={group.label} className="px-2 py-0">
          <SidebarGroupLabel className="font-medium">
            {group.label}
          </SidebarGroupLabel>

          <SidebarMenu>
            {group.items.map((item) => {
              const Icon = item.icon ? resolveIcon(item.icon) : null;

              return (
                <SidebarMenuItem key={item.key}>
                  <SidebarMenuButton
                    asChild
                    isActive={isCurrentUrl(item.href)}
                    tooltip={{ children: item.title }}
                  >
                    <Link
                      href={item.href}
                      prefetch
                      className="[&_span]:text-sidebar-foreground hover:[&_span]:font-bold hover:[&_span]:text-sidebar-foreground hover:[&>svg]:text-ring"
                    >
                      {Icon && <Icon />}
                      <span
                        className={cn(
                          isCurrentUrl(item.href) && 'font-bold',
                          'font-bold',
                        )}
                      >
                        {item.title}
                      </span>
                    </Link>
                  </SidebarMenuButton>
                </SidebarMenuItem>
              );
            })}
          </SidebarMenu>
        </SidebarGroup>
      ))}
    </>
  );
}
