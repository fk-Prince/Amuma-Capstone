<script setup lang="ts">
import { onMounted, onUnmounted } from "vue";
import {
    BedSingle,
    Crown,
    CircleCheck,
    CircleX,
    Wrench,
    X,
    Check,
    LoaderCircle,
} from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import type { RoomForm } from "~/types/room";
import { roomSchema } from "~/types/room";
import { useSchemaValidation } from "~/composables/useSchemaValidation";

const emit = defineEmits<{
    close: [];
    submit: [];
}>();

const props = defineProps<{
    form: RoomForm;
    title?: string;
    subtitle?: string;
    buttonTitle?: string;
    submitLoading?: boolean;
    errors?: Record<string, string>;
}>();

const {
    errors: schemaErrors,
    validate,
    clearError,
    reset,
} = useSchemaValidation(roomSchema, props.form);

const fieldErrors = computed(() => ({
    ...schemaErrors.value,
    ...(props.errors ?? {}),
}));

const onSubmit = () => {
    if (!validate()) return;

    emit("submit");
};

const onKeydown = (e: KeyboardEvent) => {
    if (e.key === "Escape") {
        e.preventDefault();
        emit("close");
    }
};

const close = () => {
    reset();
    emit("close");
};
onMounted(() => document.addEventListener("keydown", onKeydown, true));
onUnmounted(() => document.removeEventListener("keydown", onKeydown, true));
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
            >
                <Transition
                    appear
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary"
                        role="dialog"
                        aria-modal="true"
                        :aria-label="title"
                    >
                        <div
                            class="sticky top-0 z-10 flex items-center justify-between gap-4 rounded-t-2xl border-b border-gray-100 bg-white/95 px-6 py-5 backdrop-blur dark:bg-secondary/95 dark:border-white/10"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        class="h-5 w-5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            d="M4 21V7a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v14M14 9h4a2 2 0 0 1 2 2v10M4 21h16"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                        <path
                                            d="M9 12v.01"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h2
                                        class="text-lg font-semibold leading-tight text-gray-900 dark:text-white"
                                    >
                                        {{ title }}
                                    </h2>
                                    <p
                                        v-if="subtitle"
                                        class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        {{ subtitle }}
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="emit('close')"
                                aria-label="Close dialog"
                                class="shrink-0 rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <form @submit.prevent="onSubmit" class="p-6">
                            <div class="space-y-6">
                                <BaseInput
                                    v-model="form.room_no"
                                    :error="fieldErrors?.room_no"
                                    label="Room Number"
                                    placeholder="e.g. Room 101"
                                    @update:modelValue="clearError('room_no')"
                                />

                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                                >
                                    <BaseInput
                                        v-model="form.floor"
                                        label="Floor"
                                        placeholder="e.g. 1st"
                                        :error="fieldErrors?.floor"
                                        @update:modelValue="clearError('floor')"
                                    >
                                        <template #suffix>
                                            <span
                                                class="border-l border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400"
                                            >
                                                Floor
                                            </span>
                                        </template>
                                    </BaseInput>

                                    <Combobox
                                        v-model="form.room_type"
                                        :error="fieldErrors?.room_type"
                                        label="Room Type"
                                        placeholder="Select room type"
                                        @update:modelValue="
                                            clearError('room_type')
                                        "
                                        :items="[
                                            {
                                                label: 'Common Room',
                                                value: 'Common',
                                                iconComponent: BedSingle,
                                            },
                                            {
                                                label: 'VIP Room',
                                                value: 'VIP',
                                                iconComponent: Crown,
                                            },
                                        ]"
                                    />
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                                >
                                    <BaseInput
                                        v-model="form.capacity"
                                        :error="fieldErrors?.capacity"
                                        @update:modelValue="
                                            clearError('capacity')
                                        "
                                        type="number"
                                        min="1"
                                        label="Maximum Capacity"
                                        placeholder="e.g. 4"
                                    />

                                    <Combobox
                                        v-model="form.status"
                                        :error="fieldErrors?.status"
                                        @update:modelValue="
                                            clearError('status')
                                        "
                                        position="top"
                                        label="Room Status"
                                        placeholder="Select room status"
                                        :items="[
                                            {
                                                label: 'Available',
                                                value: 'Available',
                                                iconComponent: CircleCheck,
                                            },
                                            {
                                                label: 'Occupied',
                                                value: 'Occupied',
                                                iconComponent: CircleX,
                                            },
                                            {
                                                label: 'Under Maintenance',
                                                value: 'Maintenance',
                                                iconComponent: Wrench,
                                            },
                                        ]"
                                    />
                                </div>
                            </div>

                            <div
                                class="mt-8 flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end dark:border-white/10"
                            >
                                <button
                                    type="button"
                                    @click="emit('close')"
                                    class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-300 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5 dark:focus-visible:ring-white/10"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    :disabled="submitLoading"
                                    class="inline-flex min-w-[150px] items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 disabled:cursor-not-allowed disabled:opacity-70"
                                >
                                    <LoaderCircle
                                        v-if="submitLoading"
                                        class="h-4 w-4 animate-spin"
                                    />

                                    <Check v-else class="h-4 w-4" />

                                    {{
                                        submitLoading
                                            ? "Saving Room..."
                                            : buttonTitle
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
