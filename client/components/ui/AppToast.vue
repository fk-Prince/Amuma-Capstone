<script setup lang="ts">
import { ref } from "vue";

export type ToastType = "success" | "error" | "warning" | "info";

export interface Toast {
    id: number;
    type: ToastType;
    title: string;
    description?: string;
    duration?: number;
}

const toasts = ref<Toast[]>([]);
let counter = 0;

const styles: Record<
    ToastType,
    { wrapper: string; icon: string; title: string }
> = {
    success: {
        wrapper:
            "bg-secondary border border-green-200 text-white px-4 py-3 rounded-xl shadow-md",
        icon: "text-green-500",
        title: "text-white",
    },
    error: {
        wrapper:
            "border-red-200 bg-secondary text-red-900 px-4 py-3 rounded-xl shadow-sm backdrop-blur",
        icon: "text-red-500",
        title: "text-red-900",
    },

    warning: {
        wrapper:
            "border-amber-200 bg-secondary text-amber-900 px-4 py-3 rounded-xl shadow-sm backdrop-blur",
        icon: "text-amber-500",
        title: "text-amber-900 font-medium",
    },

    info: {
        wrapper:
            "border-blue-200 bg-secondary text-blue-900 px-4 py-3 rounded-xl shadow-sm backdrop-blur",
        icon: "text-blue-500",
        title: "text-blue-900 font-medium",
    },
};
function add(toast: Omit<Toast, "id">) {
    const id = ++counter;

    const newToast: Toast = {
        id,
        duration: 4000,
        ...toast,
    };

    toasts.value.push(newToast);

    setTimeout(() => remove(id), toast.duration ?? 4000);

    return newToast;
}

onMounted(() => {
    registerToast({ add, remove });
});
onUnmounted(() => {
    registerToast(null as any);
});

function remove(id: number) {
    toasts.value = toasts.value.filter((t) => t.id !== id);
}
defineExpose({ add, remove });
</script>

<template>
    <Teleport to="body">
        <div
            aria-live="polite"
            aria-label="Notifications"
            class="fixed top-[15%] right-1/2 translate-x-1/2 z-[9999] flex flex-col gap-2.5 items-end pointer-events-none"
        >
            <TransitionGroup
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 translate-x-6 -translate-y-4 scale-95"
                enter-to-class="opacity-100 translate-x-0 translate-y-0 scale-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 translate-x-0 translate-y-0 scale-100"
                leave-to-class="opacity-0 translate-x-6 -translate-y-4 scale-95"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto flex items-start gap-4 px-5 py-5 rounded-2xl shadow-lg min-w-[400px] max-w-[420px]"
                    :class="styles[toast.type].wrapper"
                    role="alert"
                >
                    <svg
                        v-if="toast.type === 'success'"
                        class="w-5 h-5 flex-shrink-0"
                        :class="styles[toast.type].icon"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"
                        />
                    </svg>
                    <svg
                        v-else-if="toast.type === 'error'"
                        class="w-5 h-5 flex-shrink-0"
                        :class="styles[toast.type].icon"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"
                        />
                    </svg>
                    <svg
                        v-else-if="toast.type === 'warning'"
                        class="w-5 h-5 flex-shrink-0"
                        :class="styles[toast.type].icon"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"
                        />
                    </svg>
                    <svg
                        v-else
                        class="w-5 h-5 flex-shrink-0"
                        :class="styles[toast.type].icon"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9-3.75h.008v.008H12V8.25z"
                        />
                    </svg>

                    <div class="flex-1 min-w-0">
                        <p
                            class="text-sm font-semibold leading-snug"
                            :class="styles[toast.type].title"
                        >
                            {{ toast.title }}
                        </p>
                        <p
                            v-if="toast.description"
                            class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed"
                        >
                            {{ toast.description }}
                        </p>
                    </div>

                    <button
                        type="button"
                        aria-label="Dismiss notification"
                        class="flex-shrink-0 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors mt-0.5"
                        @click="remove(toast.id)"
                    >
                        <svg
                            class="w-4 h-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
