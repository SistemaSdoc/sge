import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
  return (
    <div className="flex w-full min-w-0 items-center justify-center gap-2.5 group-data-[collapsible=icon]:gap-0">
      <div className="flex size-10 shrink-0 items-center justify-center group-data-[collapsible=icon]:size-8">
        <AppLogoIcon className="size-full fill-current" />
      </div>

      <div className="grid min-w-0 flex-1 text-left leading-none group-data-[collapsible=icon]:hidden">
        <span className="truncate text-[10px] font-medium tracking-[0.08em] text-sidebar-foreground/60 uppercase">
          Plataforma de
        </span>
        <span className="mt-1 truncate text-sm font-semibold tracking-tight text-sidebar-foreground">
          Gestão Escolar
        </span>
      </div>
    </div>
  );
}

{
  /**
  
  import AppLogoIcon from '@/components/app-logo-icon';


export default function AppLogo() {
  return (
    <div className="flex w-full min-w-0 items-center justify-center gap-2.5 group-data-[collapsible=icon]:gap-0">
      <div className="flex size-10 shrink-0 items-center justify-center group-data-[collapsible=icon]:size-8">
        <AppLogoIcon className="size-full fill-current" />
      </div>

      <div className="grid min-w-0 flex-1 text-left leading-none group-data-[collapsible=icon]:hidden">
        <span className="truncate text-[10px] font-medium tracking-[0.08em] text-sidebar-foreground/60 uppercase">
          Plataforma de
        </span>
        <span className="mt-1 truncate text-sm font-semibold tracking-tight text-sidebar-foreground">
          Gestão Escolar
        </span>
      </div>
    </div>
  );
}
  
  */
}
