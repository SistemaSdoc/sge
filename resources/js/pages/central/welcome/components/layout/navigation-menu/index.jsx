import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import {create} from '@/actions/App/Http/Controllers/Central/Auth/RegisteredController'

export default function NavigationMenu() {
  return (
    <nav className="fixed top-0 right-0 left-0 z-50 border-b border-border bg-background/95 backdrop-blur-md">
      <div className="mx-auto flex h-16 max-w-325 items-center justify-between px-4 sm:px-12">
        <div className="flex items-center gap-2.5 font-display text-base font-semibold">
          SGE
        </div>
        <div className="hidden gap-9 min-[820px]:flex">
          <a
            href="#produto"
            className="text-[13px] text-muted-foreground transition-colors duration-250 hover:text-foreground"
          >
            Produto
          </a>
          <a
            href="#modulos"
            className="text-[13px] text-muted-foreground transition-colors duration-250 hover:text-foreground"
          >
            Módulos
          </a>
          <a
            href="#plataforma"
            className="text-[13px] text-muted-foreground transition-colors duration-250 hover:text-foreground"
          >
            Plataforma
          </a>
          <a
            href="#clientes"
            className="text-[13px] text-muted-foreground transition-colors duration-250 hover:text-foreground"
          >
            Clientes
          </a>
        </div>
        <Button
          asChild
          size={'lg'}
          className="h-9 px-3 text-sm sm:h-10 sm:px-4"
        >
          <Link href={create().url} prefetch>
            Registro
          </Link>
        </Button>
      </div>
    </nav>
  );
}
