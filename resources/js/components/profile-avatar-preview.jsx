import { useInitials } from '@/hooks/use-initials';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';

export function ProfileAvatarPreview({ src, name, className }) {
  const getInitials = useInitials();
  const imageUrl = src || null;
  const avatar = (
    <Avatar className={className}>
      <AvatarImage src={imageUrl ?? undefined} alt={name ?? 'Perfil'} />
      <AvatarFallback>{getInitials(name ?? '')}</AvatarFallback>
    </Avatar>
  );

  if (!imageUrl) {
    return avatar;
  }

  return (
    <Dialog>
      <DialogTrigger asChild>
        <button
          type="button"
          data-profile-avatar-preview
          aria-label={`Ver foto de ${name ?? 'perfil'}`}
          className="inline-flex cursor-zoom-in rounded-full p-0 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
          onClick={(event) => event.stopPropagation()}
        >
          {avatar}
        </button>
      </DialogTrigger>

      <DialogContent
        className="max-h-[88svh] max-w-none overflow-hidden p-3 sm:p-4"
        overlayClassName="supports-backdrop-filter:backdrop-blur-none"
        onClick={(event) => event.stopPropagation()}
      >
        <DialogHeader>
          <DialogTitle>{name || 'Foto de perfil'}</DialogTitle>
        </DialogHeader>

        <div className="flex h-100 w-full items-center justify-center overflow-hidden">
          <img
            src={imageUrl}
            alt={name ?? 'Foto de perfil'}
            className="size-full object-contain"
          />
        </div>
      </DialogContent>
    </Dialog>
  );
}
