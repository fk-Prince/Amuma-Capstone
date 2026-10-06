<template>
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
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
        >
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" />

            <form
                class="relative z-50 flex max-h-[90dvh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary"
                @submit.prevent="submit"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-4 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <p
                            class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500"
                        >
                            Edit diagnosis
                        </p>

                        <h2
                            class="mt-1 truncate text-lg font-semibold text-slate-800 dark:text-white"
                        >
                            {{ patientName }}
                        </h2>
                    </div>

                    <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                        @click="close"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-6">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <BaseInput
                            label="Primary Diagnosis"
                            placeholder="e.g. Type 2 Diabetes"
                            required
                            :model-value="form.diagnosis"
                            :error="errors.diagnosis"
                            @update:model-value="set('diagnosis', $event)"
                        />

                        <DatePickerField
                            label="Date Diagnosed"
                            placeholder="Select date diagnosed"
                            required
                            :default-to-today="false"
                            :model-value="form.diagnosis_date"
                            :error="errors.diagnosis_date"
                            @update:model-value="set('diagnosis_date', $event)"
                        />
                    </div>

                    <BaseInput
                        label="Diagnosis Notes"
                        placeholder="Additional details"
                        :model-value="form.diagnosis_notes"
                        :error="errors.diagnosis_notes"
                        @update:model-value="set('diagnosis_notes', $event)"
                    />

                    <div class="flex flex-col gap-1.5">
                        <label
                            class="text-sm font-semibold text-slate-700 dark:text-gray-400"
                        >
                            Supporting Document
                        </label>

                        <label
                            class="group flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 border-dashed p-4 text-center text-sm transition"
                            :class="
                                errors.diagnosis_file
                                    ? 'border-red-300 bg-red-50/70'
                                    : file
                                      ? 'border-primary/40 bg-primary/5'
                                      : 'border-slate-200 bg-slate-50 hover:bg-primary/5 dark:border-white/10 dark:bg-white/5'
                            "
                        >
                            <input
                                type="file"
                                class="hidden"
                                accept=".pdf,.png,.jpg,.jpeg"
                                @change="onFileChange"
                            />

                            <template v-if="file">
                                <FileText class="h-4 w-4 text-primary" />
                                <span
                                    class="font-medium text-slate-700 dark:text-gray-400"
                                >
                                    {{ file.name }}
                                </span>
                                <button
                                    type="button"
                                    class="text-xs text-slate-400 underline hover:text-red-500 dark:text-gray-500"
                                    @click.prevent="file = null"
                                >
                                    Remove
                                </button>
                            </template>

                            <template v-else>
                                <UploadCloud
                                    class="h-4 w-4 text-slate-400 group-hover:text-primary dark:text-gray-500"
                                />
                                <span class="text-muted dark:text-gray-400">
                                    {{
                                        diagnosis?.diagnosis_file
                                            ? "Click to replace the current file"
                                            : "Click to upload a PDF, PNG or JPG (up to 10MB)"
                                    }}
                                </span>
                            </template>
                        </label>

                        <p v-if="errors.diagnosis_file" class="text-xs text-red-500">
                            {{ errors.diagnosis_file }}
                        </p>
                    </div>
                </div>

                <div
                    class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-100 px-6 py-4 dark:border-white/10"
                >
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-white/10"
                        @click="close"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        :disabled="saving"
                        class="flex items-center gap-2 rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <Loader2 v-if="saving" class="h-4 w-4 animate-spin" />
                        {{ saving ? "Saving..." : "Save changes" }}
                    </button>
                </div>
            </form>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { reactive, ref, watch } from "vue";
import { FileText, Loader2, UploadCloud, X } from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import DatePickerField from "~/components/ui/DatePickerField.vue";
import { patientService } from "~/api/patient/PatientService";
import { useToast } from "~/composables/useToast";

interface DiagnosisEntry {
    uuid: string;
    diagnosis: string | null;
    diagnosis_date: string | null;
    diagnosis_notes: string | null;
    diagnosis_file: string | null;
}

const ALLOWED_TYPES = ["application/pdf", "image/png", "image/jpeg"];

const props = defineProps<{
    open: boolean;
    diagnosis: DiagnosisEntry | null;
    patientUuid: string;
    patientName: string;
    branchUuid: string;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "saved", diagnosis: DiagnosisEntry): void;
}>();

const { success, error } = useToast();

const saving = ref(false);
const errors = ref<Record<string, string>>({});
const file = ref<File | null>(null);

const form = reactive({
    diagnosis: "",
    diagnosis_date: "",
    diagnosis_notes: "",
});

function fill() {
    form.diagnosis = props.diagnosis?.diagnosis ?? "";
    form.diagnosis_date = props.diagnosis?.diagnosis_date ?? "";
    form.diagnosis_notes = props.diagnosis?.diagnosis_notes ?? "";
    file.value = null;
    errors.value = {};
}

function set(key: keyof typeof form, value: string) {
    form[key] = value;
    delete errors.value[key];
}

function onFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const picked = input.files?.[0];

    delete errors.value.diagnosis_file;

    if (!picked) return;

    if (picked.size > 10 * 1024 * 1024) {
        errors.value.diagnosis_file = "File must be 10MB or smaller.";
        input.value = "";
        return;
    }

    if (!ALLOWED_TYPES.includes(picked.type)) {
        errors.value.diagnosis_file = "Only PDF, PNG, and JPG files are allowed.";
        input.value = "";
        return;
    }

    file.value = picked;
}

async function submit() {
    if (saving.value || !props.diagnosis) return;

    errors.value = {};

    if (!form.diagnosis.trim()) {
        errors.value.diagnosis = "Primary Diagnosis is required";
    }

    if (!form.diagnosis_date) {
        errors.value.diagnosis_date = "Date Diagnosed is required";
    }

    if (Object.keys(errors.value).length) return;

    saving.value = true;

    try {
        const res: any = await patientService.updateDiagnosis(
            props.patientUuid,
            props.diagnosis.uuid,
            {
                branch_uuid: props.branchUuid,
                diagnosis: form.diagnosis.trim(),
                diagnosis_date: form.diagnosis_date,
                diagnosis_notes: form.diagnosis_notes || undefined,
                ...(file.value ? { diagnosis_file: file.value } : {}),
            },
        );

        success(res?.message ?? "Diagnosis updated.");
        emit("saved", res.diagnosis);
        close();
    } catch (err: any) {
        const fieldErrors = err?.errors ?? {};

        errors.value = Object.fromEntries(
            Object.entries(fieldErrors).map(([key, value]: any) => [
                key,
                Array.isArray(value) ? value[0] : value,
            ]),
        );

        error(err?.message ?? "Unable to update this diagnosis.");
    } finally {
        saving.value = false;
    }
}

function close() {
    emit("close");
}

watch(
    () => props.open,
    (open) => {
        if (open) fill();
    },
    { immediate: true },
);
</script>
