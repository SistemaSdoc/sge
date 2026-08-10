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

        return () => {
            removeListener();
            flashListenerInstalled = false;
        };
    }, []);
}
