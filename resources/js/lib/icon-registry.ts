import {
  Bell,
  BookOpen,
  Building2,
  ClipboardList,
  Clock4,
  FileText,
  GraduationCap,
  LayoutGrid,
  ShieldCheck,
  Users,
  CircleDashed,
  LayoutList,
  FileTextIcon,
  ReceiptText,
  CreditCard,
  ClipboardCheck,
  CalendarClock,
} from 'lucide-react';

import type { LucideIcon } from 'lucide-react';

export const iconRegistry: Record<string, LucideIcon> = {
  Bell,
  BookOpen,
  Building2,
  ClipboardList,
  Clock4,
  FileText,
  GraduationCap,
  LayoutGrid,
  ShieldCheck,
  Users,
  LayoutList,
  FileTextIcon,
  ReceiptText,
  CreditCard,
  ClipboardCheck,
  CalendarClock
};

export function resolveIcon(name: string): LucideIcon {
  if (!(name in iconRegistry)) {
    // Lança erro se não registrar o Icon em modo de desenvolvimento.
    if (import.meta.env.DEV) {
      throw new Error(
        `[icon-registry] Ícone "${name}" não registado.\n` +
          `- O ícone existe no Lucide? Verifica em https://lucide.dev/icons/?search=${name}\n` +
          `- Se existe: importa-o e adiciona-o ao iconRegistry em lib/icon-registry.ts\n` +
          `- Se não existe: corrige o nome no backend (SidebarMenuService.php)`,
      );
    }

    // Icon padrão (caso não seja definido o icon).
    return CircleDashed;
  }

  return iconRegistry[name];
}
