import { Link, router } from '@inertiajs/react';
import { CheckCircle, XCircle, AlertCircle, Clock } from 'lucide-react';
import { useState } from 'react';
import {
  aprovar,
  reprovar,
  solicitarMelhoria,
  formAprovarTutor,
  formSolicitarMelhoriaTutor,
} from '@/actions/App/Http/Controllers/Tenant/GrupoPapAprovacaoController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import ModalDecisaoAprovacao from '@/pages/tenant/pap/components/aprovacao/ModalDecisaoAprovacao';

const STATUS = {
  rascunho: {
    label: 'Rascunho',
    icon: Clock,
    badgeClass: 'bg-muted text-muted-foreground border-transparent',
  },
  submetido: {
    label: 'Em análise — Tutor',
    icon: Clock,
    badgeClass: 'bg-blue-50 text-blue-700 border-blue-200',
  },
  pendente: {
    label: 'Pendente',
    icon: AlertCircle,
    badgeClass: 'bg-muted text-muted-foreground border-transparent',
  },
  aprovado: {
    label: 'Aprovado',
    icon: CheckCircle,
    badgeClass: 'bg-green-50 text-green-700 border-green-200',
  },
  reprovado: {
    label: 'Reprovado',
    icon: XCircle,
    badgeClass: 'bg-red-50 text-red-700 border-red-200',
  },
  'melhoria-solicitada-tutor': {
    label: 'Correção solicitada — Tutor',
    icon: AlertCircle,
    badgeClass: 'bg-amber-50 text-amber-700 border-amber-200',
  },
  'melhoria-solicitada-coordenacao': {
    label: 'Correção solicitada — Coordenação',
    icon: AlertCircle,
    badgeClass: 'bg-orange-50 text-orange-700 border-orange-200',
  },
};

const getStatus = (status) => STATUS[status?.toLowerCase()] || STATUS.pendente;

export function TabAprovacao({ grupoPap, params, can }) {
  const [open, setOpen] = useState(false);
  const [action, setAction] = useState(null);
  const [comentario, setComentario] = useState('');
  const [loading, setLoading] = useState(false);

  const abrir = (tipo) => {
    setAction(tipo);
    setComentario('');
    setOpen(true);
  };

  const fechar = () => {
    if (loading) {
      return;
    }

    setOpen(false);
    setAction(null);
    setComentario('');
  };

  const confirmar = () => {
    if (!action) {
      return;
    }

    setLoading(true);

    const rota =
      action === 'aprovar'
        ? aprovar
        : action === 'reprovar'
          ? reprovar
          : solicitarMelhoria;

    router.post(
      rota.url({ grupoPap: grupoPap.id }),
      action === 'aprovar'
        ? { comentario: comentario || null }
        : action === 'reprovar'
          ? { motivo: comentario }
          : { recomendacao: comentario },
      {
        preserveScroll: true,
        onSuccess: () => {
          setLoading(false);
          fechar();
          router.reload();
        },
        onError: () => setLoading(false),
      },
    );
  };

  const statusAtual = (grupoPap.status_aprovacao || '').toLowerCase();
  const config = getStatus(statusAtual);
  const StatusIcon = config.icon;
  const renderAreaDecisao = () => {
    // Tema ainda não submetido
    if (statusAtual === 'rascunho') {
      return (
        <p className="text-sm text-muted-foreground">
          Os alunos ainda não submeteram o tema.
        </p>
      );
    }

    // Aguarda aprovação do tutor
    if (statusAtual === 'submetido') {
      return (
        <div className="space-y-4">
          <p className="text-sm text-muted-foreground">
            {can?.aprovarComoTutor ? '' : 'Aguarda revisão do professor tutor.'}
          </p>

          {can?.aprovarComoTutor && (
            <div className="flex flex-wrap justify-end gap-2">
              <Button asChild variant="outline" disabled={loading}>
                <Link href={formSolicitarMelhoriaTutor.url(params)}>
                  Solicitar Melhoria
                </Link>
              </Button>

              <Button asChild disabled={loading}>
                <Link href={formAprovarTutor.url(params)}>
                  Enviar para coordenação
                </Link>
              </Button>
            </div>
          )}
        </div>
      );
    }

    // Finalizado
    /* if (isFinalizado) {
      return (
        <div className="flex items-center gap-2 px-4 py-3 rounded-md bg-muted/40">
          <StatusIcon className="size-4 shrink-0 text-muted-foreground" />
          <p className="text-sm text-muted-foreground">
            Tema aprovado. Pode agora definir a data e local de defesa.
          </p>
        </div>
      );
    }*/

    // Pendente / melhoria-solicitada / reprovado — botões da coordenação
    return (
      <div className="space-y-4">
        {/* <p className="text-sm text-muted-foreground">
          Decida sobre a aprovação, reprovação ou solicite melhorias.
        </p> */}
        {(can?.aprovar || can?.reprovar || can?.solicitarMelhoria) && (
          <div className="flex flex-wrap justify-end gap-2">
            {can?.solicitarMelhoria && (
              <Button
                variant="outline"
                onClick={() => abrir('melhoria')}
                disabled={loading}
              >
                <AlertCircle className="size-4" />
                Solicitar Melhoria
              </Button>
            )}
            {can?.reprovar && (
              <Button
                variant="destructive"
                onClick={() => abrir('reprovar')}
                disabled={loading}
              >
                <XCircle className="size-4" />
                Reprovar
              </Button>
            )}
            {can?.aprovar && (
              <Button onClick={() => abrir('aprovar')} disabled={loading}>
                <CheckCircle className="size-4" />
                Aprovar
              </Button>
            )}
          </div>
        )}
      </div>
    );
  };

  return (
    <div className="w-full space-y-6">
      <Card className="gap-0">
        <CardHeader className="border-b">
          <div className="flex items-center justify-between">
            <CardTitle>Aprovação do Tema</CardTitle>
            <Badge
              variant="outline"
              className={`gap-1.5 text-xs font-normal ${config.badgeClass}`}
            >
              <StatusIcon className="size-3.5" />
              {config.label}
            </Badge>
          </div>
        </CardHeader>
        <CardContent className="pt-6 space-y-6">
          <div className="grid grid-cols-1 gap-6">
            <div>
              <p className="text-xs font-bold uppercase text-muted-foreground">
                Tema
              </p>
              <p className="mt-1 text-sm font-medium">
                {grupoPap.tema_grupo ?? '—'}
              </p>
            </div>

            <div>
              <p className="text-xs font-bold uppercase text-muted-foreground">
                Problema
              </p>
              <p className="mt-1 text-sm font-medium">
                {grupoPap.problema ?? '—'}
              </p>
            </div>

            <div>
              <p className="text-xs font-bold uppercase text-muted-foreground">
                Objectivos
              </p>
              <p className="mt-1 text-sm font-medium">
                {grupoPap.objectivos ?? '—'}
              </p>
            </div>
          </div>

          {renderAreaDecisao()}
        </CardContent>
      </Card>

      <ModalDecisaoAprovacao
        open={open}
        onClose={fechar}
        tema={grupoPap}
        action={action}
        comentario={comentario}
        onComentarioChange={setComentario}
        onConfirmar={confirmar}
        loading={loading}
      />
    </div>
  );
}
