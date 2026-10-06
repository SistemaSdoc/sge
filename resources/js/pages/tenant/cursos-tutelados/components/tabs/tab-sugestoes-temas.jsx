import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { BookOpenText } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { EmptyState } from '@/components/empty-state';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { useDialog } from '@/hooks/use-dialog';
import TablePagination from '@/components/table-pagination';
import {
  destroySugestaoTema,
  storeSugestaoTema,
  updateSugestaoTema,
} from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoController';

export function TabSugestoesTemas({
  params,
  sugestoes = [],
  pagination = {},
  onPageChange,
  canManage = false,
  tutelaExterna = false,
}) {
  const [formularioAberto, setFormularioAberto] = useState(false);
  const [sugestaoEmEdicao, setSugestaoEmEdicao] = useState(null);
  const { deleteConfirm } = useDialog();
  const { data, setData, post, put, processing, errors, reset, clearErrors } =
    useForm({
      titulo: '',
      descricao: '',
    });

  const fecharFormulario = () => {
    setFormularioAberto(false);
    setSugestaoEmEdicao(null);
    reset();
    clearErrors();
  };

  const abrirFormularioCriacao = () => {
    setSugestaoEmEdicao(null);
    reset();
    clearErrors();
    setFormularioAberto(true);
  };

  const abrirFormularioEdicao = (sugestao) => {
    setSugestaoEmEdicao(sugestao);
    setData({ titulo: sugestao.titulo, descricao: sugestao.descricao ?? '' });
    clearErrors();
    setFormularioAberto(true);
  };

  const submeterSugestao = (event) => {
    event.preventDefault();
    const paramsSugestao = {
      instituicao: params.instituicao.id,
      cursoTutelado: params.cursoTutelado.id,
    };
    const options = {
      preserveScroll: true,
      onSuccess: fecharFormulario,
    };

    if (sugestaoEmEdicao) {
      put(
        updateSugestaoTema({
          ...paramsSugestao,
          sugestao: sugestaoEmEdicao.id,
        }).url,
        options,
      );

      return;
    }

    post(storeSugestaoTema(paramsSugestao).url, options);
  };

  const removerSugestao = (sugestao) => {
    deleteConfirm({
      title: 'Remover sugestão?',
      description: `A sugestão “${sugestao.titulo}” deixará de estar disponível para este curso.`,
      confirmLabel: 'Remover',
      confirmFn: () =>
        router.delete(
          destroySugestaoTema({
            instituicao: params.instituicao.id,
            cursoTutelado: params.cursoTutelado.id,
            sugestao: sugestao.id,
          }).url,
          { preserveScroll: true },
        ),
    });
  };

  return (
    <>
      <Card className="gap-0">
        <CardHeader className="border-b">
          <CardTitle>
            Sugestões de temas ({pagination.total ?? sugestoes.length})
          </CardTitle>
          <CardDescription>
            {tutelaExterna
              ? 'Temas de referência definidos pela instituição tutora para orientar a escolha dos temas da PAP neste curso.'
              : 'Registe temas de referência para orientar a escolha dos temas da PAP neste curso.'}
          </CardDescription>
          {canManage && (
            <CardAction>
              <Button onClick={abrirFormularioCriacao}>
                Adicionar sugestão
              </Button>
            </CardAction>
          )}
        </CardHeader>

        <CardContent className="p-0!">
          {sugestoes.length === 0 ? (
            <EmptyState
              variant="table"
              icon={BookOpenText}
              title="Nenhuma sugestão cadastrada"
              description={
                tutelaExterna
                  ? 'A instituição tutora ainda não registou sugestões de temas.'
                  : 'Adicione temas de referência para este curso.'
              }
              action={
                canManage && {
                  label: 'Adicionar sugestão',
                  onClick: abrirFormularioCriacao,
                  variant: 'outline',
                }
              }
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="px-4">Tema sugerido</TableHead>
                  <TableHead>Descrição</TableHead>
                  <TableHead>Estado</TableHead>
                  {canManage && (
                    <TableHead className="px-4 text-right">Acções</TableHead>
                  )}
                </TableRow>
              </TableHeader>
              <TableBody>
                {sugestoes.map((sugestao) => (
                  <TableRow key={sugestao.id}>
                    <TableCell className="px-4 font-medium">
                      {sugestao.titulo}
                    </TableCell>
                    <TableCell className="max-w-xl whitespace-normal text-muted-foreground">
                      {sugestao.descricao || '—'}
                    </TableCell>
                    <TableCell>
                      <Badge variant={sugestao.ativo ? 'default' : 'secondary'}>
                        {sugestao.ativo ? 'Activa' : 'Inactiva'}
                      </Badge>
                    </TableCell>
                    {canManage && (
                      <TableCell className="px-4 text-right">
                        <div className="flex justify-end gap-2">
                          <Button
                            variant="outline"
                            size="xs"
                            className="text-[10px]"
                            onClick={() => abrirFormularioEdicao(sugestao)}
                          >
                            Editar
                          </Button>
                          <Button
                            variant="destructive"
                            size="xs"
                            className="text-[10px]"
                            onClick={() => removerSugestao(sugestao)}
                          >
                            Remover
                          </Button>
                        </div>
                      </TableCell>
                    )}
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
        <TablePagination pagination={pagination} onPageChange={onPageChange} />
      </Card>

      <Dialog
        open={formularioAberto}
        onOpenChange={(open) => {
          if (!open) {
            fecharFormulario();
          } else {
            setFormularioAberto(true);
          }
        }}
      >
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>
              {sugestaoEmEdicao
                ? 'Editar sugestão de tema'
                : 'Nova sugestão de tema'}
            </DialogTitle>
            <DialogDescription>
              Cadastre um tema de referência para os trabalhos da PAP deste
              curso.
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={submeterSugestao} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="sugestao-titulo">Título do tema</Label>
              <Input
                id="sugestao-titulo"
                value={data.titulo}
                onChange={(event) => setData('titulo', event.target.value)}
                maxLength={255}
                required
                autoFocus
              />
              {errors.titulo && (
                <p className="text-sm text-destructive">{errors.titulo}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="sugestao-descricao">Descrição (opcional)</Label>
              <Textarea
                id="sugestao-descricao"
                value={data.descricao}
                onChange={(event) => setData('descricao', event.target.value)}
                rows={4}
              />
              {errors.descricao && (
                <p className="text-sm text-destructive">{errors.descricao}</p>
              )}
            </div>

            <DialogFooter>
              <Button
                type="button"
                variant="outline"
                onClick={fecharFormulario}
                disabled={processing}
              >
                Cancelar
              </Button>
              <Button type="submit" disabled={processing}>
                {processing
                  ? 'A guardar…'
                  : sugestaoEmEdicao
                    ? 'Guardar alterações'
                    : 'Guardar sugestão'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </>
  );
}
