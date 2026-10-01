import { Search } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { ButtonGroup } from '@/components/ui/button-group';
import { Input } from '@/components/ui/input';

export function TableSearch({
  value,
  onChange,
  onSubmit,
  placeholder = 'Pesquisar...',
  bare = false,
}) {
  const form = (
    <form
      className={bare ? 'flex' : 'flex justify-end'}
      onSubmit={(e) => {
        e.preventDefault();
        onSubmit();
      }}
    >
      <ButtonGroup className="w-full max-w-xs">
        <Input
          type="search"
          value={value}
          onChange={(e) => onChange(e.target.value)}
          placeholder={placeholder}
        />
        <Button variant="outline" size="icon" type="submit">
          <Search />
          <span className="sr-only">Pesquisar</span>
        </Button>
      </ButtonGroup>
    </form>
  );

  if (bare) {
    return form;
  }

  return <div className="border-b bg-muted/30 px-4 py-3">{form}</div>;
}