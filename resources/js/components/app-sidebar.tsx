import { Link } from '@inertiajs/react';
import {
  BoxSelectIcon,
  FileTextIcon,
  GraduationCapIcon,
  LayersIcon,
  LayoutGrid,
  ShieldCheckIcon,
  Users,
} from 'lucide-react';
import { index as indexAcessos } from '@/actions/App/Http/Controllers/AccessManagementController';
import { index as indexAlunos } from '@/actions/App/Http/Controllers/AlunoController';
import { index as indexAvisos } from '@/actions/App/Http/Controllers/AvisoController';
import { index as indexClasses } from '@/actions/App/Http/Controllers/ClasseController';
import { index as indexCursos } from '@/actions/App/Http/Controllers/CursosController';
import { index as indexGrupos } from '@/actions/App/Http/Controllers/GrupoPapController';
import { index as indexInscricoes } from '@/actions/App/Http/Controllers/InscricaoController';
import { index as indexInstituicoes } from '@/actions/App/Http/Controllers/InstituicaoController';
import { indexCursos as indexPautas } from '@/actions/App/Http/Controllers/PautaController';
import { index as indexProfessores } from '@/actions/App/Http/Controllers/ProfessorController';
import { index as indexTurmas } from '@/actions/App/Http/Controllers/TurmaController';
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

const navGroups = [
  {
    label: 'Plataforma',
    items: [
      { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
      {
        title: 'Instituições',
        href: indexInstituicoes().url,
        icon: LayersIcon,
      },
      { title: 'Cursos', href: indexCursos().url, icon: LayersIcon },
      { title: 'Classes', href: indexClasses().url, icon: GraduationCapIcon },
      { title: 'Turnos', href: indexTurnos().url, icon: LayersIcon },
      { title: 'Turmas', href: indexTurmas().url, icon: LayersIcon },
      { title: 'Pautas', href: indexPautas().url, icon: FileTextIcon },
      { title: 'Inscrições', href: indexInscricoes().url, icon: LayersIcon },
    ],
  },
  {
    label: 'Usuários',
    items: [
      { title: 'Professores', href: indexProfessores().url, icon: Users },
      { title: 'Alunos', href: indexAlunos().url, icon: Users },
      { title: 'Gerir Acessos', href: indexAcessos().url, icon: ShieldCheckIcon },
    ],
  },
  {
    label: 'PAP',
    items: [{ title: 'Grupos PAP', href: indexGrupos().url, icon: Users }],
  },
  {
    label: 'Comunicação',
    items: [{ title: 'Avisos', href: indexAvisos().url, icon: Users }],
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
    <Sidebar collapsible="icon" variant="sidebar">
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
        <NavMain groups={navGroups} />
      </SidebarContent>

      <SidebarFooter>
        <NavFooter items={footerNavItems} className="mt-auto" />
        <NavUser />
      </SidebarFooter>
    </Sidebar>
  );
}
