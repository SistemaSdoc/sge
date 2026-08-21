import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { CheckCircle, XCircle, AlertCircle, Clock } from 'lucide-react';
import ModalDecisaoAprovacao from '../../../../../../../../pages/pap/components/ModalDecisaoAprovacao';
import {
  aprovar,
  reprovar,
  solicitarMelhoria,
  aprovarTutor,
  solicitarMelhoriaComoTutor,
} from '@/actions/App/Http/Controllers/GrupoPapAprovacaoController';

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

export function TabAprovacao({ params, grupoPap, can }) {
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
    if (loading) return;
    setOpen(false);
    setAction(null);
    setComentario('');
  };

  const confirmar = () => {
    if (!action) return;
    setLoading(true);

    // Acção do tutor — rota diferente
    if (action === 'aprovarTutor') {
      router.post(
        aprovarTutor.url({ grupoPap: grupoPap.id }),
        { comentario: comentario || null },
        {
          preserveScroll: true,
          onSuccess: () => { setLoading(false); fechar(); router.reload(); },
          onError: () => setLoading(false),
        },
      );
      return;
    }

    if (action === 'melhoriaComoTutor') {
      router.post(
        solicitarMelhoriaComoTutor.url({ grupoPap: grupoPap.id }),
        { recomendacao: comentario },
        {
          preserveScroll: true,
          onSuccess: () => { setLoading(false); fechar(); router.reload(); },
          onError: () => setLoading(false),
        },
      );
      return;
    }

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
        onSuccess: () => { setLoading(false); fechar(); router.reload(); },
        onError: () => setLoading(false),
      },
    );
  };

  const statusAtual = (grupoPap.status_aprovacao || '').toLowerCase();
  const config = getStatus(statusAtual);
  const StatusIcon = config.icon;
  const isFinalizado = ['aprovado'].includes(statusAtual);

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
            {can?.aprovarComoTutor
              ? 'Revê o tema submetido pelos alunos e envia para a coordenação.'
              : 'Aguarda revisão do professor tutor.'}
          </p>
          {can?.aprovarComoTutor && (
            <div className="flex flex-wrap justify-end gap-2">
              <Button
                variant="outline"
                onClick={() => abrir('melhoriaComoTutor')} // ← novo
                disabled={loading}
              >
                <AlertCircle className="size-4" />
                Solicitar Melhoria
              </Button>
              <Button onClick={() => abrir('aprovarTutor')} disabled={loading}>
                <CheckCircle className="size-4" />
                Enviar para coordenação
              </Button>
            </div>
          )}
        </div>
      );
    }

    // Finalizado
    /* if (isFinalizado) {
      return (
        <div className="flex items-center gap-2 rounded-md bg-muted/40 px-4 py-3">
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
              <Button variant="outline" onClick={() => abrir('melhoria')} disabled={loading}>
                <AlertCircle className="size-4" />
                Solicitar Melhoria
              </Button>
            )}
            {can?.reprovar && (
              <Button variant="destructive" onClick={() => abrir('reprovar')} disabled={loading}>
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
            <Badge variant="outline" className={`gap-1.5 text-xs font-normal ${config.badgeClass}`}>
              <StatusIcon className="size-3.5" />
              {config.label}
            </Badge>
          </div>
        </CardHeader>
        <CardContent className="space-y-6 pt-6">
          <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
              <p className="text-xs font-medium text-muted-foreground uppercase">Grupo</p>
              <p className="mt-1 text-sm font-medium">{grupoPap.nome_grupo}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted-foreground uppercase">Tema</p>
              <p className="mt-1 text-sm font-medium">{grupoPap.tema_grupo ?? '—'}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted-foreground uppercase">Turma</p>
              <p className="mt-1 text-sm font-medium">{grupoPap.turma?.nome ?? '—'}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted-foreground uppercase">Problema</p>
              <p className="mt-1 text-sm font-medium">{grupoPap.problema ?? '—'}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted-foreground uppercase">Professor Tutor</p>
              <p className="mt-1 text-sm font-medium">{grupoPap.professor?.nome ?? '—'}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted-foreground uppercase">Objectivos</p>
              <p className="mt-1 text-sm font-medium">{grupoPap.objectivos ?? '—'}</p>
            </div>
          </div>

          <div className="border-t" />

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