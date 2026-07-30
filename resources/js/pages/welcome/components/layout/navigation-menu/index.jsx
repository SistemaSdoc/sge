export default function NavigationMenu() {
  return (
    <nav class="border-line bg-bg/75 fixed top-0 right-0 left-0 z-50 border-b backdrop-blur-md">
      <div class="wrap flex h-16 items-center justify-between px-12">
        <div class="font-display flex items-center gap-2.5 text-base font-semibold">
          <span class="logo-mark"></span> SGE
        </div>
        <div class="hidden gap-9 min-[820px]:flex">
          <a
            href="#produto"
            class="hover:text-text text-[13px] text-muted transition-colors duration-250"
          >
            Produto
          </a>
          <a
            href="#modulos"
            class="hover:text-text text-[13px] text-muted transition-colors duration-250"
          >
            Módulos
          </a>
          <a
            href="#plataforma"
            class="hover:text-text text-[13px] text-muted transition-colors duration-250"
          >
            Plataforma
          </a>
          <a
            href="#clientes"
            class="hover:text-text text-[13px] text-muted transition-colors duration-250"
          >
            Clientes
          </a>
        </div>
        <a
          href="/login"
          class="border-line hover:bg-accent-dim border px-4.5 py-2.25 text-[13px] transition-colors duration-250 hover:border-accent"
        >
          Login
        </a>
      </div>
    </nav>
  );
}
