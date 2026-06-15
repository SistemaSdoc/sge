import { Link } from '@inertiajs/react';
import {
  BoxSelectIcon,
  FileTextIcon,
  GraduationCapIcon,
  LayersIcon,
  LayoutGrid,
  Users,
} from 'lucide-react';
import { index as indexClasses } from '@/actions/App/Http/Controllers/ClasseController';
import { index as indexCursos } from '@/actions/App/Http/Controllers/CursosController';
import { index as indexGrupos } from '@/actions/App/Http/Controllers/GrupoPapController';
import { index as indexInstituicoes } from '@/actions/App/Http/Controllers/InstituicaoController';
import { index as indexProfessores } from '@/actions/App/Http/Controllers/ProfessorController';
import { index as indexTurnos } from '@/actions/App/Http/Controllers/TurnoController';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: dashboard(),
    icon: LayoutGrid,
  },

  {
    title: 'Instituições',
    href: indexInstituicoes().url,
    icon: LayersIcon,
  },

  {
    title: 'Cursos',
    href: indexCursos().url,
    icon: LayersIcon,
  },

  {
    title: 'Classes',
    href: indexClasses().url,
    icon: GraduationCapIcon,
  },

  {
    title: 'Turnos',
    href: indexTurnos().url,
    icon: LayersIcon,
  },

  {
    title: 'Turmas',
    href: `/turmas`,
    icon: LayersIcon,
  },

  {
    title: 'Pautas',
    href: `/pautas/cursos`,
    icon: FileTextIcon,
  },

  {
    title: 'Professores',
    href: indexProfessores().url,
    icon: Users,
  },

  {
    title: 'Grupos PAP',
    href: indexGrupos().url,
    icon: Users,
  },

  {
    title: 'Inscrições',
    href: `/inscricoes`,
    icon: LayersIcon,
  },
  
  {
    title: 'Alunos',
    href: `/alunos`,
    icon: Users,
  },

  {
    title: 'Avisos',
    href: `/avisos`,
    icon: Users,
  },
];

const footerNavItems: NavItem[] = [
  {
    title: 'Outro item 1',
    href: '/dashboard',
    icon: BoxSelectIcon,
  },
  {
    title: 'Outro item 2',
    href: '/dashboard',
    icon: BoxSelectIcon,
  },
];

export function AppSidebar() {
  return (
    <Sidebar collapsible="icon" variant="inset">
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton size="lg" asChild>
              <Link href={dashboard()} prefetch>
                <AppLogo />
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>

      <SidebarContent>
        <NavMain items={mainNavItems} />
      </SidebarContent>

      <SidebarFooter>
        <NavFooter items={footerNavItems} className="mt-auto" />
        <NavUser />
      </SidebarFooter>
    </Sidebar>
  );
}
