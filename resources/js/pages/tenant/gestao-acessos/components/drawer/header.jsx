import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';

export function HeaderDrawer({ usuario }) {
  const getInitials = useInitials();

  return (
    <div className="flex items-center gap-3 border-b px-4 pb-3">
      <Avatar>
        <AvatarImage
          src={usuario.avatar}
          alt={usuario.nome}
          className="grayscale"
        />
        <AvatarFallback>{getInitials(usuario.nome)}</AvatarFallback>
      </Avatar>

      <div className="flex flex-col">
        <span className="text-sm font-medium">{usuario.nome}</span>
        <span className="text-[10px] text-muted-foreground">
          {usuario.email}
        </span>
      </div>
    </div>
  );
}
