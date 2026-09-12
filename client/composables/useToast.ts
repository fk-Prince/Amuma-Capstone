import { ref } from "vue";
import type { Toast, ToastType } from "@/components/ui/AppToast.vue";
import { useSound } from "~/composables/useSound";

type ToastPayload = Omit<Toast, "id">;

type ToastInstance = {
    add: (t: ToastPayload) => { id: number };
    remove: (id: number) => void;
};

const _toastRef = ref<ToastInstance | null>(null);

let lastToastId: number | null = null;

export function registerToast(instance: ToastInstance) {
    _toastRef.value = instance;
}


export function useToast() {
    const sound = useSound();

    function show(payload: ToastPayload) {
        const toast = _toastRef.value;
        if (!toast) return;

        if (lastToastId !== null) {
            toast.remove(lastToastId);
        }

        requestAnimationFrame(() => {
            const created = toast.add(payload);
            lastToastId = created.id;
        });
    }

    function success(title: string, description?: string) {
        sound.playSuccess();
        show({ type: "success", title, description });
    }

    function error(title: string, description?: string) {
        sound.playError();
        show({ type: "error", title, description });
    }

    function warning(title: string, description?: string) {
        sound.playNotification();
        show({ type: "warning", title, description });
    }

    function info(title: string, description?: string) {
        sound.playNotification();
        show({ type: "info", title, description });
    }

    return { show, success, error, warning, info };
}