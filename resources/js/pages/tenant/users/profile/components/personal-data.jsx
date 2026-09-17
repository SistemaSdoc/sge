import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/hooks/use-initials';

export function PersonalData({ user }) {
  const getInitials = useInitials();

  return (
    <div className="flex items-start gap-6">
      <div className="flex items-center gap-3">
        <Avatar className="size-20">
          <AvatarImage src={user.avatar ?? undefined} alt={user.nome} />
          <AvatarFallback>{getInitials(user.nome)}</AvatarFallback>
        </Avatar>

        <div className="flex flex-col">
          <span className="text-sm">{user.nome}</span>
          <span className="text-xs text-muted-foreground">{user.email}</span>
        </div>
      </div>
    </div>
  );
}
