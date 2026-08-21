import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';

export default function ModalDecisaoAprovacao({
  open,
  onClose,
  tema,
  action,
  comentario,
  onComentarioChange,
  onConfirmar,
  loading,
}) {

  const [submitted, setSubmitted] = useState(false);

  useEffect(() => {
    if (open) setSubmitted(false);
  }, [open, action]);
  
  const MODAL_CONFIG = {
    aprovar: {
      titulo: 'Aprovar Tema PAP',
      descricao: 'O tema será aprovado e o grupo poderá avançar para a próxima fase da PAP.',
      obrigatorio: false,
      confirmLabel: 'Aprovar',
      confirmVariant: 'default',
      placeholder: 'Comentário opcional...',
    },
    reprovar: {
      titulo: 'Reprovar Tema PAP',
      descricao: 'Informe o motivo pelo qual o tema não foi aprovado.',
      obrigatorio: true,
      confirmLabel: 'Reprovar',
      confirmVariant: 'destructive',
      placeholder: 'Informe o motivo da reprovação...',
    },
    melhoria: {
      titulo: 'Solicitar Melhoria',
      descricao: 'Informe as alterações que o grupo deverá realizar antes de reenviar o tema.',
      obrigatorio: true,
      confirmLabel: 'Solicitar Melhoria',
      confirmVariant: 'outline',
      placeholder: 'Informe as recomendações de melhoria...',
    },
    aprovarTutor: {
      titulo: 'Enviar para a coordenação',
      descricao: 'O tema será enviado para análise da coordenação do curso.',
      obrigatorio: false,
      confirmLabel: 'Enviar para coordenação',
      confirmVariant: 'default',
      placeholder: 'Comentário opcional...',
    },
    melhoriaComoTutor: {
      titulo: 'Solicitar Melhoria',
      descricao: 'Informe as alterações que o grupo deverá realizar antes de reenviar o tema.',
      obrigatorio: true,
      confirmLabel: 'Solicitar Melhoria',
      confirmVariant: 'outline',
      placeholder: 'Informe as recomendações de melhoria...',
    },
  };

  const config = MODAL_CONFIG[action] ?? {};
  const comentarioError = config.obrigatorio && submitted
    ? comentario.trim().length === 0
      ? 'Este campo é obrigatório.'
      : comentario.trim().length < 10
        ? 'Este campo deve conter pelo menos 10 caracteres.'
        : null
    : null;
  const podeConfirmar = !config.obrigatorio || !comentarioError;

  const handleClose = () => {
    if (loading) return;
    onClose();
  };

  return (
    <Dialog open={open} onOpenChange={(o) => { if (!o) handleClose(); }}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{config.titulo}</DialogTitle>
        </DialogHeader>

        <div className="space-y-4 py-2">
          {/* Resumo do tema */}
          <div className="border bg-muted/40 px-4 py-3 space-y-1 text-sm">
            <p><span className="font-medium">Tema: </span>{tema?.tema_grupo}</p>
            <p><span className="font-medium">Problema: </span>{tema?.problema}</p>
            <p><span className="font-medium">Objectivos: </span>{tema?.objectivos}</p>
          </div>

          {/* Descrição da acção */}
          <p className="text-sm text-muted-foreground">{config.descricao}</p>

          {/* Comentário */}
          <div className="space-y-1.5">
            <Label htmlFor="comentario-aprovacao">
              Comentário{config.obrigatorio ? ' *' : ' (opcional)'}
            </Label>
            <Textarea
              id="comentario-aprovacao"
              rows={4}
              value={comentario}
              onChange={(e) => onComentarioChange(e.target.value)}
              placeholder={config.placeholder}
              disabled={loading}
            />
            {config.obrigatorio && comentarioError && (
              <p className="text-xs text-red-500">{comentarioError}</p>
            )}
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={handleClose} disabled={loading}>
            Cancelar
          </Button>
          <Button
            variant={config.confirmVariant ?? 'default'}
            onClick={() => {
              setSubmitted(true);
              if (!podeConfirmar) return;
              onConfirmar();
            }}
            disabled={loading}
          >
            {loading ? 'A processar...' : config.confirmLabel}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}