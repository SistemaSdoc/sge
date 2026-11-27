import { router } from '@inertiajs/react';
import { destroy as destroyJurado } from '@/actions/App/Http/Controllers/Tenant/BancaJuriPapController';
import {
  destroy as destroyIntegrante,
  actualizarNota as actualizarNotaAction,
} from '@/actions/App/Http/Controllers/Tenant/ElementoGrupoPapController';
import {
  edit,
  destroy as destroyGrupoPap,
} from '@/actions/App/Http/Controllers/Tenant/GrupoPapController';
import { create as createTema } from '@/actions/App/Http/Controllers/Tenant/GrupoPapTemaController';
import { useDialog } from '@/hooks/use-dialog';

export function useGrupoPapActions(params) {
  const { deleteConfirm } = useDialog();

  const editarGrupo = () => {
    router.visit(edit.url(params));
  };

  const definirTema = () => {
    router.visit(createTema.url(params));
  };

  const removerIntegrante = (elemento) => {
    deleteConfirm({
      title: 'Remover aluno do grupo?',
      description: `${elemento.nome} deixará de fazer parte deste grupo PAP.`,
      confirmLabel: 'Remover do grupo',
      confirmFn: () =>
        router.delete(
          destroyIntegrante.url({
            ...params,
            elementoGrupoPap: elemento.id,
          }),
          { onSuccess: () => router.reload() },
        ),
    });
  };

  const removerGrupo = () => {
    deleteConfirm({
      title: 'Remover grupo PAP?',
      description:
        'Esta acção é irreversível. O grupo PAP e os seus registos dependentes serão removidos.',
      confirmLabel: 'Remover grupo',
      confirmFn: () =>
        router.delete(destroyGrupoPap.url(params), {
          preserveScroll: true,
        }),
    });
  };

  const removerJurado = (bancaJuriPap) => {
    router.delete(destroyJurado.url({ ...params, bancaJuriPap }), {
      onSuccess: () => router.reload(),
    });
  };

  const actualizarNota = (payload, options = {}) => {
    router.put(
      actualizarNotaAction.url({
        ...params,
        elementoGrupoPap: payload.elementoId,
      }),
      payload.data,
      {
        onSuccess: () => {
          options.onSuccess?.();
          router.reload();
        },
        onError: options.onError,
      },
    );
  };

  return {
    editarGrupo,
    definirTema,
    removerIntegrante,
    removerGrupo,
    removerJurado,
    actualizarNota,
  };
}
