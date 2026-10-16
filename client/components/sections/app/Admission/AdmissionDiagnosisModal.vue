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
                    class="flex w-full max-w-4xl max-h-[88dvh] flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary"
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
                                monthly case price is added to every admission
                                period. It can be changed until the patient is
                                admitted.
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

                        <div
                            v-else-if="loading"
                            class="grid gap-6 md:grid-cols-2"
                        >
                            <div class="space-y-3">
                                <div
                                    v-for="i in 3"
                                    :key="i"
                                    class="h-10 animate-pulse rounded-xl bg-slate-100 dark:bg-white/10"
                                />
                            </div>
                            <div class="space-y-3">
                                <div
                                    v-for="i in 3"
                                    :key="i"
                                    class="h-10 animate-pulse rounded-xl bg-slate-100 dark:bg-white/10"
                                />
                            </div>
                        </div>

                        <p
                            v-else-if="!cases.length"
                            class="rounded-xl border border-dashed border-slate-200 px-4 py-3 text-sm text-slate-400 dark:border-white/10 dark:text-gray-500"
                        >
                            No diagnosis cases yet. Add them from Contracts.
                        </p>

                        <div v-else class="grid gap-6 md:grid-cols-2">
                            <!-- LEFT · SELECTION -->
                            <section class="min-w-0 space-y-4">
                                <h4
                                    class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                                >
                                    Selection
                                </h4>

                                <Combobox
                                    :key="`diagnosis-${pickKey}`"
                                    v-model="pickedDiagnosis"
                                    label="Patient diagnosis"
                                    placeholder="Select a diagnosis"
                                    search-bar
                                    search-placeholder="Search diagnoses"
                                    :items="diagnosisItems"
                                />

                                <Combobox
                                    :key="`case-${pickKey}`"
                                    :model-value="pickedCase"
                                    label="Diagnosis case"
                                    placeholder="Select a diagnosis case"
                                    search-bar
                                    search-placeholder="Search diagnosis cases"
                                    :items="caseItems"
                                    @update:model-value="selectCase"
                                />

                                <BaseInput
                                    v-model="pickedAmount"
                                    label="Amount per month"
                                    mode="number"
                                    placeholder="0.00"
                                    :error="pickError"
                                    @update:modelValue="pickError = ''"
                                />

                                <BaseInput
                                    v-model="pickedDescription"
                                    label="Description"
                                    mode="textarea"
                                    :textMax="255"
                                    placeholder="Optional note saved with this diagnosis case"
                                />

                                <button
                                    type="button"
                                    class="w-full rounded-xl border-2 border-dashed border-slate-200 py-3 text-sm font-semibold text-primary transition hover:border-primary/40 hover:bg-primary/5 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/10"
                                    :disabled="!pickedDiagnosis || !pickedCase"
                                    @click="addPicked"
                                >
                                    + Add diagnosis case
                                </button>
                            </section>

                            <!-- RIGHT · VIEW / ADD DIAGNOSIS -->
                            <section class="min-w-0 space-y-4">
                                <div class="flex items-center justify-between gap-3">
                                    <h4
                                        class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                                    >
                                        Diagnoses
                                    </h4>

                                    <button
                                        v-if="!addingDiagnosis"
                                        type="button"
                                        class="text-xs font-semibold text-primary transition hover:opacity-80"
                                        @click="addingDiagnosis = true"
                                    >
                                        + Add new diagnosis
                                    </button>
                                </div>

                                <div
                                    v-if="addingDiagnosis"
                                    class="space-y-4 rounded-xl border border-slate-200 p-4 dark:border-white/10"
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
                                            Lab result, medical certificate, or
                                            report (PDF, PNG, or JPG, up to 10MB)
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

                                <p
                                    v-if="!diagnoses.length"
                                    class="rounded-xl border border-dashed border-slate-200 px-4 py-3 text-sm text-slate-400 dark:border-white/10 dark:text-gray-500"
                                >
                                    No recorded diagnoses yet. Add one above.
                                </p>

                                <ul
                                    v-else
                                    class="divide-y divide-slate-100 rounded-xl border border-slate-200 dark:divide-white/10 dark:border-white/10"
                                >
                                    <li
                                        v-for="item in diagnoses"
                                        :key="item.uuid"
                                        class="px-4 py-3"
                                    >
                                        <div
                                            class="flex items-start justify-between gap-3"
                                        >
                                            <div class="min-w-0">
                                                <p
                                                    class="break-words text-sm font-medium text-slate-800 dark:text-white"
                                                >
                                                    {{ item.diagnosis }}
                                                </p>
                                                <p
                                                    v-if="item.diagnosis_date"
                                                    class="mt-0.5 text-[11px] text-slate-400 dark:text-gray-500"
                                                >
                                                    {{ formatDate(item.diagnosis_date) }}
                                                </p>
                                            </div>

                                            <span
                                                v-if="!chosen[item.uuid]"
                                                class="shrink-0 text-[10px] font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                                            >
                                                Not billed
                                            </span>
                                        </div>

                                        <div
                                            v-if="chosen[item.uuid]"
                                            class="mt-2 rounded-lg bg-primary/5 px-3 py-2"
                                        >
                                            <div
                                                class="flex items-start justify-between gap-3"
                                            >
                                                <p
                                                    class="min-w-0 break-words text-xs font-medium text-slate-700 dark:text-gray-300"
                                                >
                                                    {{ caseTitle(chosen[item.uuid]!.case_uuid) }}
                                                </p>
                                                <p
                                                    class="shrink-0 text-xs font-semibold"
                                                >
                                                    {{
                                                        formatCurrency(
                                                            Number(chosen[item.uuid]!.amount),
                                                        )
                                                    }}
                                                    <span
                                                        class="font-normal text-slate-400 dark:text-gray-500"
                                                        >/ mo</span
                                                    >
                                                </p>
                                            </div>

                                            <p
                                                v-if="chosen[item.uuid]!.description"
                                                class="mt-1 break-words text-xs text-slate-500 dark:text-gray-400"
                                            >
                                                {{ chosen[item.uuid]!.description }}
                                            </p>

                                            <div class="mt-2 flex gap-3">
                                                <button
                                                    type="button"
                                                    class="text-xs font-medium text-primary transition hover:opacity-80"
                                                    :disabled="saving"
                                                    @click="editRow(item.uuid)"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    type="button"
                                                    class="text-xs font-medium text-slate-400 transition hover:text-red-500 dark:text-gray-500"
                                                    :disabled="saving"
                                                    @click="removeRow(item.uuid)"
                                                >
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </section>
                        </div>
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
import { diagnosisCaseService } from "~/api/diagnosis-case/DiagnosisCaseService";
import type {
    DiagnosisCase,
    DiagnosisFeeSelection,
} from "~/types/additional-charge";
import { formatCurrency } from "~/utils/currency";
import { formatDate, getLocalDateStr } from "~/utils/time";

