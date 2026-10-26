import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';

export function SugestaoTemaDetailsDialog({ open, onOpenChange, sugestao }) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Detalhes da sugestão de tema</DialogTitle>
          <DialogDescription>
            Consulte o tema e a descrição completa da sugestão.
          </DialogDescription>
        </DialogHeader>

        {sugestao && (
          <div className="space-y-4 py-2">
            <div className="space-y-1">
              <p className="text-sm font-medium">Tema</p>
              <p className="text-sm wrap-break-word text-muted-foreground">
                {sugestao.titulo}
              </p>
            </div>
            <div className="space-y-1">
              <p className="text-sm font-medium">Descrição</p>
              <p className="text-sm wrap-break-word whitespace-pre-wrap text-muted-foreground">
                {sugestao.descricao || 'Nenhuma descrição registada.'}
                {sugestao.observacao_aprovacao && (
                  <p className="mt-1 text-xs font-medium">
                    Sugestão de Melhoria: {sugestao.observacao_aprovacao}
                  </p>
                )}
              </p>
            </div>
          </div>
        )}

        <DialogFooter>
          <Button
            type="button"
            variant="outline"
            onClick={() => onOpenChange(false)}
          >
            Fechar
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
