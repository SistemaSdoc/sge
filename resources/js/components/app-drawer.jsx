import {
  Drawer,
  DrawerContent,
  DrawerDescription,
  DrawerHeader,
  DrawerTitle,
} from '@/components/ui/drawer';
import { cn } from '@/lib/utils';
import { useDrawerStore } from '@/stores/drawer.store';

/**
 * Componente visual global do drawer.
 * Lê o estado do store e renderiza o drawer com o conteúdo fornecido.
 * Deve ser montado uma única vez no AppLayout.
 */
export function AppDrawer() {
  const { open, title, description, content, className, closeDrawer } = useDrawerStore();

  return (
    <Drawer open={open} direction="right" onOpenChange={closeDrawer}>
      <DrawerContent className="w-105">
        <DrawerHeader>
          <DrawerTitle>{title}</DrawerTitle>
          {description && <DrawerDescription>{description}</DrawerDescription>}
        </DrawerHeader>

        {/* Formulário ou conteúdo passado pelo openDrawer */}
        <div className={cn('flex-1 overflow-y-auto p-4', className)}>{content}</div>
      </DrawerContent>
    </Drawer>
  );
}