interface PatientDiagnosis {
    uuid: string;
    diagnosis: string;
    diagnosis_date: string | null;
    admission_charge?: {
        case_uuid: string | null;
        amount: number;
        description: string | null;
    } | null;
}

interface ChosenCase {
    case_uuid: string;
    amount: number | string;
    description: string;
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

const emit = defineEmits<{
    (e: "close"): void;
    (e: "save", diagnoses: DiagnosisFeeSelection["diagnoses"]): void;
}>();

const isYearly = computed(
    () => String(props.billingCycle ?? "").trim().toLowerCase() === "yearly",
);
const months = computed(() => (isYearly.value ? 12 : 1));

const loading = ref(false);
const loadError = ref("");
const diagnoses = ref<PatientDiagnosis[]>([]);
const cases = ref<DiagnosisCase[]>([]);

// diagnosis uuid -> what it is billed under
const chosen = ref<Record<string, ChosenCase>>({});

const pickedDiagnosis = ref("");
const pickedCase = ref("");
const pickedAmount = ref<number | string>("");
const pickedDescription = ref("");
const pickError = ref("");
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
                admission_charge: null,
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

function caseTitle(caseUuid: string) {
    return cases.value.find((item) => item.uuid === caseUuid)?.title ?? "—";
}

// Picking a case starts the amount from its price, which can then be changed.
function selectCase(uuid: string) {
    pickedCase.value = uuid;

    const item = cases.value.find((entry) => entry.uuid === uuid);

    if (item) pickedAmount.value = Number(item.price);

    pickError.value = "";
}

const fee = computed(() =>
    Object.entries(chosen.value)
        .filter(([diagnosisUuid]) =>
            diagnoses.value.some((item) => item.uuid === diagnosisUuid),
        )
        .reduce((sum, [, value]) => sum + (Number(value.amount) || 0), 0),
);

function resetPick() {
    pickedDiagnosis.value = "";
    pickedCase.value = "";
    pickedAmount.value = "";
    pickedDescription.value = "";
    pickError.value = "";
    pickKey.value++;
}

function addPicked() {
    if (!pickedDiagnosis.value || !pickedCase.value) return;

    const amount = Number(pickedAmount.value);

    if (pickedAmount.value === "" || Number.isNaN(amount) || amount < 0) {
        pickError.value = "Enter an amount of zero or more.";
        return;
    }

    chosen.value = {
        ...chosen.value,
        [pickedDiagnosis.value]: {
            case_uuid: pickedCase.value,
            amount,
            description: pickedDescription.value.trim(),
        },
    };

    resetPick();
}

function editRow(diagnosisUuid: string) {
    const row = chosen.value[diagnosisUuid];

    if (!row) return;

    removeRow(diagnosisUuid);

    // The picker lists only diagnoses that are not billed yet, so remount it
    // after the row has been released.
    pickKey.value++;
    pickedDiagnosis.value = diagnosisUuid;
    pickedCase.value = row.case_uuid;
    pickedAmount.value = row.amount;
    pickedDescription.value = row.description;
}

function removeRow(diagnosisUuid: string) {
    const { [diagnosisUuid]: _removed, ...rest } = chosen.value;

    chosen.value = rest;
}

function save() {
    emit(
        "save",
        Object.entries(chosen.value).map(([diagnosisUuid, value]) => ({
            patient_diagnosis_uuid: diagnosisUuid,
            diagnosis_case_uuid: value.case_uuid,
            amount: Number(value.amount),
            description: value.description || undefined,
        })),
    );
}

async function load() {
    loading.value = true;
    loadError.value = "";
    diagnoses.value = [];
    cases.value = [];
    chosen.value = {};
    resetPick();
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
                .filter((item) => item.admission_charge?.case_uuid)
                .map((item) => [
                    item.uuid,
                    {
                        case_uuid: item.admission_charge!.case_uuid!,
                        amount: Number(item.admission_charge!.amount),
                        description: item.admission_charge!.description ?? "",
                    },
                ]),
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
