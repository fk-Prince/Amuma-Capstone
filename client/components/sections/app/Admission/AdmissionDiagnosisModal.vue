<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[70] flex items-center justify-center bg-black/40 p-5"
            >
                <div
                    class="flex w-full max-w-xl max-h-[88dvh] flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold tracking-tight">
                                Diagnosis Cases
                            </h3>

                            <p
                                class="mt-1 text-sm text-slate-500 dark:text-gray-400"
                            >
                                Pick one of {{ patientName || "the patient" }}'s
                                diagnoses and the case it is billed under. The
                                monthly case price is added to every
                                admission period, starting with this admission
                                and each extension after it, times 12 on a
                                yearly plan. It can be changed until the
                                patient is admitted.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10"
                            aria-label="Close"
                            :disabled="saving"
                            @click="$emit('close')"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 py-5">
                        <p
                            v-if="loadError"
                            class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"
                        >
                            {{ loadError }}
                        </p>

                        <div v-else-if="loading" class="space-y-3">
                            <div
                                class="h-10 animate-pulse rounded-xl bg-slate-100 dark:bg-white/10"
                            />
                            <div
                                class="h-10 animate-pulse rounded-xl bg-slate-100 dark:bg-white/10"
                            />
                        </div>

                        <template v-else>
                            <p
                                v-if="!cases.length"
                                class="rounded-xl border border-dashed border-slate-200 px-4 py-3 text-sm text-slate-400 dark:border-white/10 dark:text-gray-500"
                            >
                                No diagnosis cases yet. Add them from
                                Contracts.
                            </p>

                            <div v-else class="space-y-4">
                                <Combobox
                                    :key="`diagnosis-${pickKey}`"
                                    v-model="pickedDiagnosis"
                                    label="Patient diagnosis"
                                    placeholder="Select a diagnosis"
                                    search-bar
                                    search-placeholder="Search diagnoses"
                                    :items="diagnosisItems"
                                />

                                <button
                                    v-if="!addingDiagnosis"
                                    type="button"
                                    class="-mt-2 text-xs font-semibold text-primary transition hover:opacity-80"
                                    @click="addingDiagnosis = true"
                                >
                                    + Add a new diagnosis to the record
                                </button>

                                <div
                                    v-else
                                    class="-mt-1 space-y-4 rounded-xl border border-slate-200 p-4 dark:border-white/10"
                                >
                                    <p
                                        class="text-sm font-semibold text-slate-700 dark:text-gray-300"
                                    >
                                        New diagnosis
                                    </p>

                                    <BaseInput
                                        v-model="newDiagnosis.diagnosis"
                                        label="Primary Diagnosis"
                                        placeholder="e.g. Type 2 Diabetes"
                                        :error="newErrors.diagnosis"
                                        @update:modelValue="delete newErrors.diagnosis"
                                    />

                                    <div>
                                        <DatePickerField
                                            v-model="newDiagnosis.diagnosis_date"
                                            label="Date Diagnosed"
                                            placeholder="Select date diagnosed"
                                            :min="windowStart"
                                            :max="todayStr"
                                            :default-to-today="false"
                                            :error="newErrors.diagnosis_date"
                                        />
                                        <p
                                            v-if="!newErrors.diagnosis_date"
                                            class="mt-1.5 text-xs text-slate-400 dark:text-gray-500"
                                        >
                                            Must be within the past
                                            {{ DIAGNOSIS_WINDOW_MONTHS }} months.
                                        </p>
                                    </div>

                                    <BaseInput
                                        v-model="newDiagnosis.diagnosis_notes"
                                        label="Diagnosis Notes"
                                        placeholder="Additional details about the diagnosis"
                                    />

                                    <div class="flex flex-col gap-1.5">
                                        <label
                                            class="text-sm font-semibold text-slate-700 dark:text-gray-300"
                                        >
                                            Supporting Document
                                        </label>

                                        <p
                                            class="text-[13px] text-muted dark:text-gray-400"
                                        >
                                            Upload a lab result, medical
                                            certificate, or report (PDF, PNG,
                                            or JPG, up to 10MB)
                                        </p>

                                        <label
                                            class="group flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-200 p-5 text-center transition-colors dark:border-white/10"
                                            :class="
                                                newErrors.diagnosis_file
                                                    ? 'bg-red-50/70 dark:bg-red-500/10'
                                                    : newDiagnosis.file
                                                      ? 'bg-primary/5 dark:bg-primary-500/10'
                                                      : 'bg-slate-50 hover:bg-primary/5 dark:bg-white/5 dark:hover:bg-primary-500/10'
                                            "
                                        >
                                            <input
                                                type="file"
                                                class="hidden"
                                                accept=".pdf,.png,.jpg,.jpeg"
                                                @change="onDiagnosisFile"
                                            />

                                            <template v-if="newDiagnosis.file">
                                                <FileText
                                                    class="h-6 w-6 text-primary"
                                                />
                                                <span
                                                    class="text-sm font-medium text-slate-700 dark:text-gray-300"
                                                >
                                                    {{ newDiagnosis.file.name }}
                                                </span>
                                                <button
                                                    type="button"
                                                    class="text-xs font-medium text-slate-400 underline transition hover:text-red-500"
                                                    @click.prevent="newDiagnosis.file = null"
                                                >
                                                    Remove file
                                                </button>
                                            </template>

                                            <template v-else>
                                                <UploadCloud
                                                    class="h-6 w-6 text-slate-400 transition group-hover:text-primary"
                                                />
                                                <span
                                                    class="text-sm text-muted dark:text-gray-400"
                                                >
                                                    Click to upload
                                                </span>
                                            </template>
                                        </label>

                                        <p
                                            v-if="newErrors.diagnosis_file"
                                            class="text-xs text-red-500"
                                        >
                                            {{ newErrors.diagnosis_file }}
                                        </p>
                                    </div>

                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            class="rounded-lg border px-3 py-1.5 text-xs font-medium transition hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5"
                                            :disabled="savingDiagnosis"
                                            @click="cancelNewDiagnosis"
                                        >
                                            Cancel
                                        </button>

                                        <button
                                            type="button"
                                            class="rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-white transition hover:opacity-90 disabled:opacity-50"
                                            :disabled="savingDiagnosis"
                                            @click="saveNewDiagnosis"
                                        >
                                            {{
                                                savingDiagnosis
                                                    ? "Saving..."
                                                    : "Save diagnosis"
                                            }}
                                        </button>
                                    </div>
                                </div>

                                <Combobox
                                    :key="`case-${pickKey}`"
                                    v-model="pickedCase"
                                    label="Diagnosis case"
                                    placeholder="Select a diagnosis case"
                                    search-bar
                                    search-placeholder="Search diagnosis cases"
                                    :items="caseItems"
                                />

                                <button
                                    type="button"
                                    class="w-full rounded-xl border-2 border-dashed border-slate-200 py-3 text-sm font-semibold text-primary transition hover:border-primary/40 hover:bg-primary/5 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/10"
                                    :disabled="!pickedDiagnosis || !pickedCase"
                                    @click="addPicked"
                                >
                                    + Add diagnosis case
                                </button>
                            </div>

                            <ul
                                v-if="selectedRows.length"
                                class="mt-5 divide-y divide-slate-100 rounded-xl border border-slate-200 dark:divide-white/10 dark:border-white/10"
                            >
                                <li
                                    v-for="row in selectedRows"
                                    :key="row.diagnosis_uuid"
                                    class="flex items-start justify-between gap-3 px-4 py-3"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="break-words text-sm font-medium text-slate-800 dark:text-white"
                                        >
                                            {{ row.diagnosis }}
                                        </p>
                                        <p
                                            class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
                                        >
                                            {{ row.title }}
                                        </p>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-3">
                                        <span class="text-sm font-semibold">
                                            {{ formatCurrency(row.price) }}
                                            <span
                                                class="text-xs font-normal text-slate-400 dark:text-gray-500"
                                                >/ mo</span
                                            >
                                        </span>
                                        <button
                                            type="button"
                                            class="text-xs font-medium text-slate-400 transition hover:text-red-500 dark:text-gray-500"
                                            :disabled="saving"
                                            @click="removeRow(row.diagnosis_uuid)"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </template>
                    </div>

                    <div
                        class="border-t border-slate-100 px-6 py-4 dark:border-white/10"
                    >
                        <div
                            class="mb-3 flex items-center justify-between text-sm"
                        >
                            <span class="text-slate-500 dark:text-gray-400">
                                Diagnosis fees
                                <span
                                    class="text-xs text-slate-400 dark:text-gray-500"
                                >
                                    · {{ isYearly ? "12 months" : "1 month" }}
                                </span>
                            </span>
                            <span class="text-lg font-bold tracking-tight">
                                {{ formatCurrency(fee * months) }}
                            </span>
                        </div>

                        <div class="flex gap-3">
                            <button
                                type="button"
                                class="flex-1 rounded-xl border py-2.5 text-sm font-medium transition hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5"
                                :disabled="saving"
                                @click="$emit('close')"
                            >
                                Cancel
                            </button>

                            <button
                                type="button"
                                class="flex flex-[2] items-center justify-center gap-2 rounded-xl bg-primary py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="saving || loading || !!loadError"
                                @click="save"
                            >
                                {{ saving ? "Saving..." : "Save" }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { useRoute } from "vue-router";

import { FileText, UploadCloud } from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import DatePickerField from "~/components/ui/DatePickerField.vue";
import { patientService } from "~/api/patient/PatientService";
import { useToast } from "~/composables/useToast";
import {
    DIAGNOSIS_WINDOW_MONTHS,
    diagnosisWindowStart,
} from "~/schema/patient-schema";
import { getLocalDateStr } from "~/utils/time";
import { diagnosisCaseService } from "~/api/diagnosis-case/DiagnosisCaseService";
import type {
    DiagnosisCase,
    DiagnosisFeeSelection,
} from "~/types/additional-charge";
import { formatCurrency } from "~/utils/currency";
import { formatDate } from "~/utils/time";

interface PatientDiagnosis {
    uuid: string;
    diagnosis: string;
    diagnosis_date: string | null;
    admission_case_uuid: string | null;
}

const route = useRoute();

const props = defineProps<{
    open: boolean;
    patientUuid: string;
    patientName?: string | null;
    admissionId?: number;
    // The admission's billing cycle, which decides how many months are billed.
    billingCycle?: string | null;
    saving?: boolean;
}>();

const isYearly = computed(
    () => String(props.billingCycle ?? "").trim().toLowerCase() === "yearly",
);
const months = computed(() => (isYearly.value ? 12 : 1));

const emit = defineEmits<{
    (e: "close"): void;
    (e: "save", diagnoses: DiagnosisFeeSelection["diagnoses"]): void;
}>();

const loading = ref(false);
const loadError = ref("");
const diagnoses = ref<PatientDiagnosis[]>([]);
const cases = ref<DiagnosisCase[]>([]);

// diagnosis uuid -> diagnosis case uuid
const chosen = ref<Record<string, string>>({});

const pickedDiagnosis = ref("");
const pickedCase = ref("");
const pickKey = ref(0);

const { error: toastError } = useToast();

const todayStr = getLocalDateStr(new Date());
const windowStart = diagnosisWindowStart();
const MAX_FILE_BYTES = 10 * 1024 * 1024;

const addingDiagnosis = ref(false);
const savingDiagnosis = ref(false);
const newDiagnosis = reactive<{
    diagnosis: string;
    diagnosis_date: string;
    diagnosis_notes: string;
    file: File | null;
}>({ diagnosis: "", diagnosis_date: "", diagnosis_notes: "", file: null });
const newErrors = reactive<Record<string, string>>({});

function clearNewErrors() {
    Object.keys(newErrors).forEach((key) => delete newErrors[key]);
}

function cancelNewDiagnosis() {
    addingDiagnosis.value = false;
    Object.assign(newDiagnosis, {
        diagnosis: "",
        diagnosis_date: "",
        diagnosis_notes: "",
        file: null,
    });
    clearNewErrors();
}

function onDiagnosisFile(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    delete newErrors.diagnosis_file;
    input.value = "";

    if (!file) return;

    if (file.size > MAX_FILE_BYTES) {
        newErrors.diagnosis_file = "File size must not exceed 10MB.";
        return;
    }

    if (!["application/pdf", "image/png", "image/jpeg"].includes(file.type)) {
        newErrors.diagnosis_file = "Only PDF, PNG, and JPG files are allowed.";
        return;
    }

    newDiagnosis.file = file;
}

async function saveNewDiagnosis() {
    clearNewErrors();

    if (!newDiagnosis.diagnosis.trim()) {
        newErrors.diagnosis = "Primary Diagnosis is required.";
    }
    if (!newDiagnosis.diagnosis_date) {
        newErrors.diagnosis_date = "Date Diagnosed is required.";
    } else if (
        newDiagnosis.diagnosis_date < windowStart ||
        newDiagnosis.diagnosis_date > todayStr
    ) {
        newErrors.diagnosis_date = `Diagnosis must be from the past ${DIAGNOSIS_WINDOW_MONTHS} months.`;
    }
    if (Object.keys(newErrors).length) return;

    savingDiagnosis.value = true;

    try {
        const res = await patientService.addDiagnosis(props.patientUuid, {
            branch_uuid: route.params.uuid,
            diagnosis: newDiagnosis.diagnosis.trim(),
            diagnosis_date: newDiagnosis.diagnosis_date,
            diagnosis_notes: newDiagnosis.diagnosis_notes.trim() || undefined,
            diagnosis_file: newDiagnosis.file ?? undefined,
        });

        const created = res.diagnosis;

        diagnoses.value = [
            {
                uuid: created.uuid,
                diagnosis: created.diagnosis,
                diagnosis_date: created.diagnosis_date,
                admission_case_uuid: null,
            },
            ...diagnoses.value,
        ];
        pickedDiagnosis.value = created.uuid;
        cancelNewDiagnosis();
    } catch (err: any) {
        const first = Object.values(err?.errors ?? {})[0];

        toastError(
            (Array.isArray(first) ? first[0] : first) ||
                err?.data?.message ||
                err?.message ||
                "Could not add the diagnosis.",
        );
    } finally {
        savingDiagnosis.value = false;
    }
}

const diagnosisItems = computed(() =>
    diagnoses.value
        .filter((item) => !chosen.value[item.uuid])
        .map((item) => ({
            label: item.diagnosis_date
                ? `${item.diagnosis} — ${formatDate(item.diagnosis_date)}`
                : item.diagnosis,
            value: item.uuid,
        })),
);

const caseItems = computed(() =>
    cases.value.map((item) => ({
        label: `${item.title} — ${formatCurrency(item.price)} / month`,
        value: item.uuid,
    })),
);

const selectedRows = computed(() =>
    Object.entries(chosen.value)
        .map(([diagnosisUuid, caseUuid]) => {
            const diagnosis = diagnoses.value.find(
                (item) => item.uuid === diagnosisUuid,
            );
            const diagnosisCase = cases.value.find(
                (item) => item.uuid === caseUuid,
            );

            return diagnosis && diagnosisCase
                ? {
                      diagnosis_uuid: diagnosisUuid,
                      diagnosis: diagnosis.diagnosis,
                      title: diagnosisCase.title,
                      price: Number(diagnosisCase.price),
                  }
                : null;
        })
        .filter((row): row is NonNullable<typeof row> => row !== null),
);

const fee = computed(() =>
    selectedRows.value.reduce((sum, row) => sum + row.price, 0),
);

function addPicked() {
    if (!pickedDiagnosis.value || !pickedCase.value) return;

    chosen.value = {
        ...chosen.value,
        [pickedDiagnosis.value]: pickedCase.value,
    };
    pickedDiagnosis.value = "";
    pickedCase.value = "";
    pickKey.value++;
}

function removeRow(diagnosisUuid: string) {
    const { [diagnosisUuid]: _removed, ...rest } = chosen.value;

    chosen.value = rest;
}

function save() {
    emit(
        "save",
        selectedRows.value.map((row) => ({
            patient_diagnosis_uuid: row.diagnosis_uuid,
            diagnosis_case_uuid: chosen.value[row.diagnosis_uuid]!,
        })),
    );
}

async function load() {
    loading.value = true;
    loadError.value = "";
    diagnoses.value = [];
    cases.value = [];
    chosen.value = {};
    pickedDiagnosis.value = "";
    pickedCase.value = "";
    cancelNewDiagnosis();

    try {
        const branch_uuid = route.params.uuid as string;

        const [caseList, diagnosisList] = await Promise.all([
            diagnosisCaseService.list({ branch_uuid, for: "charges" }),
            diagnosisCaseService.patientDiagnoses({
                branch_uuid,
                patient_uuid: props.patientUuid,
                ...(props.admissionId
                    ? { patient_admission_id: props.admissionId }
                    : {}),
            }),
        ]);

        cases.value = caseList.data ?? [];
        diagnoses.value = diagnosisList.data ?? [];

        chosen.value = Object.fromEntries(
            diagnoses.value
                .filter((item) => item.admission_case_uuid)
                .map((item) => [item.uuid, item.admission_case_uuid!]),
        );
    } catch (err: any) {
        loadError.value =
            err?.data?.message ??
            err?.message ??
            "Couldn't load the patient's diagnoses.";
    } finally {
        loading.value = false;
    }
}

watch(
    () => props.open,
    (open) => {
        if (open) load();
    },
    { immediate: true },
);
</script>
