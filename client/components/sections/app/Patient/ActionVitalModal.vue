<script setup lang="ts">
import { computed, onMounted, onUnmounted, reactive, ref, watch } from "vue";
import { Activity, Check, LoaderCircle, X } from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import {
    vitalSchema,
    type VitalFormData,
    type Vital,
} from "~/types/medication";
import { getLocalDateStr } from "~/utils/time";
import type { PatientRetrieve } from "~/types/patient";

const props = defineProps<{
    open: boolean;
    submitLoading?: boolean;
    patient: PatientRetrieve;
    vital?: Vital | null;
}>();

const emit = defineEmits<{
    submit: [action: "create" | "update", payload: VitalFormData, id?: string];
    close: [];
}>();

function nowTimeStr() {
    const d = new Date();

    return `${String(d.getHours()).padStart(2, "0")}:${String(
        d.getMinutes(),
    ).padStart(2, "0")}`;
}

function emptyForm(): VitalFormData {
    return {
        bloodPressureSystolic: "",
        bloodPressureDiastolic: "",
        heartRate: "",
        respiratoryRate: "",
        temperature: "",
        oxygenSaturation: "",
        bloodGlucose: "",
        painLevel: "",
        recordedDate: getLocalDateStr(new Date()),
        recordedTime: nowTimeStr(),
        notes: "",
    };
}

const form = reactive<VitalFormData>(emptyForm());

const errors = reactive<Record<string, string>>({
    general: "",
});

function clearError(field: string) {
    delete errors[field];
    errors.general = "";
}

function validate() {
    Object.keys(errors).forEach((key) => delete errors[key]);

    const result = vitalSchema.safeParse(form);

    if (!result.success) {
        result.error.issues.forEach((issue) => {
            const key = issue.path[0];

            if (typeof key === "string") {
                if (!errors[key]) {
                    errors[key] = issue.message;
                }
            } else {
                errors.general = issue.message;
            }
        });

        return false;
    }

    return true;
}

