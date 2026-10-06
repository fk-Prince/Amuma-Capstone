import { ref } from "vue";
import type { Toast, ToastType } from "@/components/ui/AppToast.vue";

type ToastPayload = Omit<Toast, "id">;

type ToastInstance = {
    add: (t: ToastPayload) => { id: number };
    remove: (id: number) => void;
};

const _toastRef = ref<ToastInstance | null>(null);

let lastToastId: number | null = null;
let lastToastKey = "";
let lastToastAt = 0;

export function registerToast(instance: ToastInstance) {
    _toastRef.value = instance;
}


export function useToast() {
    function show(payload: ToastPayload) {
        const toast = _toastRef.value;
        if (!toast) return;


        const key = `${payload.type}:${payload.title}`;
        const now = Date.now();

        if (key === lastToastKey && now - lastToastAt < 1500) return;

        lastToastKey = key;
        lastToastAt = now;

        if (lastToastId !== null) {
            toast.remove(lastToastId);
        }

        requestAnimationFrame(() => {
            const created = toast.add(payload);
            lastToastId = created.id;
        });
    }

    function success(title: string, description?: string) {
        show({ type: "success", title, description });
    }

    function error(title: string, description?: string) {
        show({ type: "error", title, description });
    }

    function warning(title: string, description?: string) {
        show({ type: "warning", title, description });
    }

    function info(title: string, description?: string) {
        show({ type: "info", title, description });
    }

    return { show, success, error, warning, info };
}