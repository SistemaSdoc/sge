import { router } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';

let flashListenerInstalled = false;

export function useFlashToast(): void {
  const lastToastRef = useRef<string | null>(null);

  useEffect(() => {
    if (flashListenerInstalled) {
      return;
    }

    flashListenerInstalled = true;

    const removeListener = router.on('flash', (event) => {
      const flash = (event as CustomEvent).detail?.flash;
      const data = flash?.toast as FlashToast | undefined;

      if (!data) {
        return;
      }

      const toastKey = `${data.type}:${data.message}`;

      if (toastKey === lastToastRef.current) {
        return;
      }

      lastToastRef.current = toastKey;
      setTimeout(() => {
        lastToastRef.current = null;
      }, 500);

      toast[data.type](data.message);
    });

    const url = new URL(window.location.href);
    const linkResultMessages: Record<string, Record<string, FlashToast>> = {
      facebook_link: {
        success: {
          type: 'success',
          message: 'A conta do Facebook foi associada com sucesso.',
        },
        cancelled: {
          type: 'warning',
          message: 'A associação com o Facebook foi cancelada.',
        },
        error: {
          type: 'error',
          message: 'Não foi possível associar o Facebook. Tente novamente.',
        },
      },
      google_link: {
        success: {
          type: 'success',
          message: 'A conta Google foi associada com sucesso.',
        },
        cancelled: {
          type: 'warning',
          message: 'A associação com o Google foi cancelada.',
        },
        error: {
          type: 'error',
          message: 'Não foi possível associar o Google. Tente novamente.',
        },
      },
    };
    let hasLinkResult = false;

    Object.entries(linkResultMessages).forEach(([queryKey, messages]) => {
      const result = url.searchParams.get(queryKey);
      const message = result ? messages[result] : undefined;

      if (!message) {
        return;
      }

      toast[message.type](message.message);
      url.searchParams.delete(queryKey);
      hasLinkResult = true;
    });

    if (hasLinkResult) {
      window.history.replaceState(
        window.history.state,
        '',
        `${url.pathname}${url.search}${url.hash}`,
      );
    }

    return () => {
      removeListener();
      flashListenerInstalled = false;
    };
  }, []);
}