async function onSubmit() {
    if (!validate()) return;

    const payload = { ...form };

    if (props.vital?.id) {
        emit("submit", "update", payload, props.vital.id);
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
    () => [props.open, props.vital] as const,
    ([isOpen, vital]) => {
        if (!isOpen) return;

        Object.keys(errors).forEach((key) => delete errors[key]);

        if (vital) {
            Object.assign(form, {
                bloodPressureSystolic: vital.bloodPressureSystolic ?? "",
                bloodPressureDiastolic: vital.bloodPressureDiastolic ?? "",
                heartRate: vital.heartRate ?? "",
                respiratoryRate: vital.respiratoryRate ?? "",
                temperature: vital.temperature ?? "",
                oxygenSaturation: vital.oxygenSaturation ?? "",
                bloodGlucose: vital.bloodGlucose ?? "",
                painLevel: vital.painLevel ?? "",
                recordedDate: vital.recordedDate ?? "",
                recordedTime: vital.recordedTime ?? "",
                notes: vital.notes ?? "",
                id: vital.id,
            });
        } else {
            Object.assign(form, emptyForm());
        }
    },
    { immediate: true },
);

const bloodPressureDisplay = computed(() => {
    if (!form.bloodPressureSystolic && !form.bloodPressureDiastolic) {
        return "";
    }

    return `${form.bloodPressureSystolic || "--"}/${
        form.bloodPressureDiastolic || "--"
    } mmHg`;
});
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
                        aria-label="Vital Form"
                        class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary"
                    >
                        <div
                            class="sticky top-0 z-10 flex items-center justify-between gap-4 rounded-t-2xl border-b border-gray-100 bg-white/95 px-6 py-5 backdrop-blur dark:bg-secondary/95 dark:border-white/10"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                                >
                                    <Activity class="h-5 w-5" />
                                </div>

                                <div>
                                    <h2
                                        class="text-lg font-semibold leading-tight text-gray-900 dark:text-white"
                                    >
                                        {{
                                            props.vital
                                                ? "Edit Vital"
                                                : "Record Vital"
                                        }}
                                    </h2>

                                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                        {{
                                            props.vital
                                                ? "Update this vital sign record for this patient."
                                                : "Capture a new set of vital signs for this patient."
                                        }}
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="close"
                                aria-label="Close dialog"
                                class="shrink-0 rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
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
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-400"
                                    >
                                        Blood Pressure
                                    </label>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <BaseInput
                                            v-model="form.bloodPressureSystolic"
                                            type="number"
                                            min="0"
                                            placeholder="Systolic e.g. 120"
                                            :error="
                                                errors.bloodPressureSystolic
                                            "
                                            @update:modelValue="
                                                clearError(
                                                    'bloodPressureSystolic',
                                                )
                                            "
                                        />

                                        <BaseInput
                                            v-model="
                                                form.bloodPressureDiastolic
                                            "
                                            type="number"
                                            min="0"
                                            placeholder="Diastolic e.g. 80"
                                            :error="
                                                errors.bloodPressureDiastolic
                                            "
                                            @update:modelValue="
                                                clearError(
                                                    'bloodPressureDiastolic',
                                                )
                                            "
                                        />
                                    </div>

                                    <p
                                        v-if="bloodPressureDisplay"
                                        class="mt-1.5 text-xs text-gray-400 dark:text-gray-500"
                                    >
                                        {{ bloodPressureDisplay }}
                                    </p>
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-3"
                                >
                                    <BaseInput
                                        v-model="form.heartRate"
                                        type="number"
                                        min="0"
                                        label="Heart Rate"
                                        placeholder="e.g. 72"
                                        :error="errors.heartRate"
                                        @update:modelValue="
                                            clearError('heartRate')
                                        "
                                    />

                                    <BaseInput
                                        v-model="form.respiratoryRate"
                                        type="number"
                                        min="0"
                                        label="Respiratory Rate"
                                        placeholder="e.g. 16"
                                        :error="errors.respiratoryRate"
                                        @update:modelValue="
                                            clearError('respiratoryRate')
                                        "
                                    />

                                    <BaseInput
                                        v-model="form.temperature"
                                        type="number"
                                        min="0"
                                        step="0.1"
                                        label="Temperature (°F)"
                                        placeholder="e.g. 98.6"
                                        :error="errors.temperature"
                                        @update:modelValue="
                                            clearError('temperature')
                                        "
                                    />
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                                >
                                    <BaseInput
                                        v-model="form.oxygenSaturation"
                                        type="number"
                                        min="0"
                                        max="100"
                                        label="O2 Saturation (%)"
                                        placeholder="e.g. 98"
                                        :error="errors.oxygenSaturation"
                                        @update:modelValue="
                                            clearError('oxygenSaturation')
                                        "
                                    />

                                    <BaseInput
                                        v-model="form.bloodGlucose"
                                        type="number"
                                        min="0"
                                        label="Blood Glucose (mg/dL)"
                                        placeholder="e.g. 95"
                                        :error="errors.bloodGlucose"
                                        @update:modelValue="
                                            clearError('bloodGlucose')
                                        "
                                    />
                                </div>

                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-400"
                                    >
                                        Pain Level (0-10)
                                    </label>

                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="level in 11"
                                            :key="level - 1"
                                            type="button"
                                            @click="
                                                form.painLevel = String(
                                                    level - 1,
                                                )
                                            "
                                            class="h-9 w-9 rounded-lg border text-sm font-medium dark:border-white/10"
                                            :class="
                                                form.painLevel ===
                                                String(level - 1)
                                                    ? 'border-primary bg-primary/10 text-primary'
                                                    : 'border-gray-200 text-gray-500 dark:border-white/10 dark:text-gray-400'
                                            "
                                        >
                                            {{ level - 1 }}
                                        </button>
                                    </div>
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                                >
                                    <BaseInput
                                        v-model="form.recordedDate"
                                        mode="date"
                                        label="Date"
                                        :error="errors.recordedDate"
                                        @update:modelValue="
                                            clearError('recordedDate')
                                        "
                                    />

                                    <BaseInput
                                        v-model="form.recordedTime"
                                        mode="time"
                                        label="Time"
                                        :error="errors.recordedTime"
                                        @update:modelValue="
                                            clearError('recordedTime')
                                        "
                                    />
                                </div>

                                <BaseInput
                                    v-model="form.notes"
                                    label="Notes"
                                    placeholder="Any additional observations"
                                    :error="errors.notes"
                                    @update:modelValue="clearError('notes')"
                                />
                            </div>

                            <div
                                class="mt-8 flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end dark:border-white/10"
                            >
                                <button
                                    type="button"
                                    @click="close"
                                    class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5"
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
                                            ? "Saving Vital..."
                                            : props.vital
                                              ? "Update Vital"
                                              : "Record Vital"
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
