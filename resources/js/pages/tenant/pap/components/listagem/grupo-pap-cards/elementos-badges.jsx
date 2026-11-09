import { Link } from '@inertiajs/react';
import { ArrowUpRightIcon } from 'lucide-react';
import { show as showAluno } from '@/actions/App/Http/Controllers/Tenant/AlunoController';
import { show as showUser } from '@/actions/App/Http/Controllers/Tenant/UserProfileController';
import { Badge } from '@/components/ui/badge';

const MAX_ELEMENTOS_VISIVEIS = 2;

export function ElementosBadges({ elementos = [] }) {
  const visiveis = elementos.slice(0, MAX_ELEMENTOS_VISIVEIS);
  const restantes = elementos.length - MAX_ELEMENTOS_VISIVEIS;

  return (
    <div className="flex flex-wrap gap-1">
      {visiveis.map((elemento) => (
        <Badge
          key={elemento.id}
          variant="secondary"
          asChild
          onClick={(e) => e.stopPropagation()}
          className="hover:underline"
        >
          {elemento.can_view ? (
            <Link
              href={
                elemento.is_current_user
                  ? showUser.url({ user: elemento.user_id })
                  : showAluno.url({ aluno: elemento.id })
              }
            >
              {elemento.nome?.split(' ').slice(0, 2).join(' ')}
              <ArrowUpRightIcon size={10} className="ml-1" />
            </Link>
          ) : (
            <span>{elemento.nome?.split(' ').slice(0, 2).join(' ')}</span>
          )}
        </Badge>
      ))}

      {restantes > 0 && (
        <Badge
          variant="outline"
          className="cursor-default text-muted-foreground"
          onClick={(e) => e.stopPropagation()}
        >
          +{restantes}
        </Badge>
      )}

      {elementos.length === 0 && (
        <span className="text-xs text-muted-foreground">Sem elementos</span>
      )}
    </div>
  );
}
