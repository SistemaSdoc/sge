import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

const MODAL_CONFIG = {
  aprovarTutor: {
    titulo: 'Enviar para a coordenação',
    descricao: 'O trabalho será enviado para análise da coordenação do curso.',
    obrigatorio: false,
    confirmLabel: 'Enviar para coordenação',
    confirmVariant: 'default',
  },
  correcaoTutor: {
    titulo: 'Solicitar correção ao aluno',
    descricao:
      'Descreve as correções necessárias. O aluno receberá este feedback e deverá submeter uma nova versão.',
    obrigatorio: true,
    confirmLabel: 'Solicitar correção',
    confirmVariant: 'outline',
  },
  aprovarCoordenacao: {
    titulo: 'Aprovar trabalho',
    descricao: 'O trabalho será aprovado definitivamente.',
    obrigatorio: false,
    confirmLabel: 'Aprovar',
    confirmVariant: 'default',
  },
  correcaoCoordenacao: {
    titulo: 'Solicitar correção ao aluno',
    descricao:
      'O trabalho voltará ao aluno. Após correcção, passará novamente pelo tutor antes de chegar à coordenação.',
    obrigatorio: true,
    confirmLabel: 'Solicitar correção',
    confirmVariant: 'outline',
  },
  reprovarCoordenacao: {
    titulo: 'Reprovar trabalho',
    descricao:
      'O trabalho será reprovado definitivamente. Esta acção não pode ser revertida.',
    obrigatorio: true,
    confirmLabel: 'Reprovar',
    confirmVariant: 'destructive',
  },
};

export function ModalDecisao({
  open,
  onClose,
  action,
  onConfirmar,
  loading,
  minimumCommentLength = 1,
  validateOnSubmit = false,
}) {
  const [comentario, setComentario] = useState('');
  const [ficheiro, setFicheiro] = useState(null);
  const [submitted, setSubmitted] = useState(false);

  const config = MODAL_CONFIG[action] ?? {};
  const pedeFicheiro = ['correcaoTutor', 'correcaoCoordenacao'].includes(
    action,
  );
  const comentarioInvalido =
    config.obrigatorio && comentario.trim().length < minimumCommentLength;
  const comentarioError =
    validateOnSubmit && submitted && comentarioInvalido
      ? comentario.trim().length === 0
        ? 'Este campo é obrigatório.'
        : `Este campo deve conter pelo menos ${minimumCommentLength} caracteres.`
      : null;

  const handleClose = () => {
    if (loading) {
      return;
    }

    setComentario('');
    setFicheiro(null);
    setSubmitted(false);
    onClose();
  };

  const handleConfirm = () => {
    if (validateOnSubmit) {
      setSubmitted(true);
    }

    if (comentarioInvalido) {
      return;
    }

    onConfirmar(comentario, ficheiro);
  };

  return (
    <Dialog
      open={open}
      onOpenChange={(isOpen) => {
        if (!isOpen) {
          handleClose();
        }
      }}
    >
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{config.titulo}</DialogTitle>
        </DialogHeader>

        <div className="space-y-4 py-2">
          <p className="text-sm text-muted-foreground">{config.descricao}</p>

          {pedeFicheiro && (
            <div className="space-y-1.5">
              <Label htmlFor="ficheiro-correcao">
                PDF com correções{' '}
                <span className="text-muted-foreground">(opcional)</span>
              </Label>
              <Input
                id="ficheiro-correcao"
                type="file"
                accept=".pdf"
                onChange={(e) => setFicheiro(e.target.files?.[0] ?? null)}
              />
              {ficheiro && (
                <p className="text-xs text-muted-foreground">
                  {ficheiro.name} ({(ficheiro.size / 1024 / 1024).toFixed(2)}{' '}
                  MB)
                </p>
              )}
            </div>
          )}

          <div className="space-y-1.5">
            <Label htmlFor="comentario-trabalho">
              Comentário{config.obrigatorio ? ' *' : ' (opcional)'}
            </Label>
            <Textarea
              id="comentario-trabalho"
              rows={4}
              value={comentario}
              onChange={(e) => setComentario(e.target.value)}
              placeholder={
                config.obrigatorio
                  ? 'Descreve as correções necessárias...'
                  : 'Adiciona um comentário opcional...'
              }
            />
            {comentarioError && (
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
            onClick={handleConfirm}
            disabled={
              loading ||
              (!validateOnSubmit && config.obrigatorio && comentarioInvalido)
            }
          >
            {loading ? 'A processar...' : config.confirmLabel}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
