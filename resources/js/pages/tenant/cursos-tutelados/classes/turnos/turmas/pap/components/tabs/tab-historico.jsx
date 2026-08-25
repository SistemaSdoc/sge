import { EmptyState } from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription,
} from '@/components/ui/card';
import {
  CheckCircle,
  XCircle,
  AlertCircle,
  Clock,
  RefreshCw,
  FileText,
  Landmark,
} from 'lucide-react';

const STATUS = {
  pendente: {
    label: 'Pendente',
    icon: Clock,
    badgeClass: 'bg-muted text-muted-foreground border-transparent',
    barClass: 'bg-muted-foreground',
  },
  aprovado: {
    label: 'Aprovado',
    icon: CheckCircle,
    badgeClass: 'bg-green-50 text-green-700 border-green-200',
    barClass: 'bg-green-500',
  },
  reprovado: {
    label: 'Reprovado',
    icon: XCircle,
    badgeClass: 'bg-red-50 text-red-700 border-red-200',
    barClass: 'bg-red-500',
  },
  'melhoria-solicitada': {
    label: 'Melhoria Solicitada',
    icon: AlertCircle,
    badgeClass: 'bg-amber-50 text-amber-700 border-amber-200',
    barClass: 'bg-amber-500',
  },
  'tema-submetido': {
    label: 'Reenviado',
    icon: RefreshCw,
    badgeClass: 'bg-blue-50 text-blue-700 border-blue-200',
    barClass: 'bg-blue-500',
  },
};

const getStatus = (s) => STATUS[s] || STATUS.pendente;

const formatDate = (d) =>
  d
    ? new Date(d).toLocaleDateString('pt-PT', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
      }) +
      ' · ' +
      new Date(d).toLocaleTimeString('pt-PT', {
        hour: '2-digit',
        minute: '2-digit',
      })
    : null;

export function TabHistorico({ historico = [] }) {
  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <CardTitle>Histórico</CardTitle>
        <CardDescription>
          Registo de todas as decisões e alterações de tema
        </CardDescription>
      </CardHeader>

      <CardContent className="p-0!">
        {historico.length === 0 ? (
          <EmptyState
            variant="table"
            icon={Landmark}
            title="Nenhum registo encontrado"
            description="Decida sobre a aprovação, reprovação ou solicite melhorias."
          />
        ) : (
          <div className="divide-y">
            {historico.map((item) => {
              const config = getStatus(item.estado_novo);
              const Icon = config.icon;

              return (
                <div key={item.id} className="px-6 py-4">
                  <div className="flex items-start justify-between gap-4">
                    <Badge
                      variant="outline"
                      className={`gap-1.5 text-xs font-normal ${config.badgeClass}`}
                    >
                      <Icon className="size-3.5" />
                      {config.label}
                    </Badge>
                    <span className="shrink-0 text-xs text-muted-foreground">
                      {formatDate(item.created_at)}
                    </span>
                  </div>

                  <div className="mt-2.5 space-y-2">
                    <p className="text-sm font-medium">
                      {item.utilizador?.nome ?? '—'}
                    </p>

                    {item.comentario && (
                      <p className="text-sm text-muted-foreground">
                        {item.comentario}
                      </p>
                    )}

                    {item.tema && (
                      <div className="flex items-start gap-2 rounded-md border bg-muted/30 px-3 py-2 text-xs">
                        <FileText className="mt-0.5 size-3.5 shrink-0 text-muted-foreground" />
                        <div className="space-y-1">
                          <p>
                            <span className="font-medium">Tema:</span>{' '}
                            {item.tema}
                          </p>
                          {item.problema && (
                            <p>
                              <span className="font-medium">Problema:</span>{' '}
                              {item.problema}
                            </p>
                          )}
                          {item.objectivos && (
                            <p>
                              <span className="font-medium">Objectivos:</span>{' '}
                              {item.objectivos}
                            </p>
                          )}
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
