import { Search } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { ButtonGroup } from '@/components/ui/button-group';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

/**
 * @param {{
 *   value: string,
 *   onChange: (value: string) => void,
 *   onSubmit: () => void,
 *   placeholder?: string,
 *   children?: import('react').ReactNode,
 *   bare?: boolean,
 *   buttonGroupClassName?: string,
 *   disabled?: boolean
 * }} props
 */
export function TableSearch({
  value,
  onChange,
  onSubmit,
  placeholder = 'Pesquisar...',
  children = null,
  bare = false,
  buttonGroupClassName,
  disabled = false,
}) {
  const form = (
    <form
      className={
        bare ? 'flex' : 'flex flex-wrap items-center justify-end gap-3'
      }
      onSubmit={(e) => {
        e.preventDefault();
        onSubmit();
      }}
    >
      <ButtonGroup className={cn('w-full max-w-xs', buttonGroupClassName)}>
        <Input
          type="search"
          value={value}
          onChange={(e) => onChange(e.target.value)}
          placeholder={placeholder}
          disabled={disabled}
        />
        <Button
          variant="outline"
          size="icon"
          type="submit"
          className="hover:cursor-pointer"
          disabled={disabled}
        >
          <Search />
          <span className="sr-only">Pesquisar</span>
        </Button>
      </ButtonGroup>
      {children}
    </form>
  );

  if (bare) {
    return form;
  }

  return <div className="border-b bg-muted/30 px-4 py-3">{form}</div>;
}
