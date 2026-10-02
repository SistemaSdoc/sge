import { useForm } from '@inertiajs/react';
import { UserRoundPlus, UserRoundX } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { store as storeSecretario } from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoSecretarioController';

export function TabSecretarios({
  params,
  secretarios = [],
  disponiveis = [],
  canAttach = false,
  removeFn,
}) {
  const { data, setData, post, processing, errors, reset } = useForm({
    user_id: '',
  });

  const submit = (event) => {
    event.preventDefault();

    post(storeSecretario(params).url, {
      preserveScroll: true,
      onSuccess: () => reset(),
    });
  };

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <CardTitle>Secretários do Curso ({secretarios.length})</CardTitle>
        <CardDescription>
          A conta deve existir primeiro na aplicação. Ao associá-la, recebe
          acesso apenas a este curso; os perfis existentes não são alterados.
        </CardDescription>
        {canAttach && (
          <CardAction>
            <form
              onSubmit={submit}
              className="flex flex-wrap items-start gap-2"
            >
              <div className="w-64">
                <Select
                  value={data.user_id}
                  onValueChange={(value) => setData('user_id', value)}
                  disabled={processing || disponiveis.length === 0}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder="Selecionar utilizador" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      <SelectLabel>Contas da aplicação</SelectLabel>
                      {disponiveis.map((user) => (
                        <SelectItem key={user.id} value={String(user.id)}>
                          {user.nome}
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  </SelectContent>
                </Select>
                {errors.user_id && (
                  <p className="mt-1 text-sm text-destructive">
                    {errors.user_id}
                  </p>
                )}
              </div>
              <Button type="submit" disabled={processing || !data.user_id}>
                Adicionar
              </Button>
            </form>
          </CardAction>
        )}
      </CardHeader>

      <CardContent className="p-0">
        {secretarios.length === 0 ? (
          <div className="flex flex-col items-center gap-2 px-6 py-10 text-center">
            <UserRoundX className="size-8 text-muted-foreground" />
            <p className="font-medium">Ainda não há secretários associados</p>
            <p className="text-sm text-muted-foreground">
              {canAttach
                ? disponiveis.length > 0
                  ? 'Associe uma conta existente da instituição para apoiar a coordenação.'
                  : 'Não há utilizadores disponíveis com a função Secretário do Curso. Crie a conta, atribua-lhe essa função na gestão de utilizadores e volte para associá-la aqui.'
                : 'O coordenador ainda não associou ninguém a este curso.'}
            </p>
          </div>
        ) : (
          <ul className="divide-y">
            {secretarios.map((secretario) => (
              <li
                key={secretario.id}
                className="flex items-center justify-between gap-3 px-4 py-3"
              >
                <span className="font-medium">{secretario.nome}</span>
                {canAttach && (
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => removeFn(secretario.id, secretario.nome)}
                  >
                    Remover
                  </Button>
                )}
              </li>
            ))}
          </ul>
        )}
      </CardContent>
    </Card>
  );
}
