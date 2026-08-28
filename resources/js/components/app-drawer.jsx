import {
  Drawer,
  DrawerContent,
  DrawerDescription,
  DrawerClose,
  DrawerHeader,
  DrawerTitle,
} from '@/components/ui/drawer';
import { cn } from '@/lib/utils';
import { useDrawerStore } from '@/stores/drawer.store';
import { X } from 'lucide-react';
import { Button } from '@/components/ui/button';

/**
 * Componente visual global do drawer.
 * Lê o estado do store e renderiza o drawer com o conteúdo fornecido.
 * Deve ser montado uma única vez no AppLayout.
 */
export function AppDrawer() {
  const {
    open,
    title,
    description,
    content,
    className,
    closeOnOutsideClick,
    closeDrawer,
  } = useDrawerStore();

  return (
    <Drawer open={open} direction="right" onOpenChange={closeDrawer}>
      <DrawerContent
        className="w-105"
        onPointerDownOutside={
          closeOnOutsideClick ? undefined : (e) => e.preventDefault()
        }
      >
        <DrawerHeader className="flex-row items-start justify-between gap-4">
          <div className="min-w-0">
            <DrawerTitle>{title}</DrawerTitle>
            {description && (
              <DrawerDescription>{description}</DrawerDescription>
            )}
          </div>
          <DrawerClose asChild>
            <Button
              type="button"
              variant="ghost"
              size="icon"
              aria-label="Fechar drawer"
            >
              <X className="size-4" />
            </Button>
          </DrawerClose>
        </DrawerHeader>

        {/* Formulário ou conteúdo passado pelo openDrawer */}
        <div className={cn('flex-1 overflow-y-auto p-4', className)}>
          {content}
        </div>
      </DrawerContent>
    </Drawer>
  );
}
