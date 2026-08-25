<script setup lang="ts">
import { onMounted, onUnmounted, reactive, watch } from "vue";
import { CalendarClock, Check, LoaderCircle, X } from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import { useSchemaValidation } from "~/composables/useSchemaValidation";
import {
    patientActivitySchema,
    activityTypeOptions,
    emptyForm,
    type PatientActivityForm,
    type PatientActivity,
} from "~/types/patient-activity";
import type { PatientRetrieve } from "~/types/patient";

const props = defineProps<{
    open: boolean;
    submitLoading?: boolean;
    patient: PatientRetrieve;
    activity?: PatientActivity | null;
}>();

const emit = defineEmits<{
    submit: [
        action: "create" | "update",
        payload: PatientActivityForm,
        id?: string,
    ];
    close: [];
}>();

const form = reactive<PatientActivityForm>(emptyForm());

const { errors, validate, clearError } = useSchemaValidation(
    patientActivitySchema,
    form,
);

async function onSubmit() {
    if (!validate()) return;

    const payload = { ...form };

    if (props.activity?.id) {
        emit("submit", "update", payload, props.activity.id);
    } else {
        emit("submit", "create", payload);
    }
}

function close() {
    emit("close");
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === "Escape" && props.open) {
        close();
    }
}

onMounted(() => {
    document.addEventListener("keydown", onKeydown, true);
});

onUnmounted(() => {
    document.removeEventListener("keydown", onKeydown, true);
});

watch(
    () => [props.open, props.activity] as const,
    ([isOpen, activity]) => {
        if (!isOpen) return;

        if (activity) {
            Object.assign(form, {
                title: activity.title,
                subtitle: activity.subtitle ?? "",
                description: activity.description ?? "",
                type: activity.type,
                occurredAt: activity.occurredAt,
            });
        } else {
            Object.assign(form, emptyForm());
        }
    },
    { immediate: true },
);
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
                v-if="open"
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
                        v-if="open"
                        role="dialog"
                        aria-modal="true"
                        aria-label="Activity Form"
                        class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
                    >
                        <div
                            class="sticky top-0 z-10 flex items-center justify-between gap-4 rounded-t-2xl border-b border-gray-100 bg-white/95 px-6 py-5 backdrop-blur"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                                >
                                    <CalendarClock class="h-5 w-5" />
                                </div>

                                <div>
                                    <h2
                                        class="text-lg font-semibold leading-tight text-gray-900"
                                    >
                                        {{
                                            props.activity
                                                ? "Edit Activity"
                                                : "Add Activity"
                                        }}
                                    </h2>

                                    <p class="mt-0.5 text-xs text-gray-400">
                                        {{
                                            props.activity
                                                ? "Update this activity for this patient."
                                                : "Log a new activity for this patient."
                                        }}
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="close"
                                aria-label="Close dialog"
                                class="shrink-0 rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <form @submit.prevent="onSubmit" class="p-6">
                            <p
                                v-if="errors.general"
                                class="mb-4 text-center text-sm text-red-500"
                            >
                                {{ errors.general }}
                            </p>

                            <div class="space-y-6">
                                <BaseInput
                                    v-model="form.title"
                                    label="Title"
                                    placeholder="e.g. Doctor consultation"
                                    :error="errors.title"
                                    @update:modelValue="clearError('title')"
                                />

                                <BaseInput
                                    v-model="form.subtitle"
                                    label="Subtitle"
                                    placeholder="e.g. Dr. Gem Manolo — routine check-up"
                                    :error="errors.subtitle"
                                    @update:modelValue="
                                        clearError('subtitle')
                                    "
                                />

                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                                >
                                    <Combobox
                                        v-model="form.type"
                                        label="Type"
                                        placeholder="Select type"
                                        :items="activityTypeOptions"
                                        :error="errors.type"
                                        @update:modelValue="clearError('type')"
                                    />

                                    <BaseInput
                                        v-model="form.occurredAt"
                                        mode="datetime"
                                        label="Date & Time"
                                        :error="errors.occurredAt"
                                        @update:modelValue="
                                            clearError('occurredAt')
                                        "
                                    />
                                </div>

                                <BaseInput
                                    v-model="form.description"
                                    mode="textarea"
                                    :rows="3"
                                    label="Description"
                                    placeholder="Any additional details about this activity"
                                    :error="errors.description"
                                    @update:modelValue="
                                        clearError('description')
                                    "
                                />
                            </div>

                            <div
                                class="mt-8 flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end"
                            >
                                <button
                                    type="button"
                                    @click="close"
                                    class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    :disabled="submitLoading"
                                    class="inline-flex min-w-[170px] items-center justify-center gap-2 rounded-xl bg-primary hover:bg-primary/50 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-70"
                                >
                                    <LoaderCircle
                                        v-if="submitLoading"
                                        class="h-4 w-4 animate-spin"
                                    />

                                    <Check v-else class="h-4 w-4" />

                                    {{
                                        submitLoading
                                            ? "Saving Activity..."
                                            : props.activity
                                              ? "Update Activity"
                                              : "Add Activity"
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
