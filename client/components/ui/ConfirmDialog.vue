<script setup lang="ts">
import { LoaderCircle } from "lucide-vue-next";

const props = withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        message: string;
        description?: string;
        confirmLabel?: string;
        cancelLabel?: string;
        loading?: boolean;
        variant?: "default" | "danger";
    }>(),
    {
        description: "",
        confirmLabel: "Confirm",
        cancelLabel: "Cancel",
        variant: "default",
        loading: false,
    },
);

const emit = defineEmits<{
    confirm: [];
    cancel: [];
}>();
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/50 p-4"
                @click.self="!props.loading && emit('cancel')"
            >
                <div
                    role="alertdialog"
                    aria-modal="true"
                    :aria-label="title"
                    class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl ring-1 ring-black/5 dark:bg-secondary"
                >
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ title }}
                    </h3>

                    <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
                        {{ message }}
                    </p>

                    <p v-if="description" class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                        {{ description }}
                    </p>

                    <div class="mt-5 flex justify-end gap-2.5">
                        <button
                            type="button"
                            :disabled="props.loading"
                            class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5"
                            @click="emit('cancel')"
                        >
                            {{ cancelLabel }}
                        </button>

                        <button
                            type="button"
                            :disabled="props.loading"
                            class="flex min-w-[90px] items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-70"
                            :class="
                                variant === 'danger'
                                    ? 'bg-rose-500 hover:bg-rose-600'
                                    : 'bg-primary hover:bg-primary/60'
                            "
                            @click="emit('confirm')"
                        >
                            <LoaderCircle
                                v-if="props.loading"
                                class="h-4 w-4 shrink-0 animate-spin"
                            />
                            {{ props.loading ? "Saving..." : confirmLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
