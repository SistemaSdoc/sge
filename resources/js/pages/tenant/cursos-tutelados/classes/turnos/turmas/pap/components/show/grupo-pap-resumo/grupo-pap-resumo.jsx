import { Link } from '@inertiajs/react';
import { Minus } from 'lucide-react';
import { show as showProfessor } from '@/actions/App/Http/Controllers/Tenant/ProfessorController';
import { show as showUser } from '@/actions/App/Http/Controllers/Tenant/UserProfileController';
import { CardContent } from '@/components/ui/card';
import { DocumentoGrupo } from './documento-grupo';

export function GrupoPapResumo({ grupoPap, turma, currentUserId }) {
  return (
    <CardContent className="grid grid-cols-1 gap-6 py-6 md:grid-cols-4">
      <div>
        <p className="text-sm text-muted-foreground">Professor tutor</p>
        <p className="font-medium">
          {grupoPap?.professor ? (
            grupoPap.professor.can_view ? (
              <Link
                href={
                  String(currentUserId) === String(grupoPap.professor.user_id)
                    ? showUser.url({ user: grupoPap.professor.user_id })
                    : showProfessor.url({
                        professor: grupoPap.professor.id,
                      })
                }
                className="hover:underline"
              >
                {grupoPap.professor.nome}
              </Link>
            ) : (
              grupoPap.professor.nome
            )
          ) : (
            <Minus size={15} className="text-muted-foreground" />
          )}
        </p>
      </div>

      <div>
        <p className="text-sm text-muted-foreground">Turma</p>
        <p className="font-medium">
          {turma?.nome ?? <Minus size={15} className="text-muted-foreground" />}
        </p>
      </div>

      <div>
        <p className="text-sm text-muted-foreground">Status</p>
        <p className="font-medium">
          {grupoPap?.status ?? (
            <Minus size={15} className="text-muted-foreground" />
          )}
        </p>
      </div>

      <div>
        <p className="text-sm text-muted-foreground">Local & Data de defesa</p>
        <p className="font-medium">
          {grupoPap?.local_defesa ? `${grupoPap.local_defesa} / ` : ''}
          {grupoPap?.data_defesa
            ? new Date(grupoPap.data_defesa).toLocaleString('pt-PT', {
                weekday: 'short',
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
              })
            : 'Por definir...'}
        </p>
      </div>

      <DocumentoGrupo label="Critérios PAP" url={grupoPap?.criterios_pap_url} />
      <DocumentoGrupo label="Manual de PT" url={grupoPap?.manual_pt_url} />
      <DocumentoGrupo
        label="Estrutura do Trabalho PAP"
        url={grupoPap?.estrutura_trabalho_pap_url}
      />
      <DocumentoGrupo
        label="Sugestões de Temas PAP"
        url={grupoPap?.sugestoes_temas_pap_url}
      />
    </CardContent>
  );
}
