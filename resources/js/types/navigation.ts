import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

export type BreadcrumbItem = {
  title: string;
  href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
  key: string;
  title: string;
  href: NonNullable<InertiaLinkProps['href']>;
  icon?: string;
  isActive?: boolean;
};

export interface LocalNavItem {
  title: string;
  href: string;
  icon?: LucideIcon;
}
