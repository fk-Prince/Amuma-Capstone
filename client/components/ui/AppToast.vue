<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";

export type ToastType = "success" | "error" | "warning" | "info" | "message";

export interface Toast {
    id: number;
    type: ToastType;
    title: string;
    description?: string;
    duration?: number;
    // When set, the whole toast is clickable and runs this (used by "message").
    onClick?: () => void;
}

const toasts = ref<Toast[]>([]);
let counter = 0;

const styles: Record<
    ToastType,
    {
        wrapper: string;
        iconBg: string;
        icon: string;
        title: string;
        progress: string;
    }
> = {
    success: {
        wrapper: "border-emerald-200 dark:border-emerald-500/20",
        iconBg: "bg-emerald-100 dark:bg-emerald-500/15",
        icon: "text-emerald-600 dark:text-emerald-300",
        title: "text-emerald-900 dark:text-emerald-300",
        progress: "bg-emerald-500",
    },

    error: {
        wrapper: "border-red-200 dark:border-red-500/20",
        iconBg: "bg-red-100 dark:bg-red-500/15",
        icon: "text-red-600 dark:text-red-300",
        title: "text-red-900 dark:text-red-300",
        progress: "bg-red-500",
    },

    warning: {
        wrapper: "border-amber-200 dark:border-amber-500/20",
        iconBg: "bg-amber-100 dark:bg-amber-500/15",
        icon: "text-amber-600 dark:text-amber-300",
        title: "text-amber-900 dark:text-amber-300",
        progress: "bg-amber-500",
    },

    info: {
        wrapper: "border-blue-200 dark:border-blue-500/20",
        iconBg: "bg-blue-100 dark:bg-blue-500/15",
        icon: "text-blue-600 dark:text-blue-300",
        title: "text-blue-900 dark:text-blue-300",
        progress: "bg-blue-500",
    },

    // New message: a solid brand icon and a plain bold title so it stands out.
    message: {
        wrapper: "border-blue-200 dark:border-blue-500/30",
        iconBg: "bg-primary",
        icon: "text-white",
        title: "text-slate-900 dark:text-white",
        progress: "bg-primary",
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

    setTimeout(() => {
        remove(id);
    }, newToast.duration);

    return newToast;
}

function remove(id: number) {
    toasts.value = toasts.value.filter((t) => t.id !== id);
}

function activate(toast: Toast) {
    if (!toast.onClick) return;

    toast.onClick();
    remove(toast.id);
}

onMounted(() => {
    registerToast({ add, remove });
});

onUnmounted(() => {
    registerToast(null as any);
});

defineExpose({
    add,
    remove,
});
</script>

<template>
    <Teleport to="body">
        <div
            aria-live="polite"
            aria-label="Notifications"
            class="fixed right-4 top-[15%] z-[9999] flex w-full max-w-sm flex-col gap-3 pointer-events-none sm:right-6"
        >
            <TransitionGroup
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-4 scale-95"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 translate-y-4 scale-95"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto relative overflow-hidden rounded-2xl border bg-white p-4 shadow-lg dark:bg-secondary dark:border-white/10"
                    :class="[
                        styles[toast.type].wrapper,
                        toast.onClick ? 'cursor-pointer' : '',
                        toast.type === 'message' ? 'toast-glow' : '',
                    ]"
                    role="alert"
                    :tabindex="toast.onClick ? 0 : undefined"
                    @click="activate(toast)"
                    @keydown.enter="activate(toast)"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                            :class="styles[toast.type].iconBg"
                        >
                            <span
                                v-if="toast.type === 'message'"
                                class="toast-ring"
                                aria-hidden="true"
                            />

                            <svg
                                v-if="toast.type === 'success'"
                                class="h-5 w-5"
                                :class="styles[toast.type].icon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                />
                            </svg>

                            <svg
                                v-else-if="toast.type === 'error'"
                                class="h-5 w-5"
                                :class="styles[toast.type].icon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle cx="12" cy="12" r="9" />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 9l6 6M15 9l-6 6"
                                />
                            </svg>

                            <svg
                                v-else-if="toast.type === 'warning'"
                                class="h-5 w-5"
                                :class="styles[toast.type].icon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v4m0 4h.01M10.3 3.8 2.2 18a2 2 0 0 0 1.7 3h16.2a2 2 0 0 0 1.7-3L13.7 3.8a2 2 0 0 0-3.4 0Z"
                                />
                            </svg>

                            <svg
                                v-else-if="toast.type === 'message'"
                                class="relative h-5 w-5"
                                :class="styles[toast.type].icon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path
                                    d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"
                                />
                            </svg>

                            <svg
                                v-else
                                class="h-5 w-5"
                                :class="styles[toast.type].icon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 10v6M12 7h.01" />
                            </svg>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p
                                class="text-sm font-semibold leading-tight"
                                :class="styles[toast.type].title"
                            >
                                {{ toast.title }}
                            </p>

                            <p
                                v-if="toast.description"
                                class="mt-1 text-sm leading-relaxed text-slate-500 dark:text-gray-400"
                            >
                                {{ toast.description }}
                            </p>

                            <p
                                v-if="toast.onClick"
                                class="mt-2 text-xs font-semibold text-primary dark:text-primary-300"
                            >
                                Click to open
                            </p>
                        </div>

                        <!-- Close -->
                        <button
                            type="button"
                            class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
                            aria-label="Close"
                            @click.stop="remove(toast.id)"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div
                        class="absolute bottom-0 left-0 h-1 animate-toast-progress"
                        :class="styles[toast.type].progress"
                        :style="{ animationDuration: `${toast.duration ?? 4000}ms` }"
                    />
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
@keyframes toast-progress {
    from {
        width: 100%;
    }

    to {
        width: 0%;
    }
}

.animate-toast-progress {
    animation: toast-progress 4s linear forwards;
}

/* New message toast: a soft blue glow and a ring around the icon that pulse
   a few times to catch the eye, then settle. */
.toast-glow {
    animation: toast-glow 1.4s ease-out 2;
}

@keyframes toast-glow {
    0%,
    100% {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    40% {
        box-shadow:
            0 0 0 4px rgba(49, 130, 237, 0.28),
            0 10px 24px -6px rgba(49, 130, 237, 0.55);
    }
}

.toast-ring {
    position: absolute;
    inset: 0;
    border: 2px solid rgba(49, 130, 237, 0.6);
    border-radius: 0.75rem;
    opacity: 0;
    animation: toast-ring 1.4s ease-out 3;
}

@keyframes toast-ring {
    0% {
        opacity: 0.8;
        transform: scale(1);
    }
    100% {
        opacity: 0;
        transform: scale(1.7);
    }
}

@media (prefers-reduced-motion: reduce) {
    .toast-glow,
    .toast-ring {
        animation: none;
    }
}
</style>