import { Button } from '@/components/ui/button';
import {
  AlertDialog,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Textarea } from '@/components/ui/textarea';

export default function ModalDecisaoAprovacao({
  open,
  onClose,
  tema,
  problema,
  objectivos,
  action,
  comentario,
  onComentarioChange,
  onConfirmar,
  loading,
}) {
  const comentarioObrigatorio = action === 'reprovar' || action === 'melhoria';
  const podeConfirmar = action === 'aprovar' || comentario.trim().length >= 10;

  const getTitle = () => {
    switch (action) {
      case 'aprovar':
        return 'Aprovar Tema PAP';
      case 'reprovar':
        return 'Reprovar Tema PAP';
      case 'melhoria':
        return 'Solicitar Melhoria';
      default:
        return '';
    }
  };

  const getPlaceholder = () => {
    switch (action) {
      case 'aprovar':
        return 'Comentários adicionais (opcional)...';
      case 'reprovar':
        return 'Informe o motivo da reprovação...';
      case 'melhoria':
        return 'Informe as recomendações de melhoria...';
      default:
        return '';
    }
  };

  const getDescricao = () => {
    switch (action) {
      case 'aprovar':
        return 'O tema será aprovado e o grupo poderá avançar para a próxima fase da PAP.';
      case 'reprovar':
        return 'Informe o motivo pelo qual o tema não foi aprovado.';
      case 'melhoria':
        return 'Informe as alterações que o grupo deverá realizar antes de reenviar o tema.';
      default:
        return '';
    }
  };

  return (
    <AlertDialog
      open={open}
      onOpenChange={(isOpen) => {
        if (!isOpen) onClose();
      }}
    >
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{getTitle()}</AlertDialogTitle>

          <AlertDialogDescription>
            <span className="font-semibold text-foreground">Tema: </span>
            {tema?.tema_grupo}
          </AlertDialogDescription>

          <AlertDialogDescription>
            <span className="font-semibold text-foreground">Problema: </span>
            {tema?.problema}
          </AlertDialogDescription>

          <AlertDialogDescription>
            <span className="font-semibold text-foreground">Objectivos: </span>
            {tema?.objectivos}
          </AlertDialogDescription>
        </AlertDialogHeader>

        {/* Informação */}
        <div className="rounded-md bg-gray-50 p-3 text-sm">
          <p>{getDescricao()}</p>
        </div>

        {/* Campo de comentário */}
        <Textarea
          placeholder={getPlaceholder()}
          value={comentario}
          onChange={(e) => onComentarioChange(e.target.value)}
          disabled={loading}
          className="min-h-28"
        />

        {/* Mensagem de validação */}
        {comentarioObrigatorio && comentario.trim().length < 10 && (
          <p className="text-sm text-red-500">
            Este campo deve conter pelo menos 10 caracteres.
          </p>
        )}

        <AlertDialogFooter>
          <AlertDialogCancel disabled={loading}>Cancelar</AlertDialogCancel>

          <Button
            disabled={loading || !podeConfirmar}
            onClick={onConfirmar}
            variant={action === 'reprovar' ? 'destructive' : 'default'}
          >
            {loading ? 'Processando...' : 'Confirmar'}
          </Button>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}
