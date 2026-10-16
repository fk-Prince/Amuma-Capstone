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
                                Every diagnosis case added here is billed to
                                {{ patientName || "the patient" }} on one new
                                invoice. The monthly case price is added to
                                every admission period, and a diagnosis case
                                can't be removed once it is added.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10"
                            aria-label="Close"
                            :disabled="submitting"
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
                                    v-for="i in 4"
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

                        <div v-else class="grid gap-6 md:grid-cols-[1fr_auto_1fr]">
                            <!-- LEFT · SELECTION -->
                            <section class="min-w-0">
                                <div
                                    v-if="entries.length > 1"
                                    class="mb-5 flex flex-wrap items-center gap-2"
                                >
                                    <button
                                        v-for="(entry, index) in entries"
                                        :key="entry.key"
                                        type="button"
                                        class="group flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition-colors"
                                        :class="
                                            index === activeIndex
                                                ? 'bg-primary text-white'
                                                : 'bg-slate-50 text-slate-500 hover:bg-slate-100 dark:bg-secondary dark:text-gray-400 dark:border dark:border-white/10 dark:hover:bg-white/5'
                                        "
                                        @click="activeIndex = index"
                                    >
                                        <span
                                            class="flex h-5 w-5 items-center justify-center rounded-full text-[11px] font-semibold"
                                            :class="
                                                entryHasError(index)
                                                    ? 'bg-red-500 text-white'
                                                    : index === activeIndex
                                                      ? 'bg-white/20 text-white'
                                                      : 'bg-white text-slate-400 dark:bg-white/10 dark:text-gray-500'
                                            "
                                        >
                                            {{ index + 1 }}
                                        </span>

                                        Case {{ index + 1 }}

                                        <XIcon
                                            class="h-3.5 w-3.5 opacity-0 transition-opacity group-hover:opacity-100"
                                            :class="
                                                index === activeIndex
                                                    ? 'text-white/80 hover:text-white'
                                                    : 'text-slate-400 hover:text-red-500 dark:text-gray-500'
                                            "
                                            @click.stop="removeEntry(index)"
                                        />
                                    </button>

                                    <button
                                        type="button"
                                        class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-50 text-slate-400 transition hover:bg-primary/10 hover:text-primary dark:bg-secondary dark:text-gray-500"
                                        @click="addEntry"
                                    >
                                        <Plus class="h-4 w-4" />
                                    </button>
                                </div>

                                <div class="space-y-4">
                                    <div
                                        v-if="entries.length > 1"
                                        class="flex items-center justify-between"
                                    >
                                        <h4
                                            class="text-sm font-semibold text-slate-700 dark:text-gray-300"
                                        >
                                            Case {{ activeIndex + 1 }}
                                        </h4>

                                        <button
                                            type="button"
                                            class="text-sm font-medium text-slate-400 transition hover:text-red-500 dark:text-gray-500"
                                            :disabled="submitting"
                                            @click="removeEntry(activeIndex)"
                                        >
                                            Remove
                                        </button>
                                    </div>

                                    <Combobox
                                        :key="`diagnosis-${active.key}-${active.diagnosis_uuid}`"
                                        :model-value="active.diagnosis_uuid"
                                        label="Patient diagnosis"
                                        placeholder="Select or add a diagnosis"
                                        required
                                        search-bar
                                        search-placeholder="Search diagnoses"
                                        :items="diagnosisItems"
                                        :error="errorFor(activeIndex, 'patient_diagnosis_uuid')"
                                        @update:model-value="pickDiagnosis"
                                    />

                                    <Combobox
                                        :key="`case-${active.key}-${active.case_uuid}`"
                                        :model-value="active.case_uuid"
                                        label="Diagnosis case"
                                        placeholder="Select a diagnosis case"
                                        required
                                        search-bar
                                        search-placeholder="Search diagnosis cases"
                                        :items="caseItems"
                                        :error="errorFor(activeIndex, 'diagnosis_case_uuid')"
                                        @update:model-value="selectCase"
                                    />

                                    <BaseInput
                                        :key="`amount-${active.key}`"
                                        v-model="active.amount"
                                        label="Amount per month"
                                        mode="number"
                                        placeholder="0.00"
                                        required
                                        :error="errorFor(activeIndex, 'amount')"
                                        @update:modelValue="clearError(activeIndex, 'amount')"
                                    />

                                    <BaseInput
                                        :key="`description-${active.key}`"
                                        v-model="active.description"
                                        label="Description"
                                        mode="textarea"
                                        :textMax="255"
                                        placeholder="Optional note saved with this diagnosis case"
                                        :error="errorFor(activeIndex, 'description')"
                                    />
                                </div>

                                <button
                                    type="button"
                                    class="mt-5 w-full rounded-xl border-2 border-dashed border-slate-200 py-3 text-sm font-semibold text-primary transition hover:border-primary/40 hover:bg-primary/5 disabled:opacity-50 dark:border-white/10"
                                    :disabled="submitting"
                                    @click="addEntry"
                                >
                                    + Add another diagnosis case
                                </button>

                                <p
                                    v-if="submitError"
                                    class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-600 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"
                                >
                                    {{ submitError }}
                                </p>
                            </section>

                            <div
                                class="hidden w-px bg-slate-200 dark:bg-white/10 md:block"
                                aria-hidden="true"
                            />

                            <!-- RIGHT · VIEW / ADD DIAGNOSIS -->
                            <section class="min-w-0 space-y-4">
                                <div
                                    class="flex flex-wrap items-center justify-between gap-3"
                                >
                                    <h4
                                        class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                                    >
                                        Diagnoses
                                    </h4>

                                    <div class="flex items-center gap-3">
                                        <button
                                            v-if="billedRows.length"
                                            type="button"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-primary transition hover:opacity-80"
                                            @click="openSlip(billedRows)"
                                        >
                                            <Printer class="h-3.5 w-3.5" />
                                            Print all
                                        </button>

                                        <button
                                            v-if="!addingDiagnosis"
                                            type="button"
                                            class="text-xs font-semibold text-primary transition hover:opacity-80"
                                            @click="addingDiagnosis = true"
                                        >
                                            + Add new diagnosis
                                        </button>
                                    </div>
                                </div>

                                <div v-if="addingDiagnosis" class="space-y-4">
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

                                    <DatePickerField
                                        v-model="newDiagnosis.diagnosis_date"
                                        label="Date Diagnosed"
                                        placeholder="Select date diagnosed"
                                        :max="todayStr"
                                        :default-to-today="false"
                                        :error="newErrors.diagnosis_date"
                                    />

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
                                    class="divide-y divide-slate-100 dark:divide-white/10"
                                >
                                    <li v-for="item in diagnoses" :key="item.uuid">
                                        <button
                                            type="button"
                                            class="flex w-full items-start justify-between gap-3 px-4 py-3 text-left transition disabled:cursor-default"
                                            :class="
                                                active.diagnosis_uuid === item.uuid
                                                    ? 'bg-primary/5'
                                                    : item.admission_charge
                                                      ? ''
                                                      : 'hover:bg-slate-50 dark:hover:bg-white/5'
                                            "
                                            :disabled="!!item.admission_charge || takenByOthers.has(item.uuid)"
                                            @click="pickDiagnosis(item.uuid)"
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

                                                <template v-if="item.admission_charge">
                                                    <p
                                                        class="mt-1.5 break-words text-xs text-slate-600 dark:text-gray-300"
                                                    >
                                                        {{ item.admission_charge.case_title }}
                                                        ·
                                                        {{ formatCurrency(item.admission_charge.amount) }}
                                                        / mo
                                                    </p>
                                                    <p
                                                        v-if="item.admission_charge.description"
                                                        class="mt-0.5 break-words text-xs text-slate-500 dark:text-gray-400"
                                                    >
                                                        {{ item.admission_charge.description }}
                                                    </p>
                                                    <p
                                                        class="mt-0.5 text-[11px] text-slate-400 dark:text-gray-500"
                                                    >
                                                        {{ item.admission_charge.invoice_code }}
                                                    </p>
                                                </template>
                                            </div>

                                            <span
                                                v-if="active.diagnosis_uuid === item.uuid"
                                                class="shrink-0 text-[10px] font-semibold uppercase tracking-wide text-primary"
                                            >
                                                Selected
                                            </span>
                                            <span
                                                v-else-if="item.admission_charge"
                                                class="shrink-0 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"
                                            >
                                                Charged
                                            </span>
                                            <span
                                                v-else-if="takenByOthers.has(item.uuid)"
                                                class="shrink-0 text-[10px] font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                                            >
                                                On another case
                                            </span>
                                        </button>

                                        <div
                                            v-if="item.admission_charge"
                                            class="px-4 pb-3"
                                        >
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-1 text-xs font-medium text-primary transition hover:opacity-80"
                                                @click="openSlip([item])"
                                            >
                                                <Printer class="h-3.5 w-3.5" />
                                                Print slip
                                            </button>
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
                                Total ({{ filledEntries.length }}
                                {{ filledEntries.length === 1 ? "case" : "cases" }}
                                ·
                                {{ isYearly ? "12 months" : "1 month" }})
                            </span>
                            <span class="text-lg font-bold tracking-tight">
                                {{ formatCurrency(total * months) }}
                            </span>
                        </div>

                        <div class="flex gap-3">
                            <button
                                type="button"
                                class="flex-1 rounded-xl border py-2.5 text-sm font-medium transition hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5"
                                :disabled="submitting"
                                @click="$emit('close')"
                            >
                                Close
                            </button>

                            <button
                                type="button"
                                class="flex flex-[2] items-center justify-center gap-2 rounded-xl bg-primary py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="submitting || loading || !!loadError || !cases.length"
                                @click="submit"
                            >
                                <LoaderCircle
                                    v-if="submitting"
                                    class="h-4 w-4 animate-spin"
                                />
                                {{
                                    submitting
                                        ? "Adding..."
                                        : entries.length === 1
                                          ? "Add Diagnosis Case"
                                          : `Add ${entries.length} Diagnosis Cases`
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <ChargeSlipModal :slip="slip" @close="slip = null" />
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { useRoute } from "vue-router";
import {
    FileText,
    LoaderCircle,
    Plus,
    Printer,
    UploadCloud,
    X as XIcon,
} from "lucide-vue-next";

import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import DatePickerField from "~/components/ui/DatePickerField.vue";
import ChargeSlipModal from "~/components/sections/app/Admission/ChargeSlipModal.vue";
import { admissionService } from "~/api/admission/AdmissionService";
import { diagnosisCaseService } from "~/api/diagnosis-case/DiagnosisCaseService";
import { patientService } from "~/api/patient/PatientService";
import { useAuthUser } from "~/composables/useAuthUser";
import { useToast } from "~/composables/useToast";
import { useBranchStore } from "~/stores/branch";
import type { ChargeSlip } from "~/types/charge-slip";
import type { DiagnosisCase } from "~/types/additional-charge";
import { formatCurrency } from "~/utils/currency";
import { formatDate, getLocalDateStr } from "~/utils/time";

interface BilledCase {
    case_uuid: string | null;
    case_title: string | null;
    amount: number;
    description: string | null;
    billed_amount: number;
    invoice_code: string | null;
    invoice_status: string | null;
    balance_due: number | null;
}

interface PatientDiagnosis {
    uuid: string;
    diagnosis: string;
    diagnosis_date: string | null;
    admission_charge?: BilledCase | null;
}

interface Entry {
    key: number;
    diagnosis_uuid: string;
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
}>();

const emit = defineEmits<{
    (e: "close"): void;
    // The patient's record after a diagnosis case was added.
    (e: "saved", patient: unknown): void;
}>();

const { success, error: toastError } = useToast();
const authUser = useAuthUser();
const branchStore = useBranchStore();

const NEW_DIAGNOSIS = "__new__";

const isYearly = computed(
    () => String(props.billingCycle ?? "").trim().toLowerCase() === "yearly",
);
const months = computed(() => (isYearly.value ? 12 : 1));

const loading = ref(false);
const loadError = ref("");
const diagnoses = ref<PatientDiagnosis[]>([]);
const cases = ref<DiagnosisCase[]>([]);

let nextKey = 0;

const newEntry = (): Entry => ({
    key: nextKey++,
    diagnosis_uuid: "",
    case_uuid: "",
    amount: "",
    description: "",
});

const entries = ref<Entry[]>([newEntry()]);
const activeIndex = ref(0);
const errors = ref<Record<string, string>>({});
const submitError = ref("");
const submitting = ref(false);
const slip = ref<ChargeSlip | null>(null);

const active = computed(() => entries.value[activeIndex.value] ?? entries.value[0]!);

const todayStr = getLocalDateStr(new Date());
const MAX_FILE_BYTES = 10 * 1024 * 1024;

const addingDiagnosis = ref(false);
const savingDiagnosis = ref(false);
const newDiagnosis = reactive<{
    diagnosis: string;
    diagnosis_date: string;
    diagnosis_notes: string;
    file: File | null;
}>({ diagnosis_date: "", diagnosis: "", diagnosis_notes: "", file: null });
const newErrors = reactive<Record<string, string>>({});

const takenByOthers = computed(
    () =>
        new Set(
            entries.value
                .filter((entry) => entry !== active.value)
                .map((entry) => entry.diagnosis_uuid)
                .filter(Boolean),
        ),
);

const billedRows = computed(() =>
    diagnoses.value.filter((item) => item.admission_charge),
);

const diagnosisItems = computed(() => [
    ...diagnoses.value
        .filter(
            (item) =>
                !item.admission_charge && !takenByOthers.value.has(item.uuid),
        )
        .map((item) => ({
            label: item.diagnosis_date
                ? `${item.diagnosis} — ${formatDate(item.diagnosis_date)}`
                : item.diagnosis,
            value: item.uuid,
        })),
    { label: "+ Add new diagnosis", value: NEW_DIAGNOSIS },
]);

const caseItems = computed(() =>
    cases.value.map((item) => ({
        label: `${item.title} — ${formatCurrency(item.price)} / month`,
        value: item.uuid,
    })),
);

const filledEntries = computed(() =>
    entries.value.filter((entry) => entry.diagnosis_uuid && entry.case_uuid),
);

const total = computed(() =>
    filledEntries.value.reduce(
        (sum, entry) => sum + (Number(entry.amount) || 0),
        0,
    ),
);

function isPaid(billed: BilledCase) {
    return String(billed.invoice_status ?? "").toLowerCase() === "paid";
}

function errorFor(index: number, field: string) {
    return errors.value[`diagnoses.${index}.${field}`];
}

function clearError(index: number, field: string) {
    delete errors.value[`diagnoses.${index}.${field}`];
}

function entryHasError(index: number) {
    return Object.keys(errors.value).some((key) =>
        key.startsWith(`diagnoses.${index}.`),
    );
}

function addEntry() {
    entries.value.push(newEntry());
    activeIndex.value = entries.value.length - 1;
}

function removeEntry(index: number) {
    if (entries.value.length <= 1) return;

    entries.value.splice(index, 1);
    errors.value = {};

    if (activeIndex.value >= entries.value.length) {
        activeIndex.value = entries.value.length - 1;
    } else if (index < activeIndex.value) {
        activeIndex.value -= 1;
    }
}

function pickDiagnosis(uuid: string) {
    if (uuid === NEW_DIAGNOSIS) {
        addingDiagnosis.value = true;
        return;
    }

    active.value.diagnosis_uuid = uuid;
    clearError(activeIndex.value, "patient_diagnosis_uuid");
}

// Picking a case starts the amount from its price, which can then be changed.
function selectCase(uuid: string) {
    const entry = active.value;
    const item = cases.value.find((candidate) => candidate.uuid === uuid);

    entry.case_uuid = uuid;

    if (item) entry.amount = Number(item.price);

    clearError(activeIndex.value, "diagnosis_case_uuid");
    clearError(activeIndex.value, "amount");
}

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
    } else if (newDiagnosis.diagnosis_date > todayStr) {
        newErrors.diagnosis_date = "Diagnosis date cannot be in the future.";
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
        active.value.diagnosis_uuid = created.uuid;
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

function openSlip(rows: PatientDiagnosis[]) {
    const billed = rows.filter((row) => row.admission_charge);

    if (!billed.length) return;

    const user = authUser.value;
    const codes = [
        ...new Set(
            billed
                .map((row) => row.admission_charge!.invoice_code)
                .filter(Boolean) as string[],
        ),
    ];

    slip.value = {
        title: "Diagnosis Case Slip",
        section: "Diagnosis cases",
        branch_name: branchStore.activeBranch?.name ?? null,
        patient_name: props.patientName ?? null,
        prepared_by: user
            ? [user.first_name, user.last_name].filter(Boolean).join(" ")
            : null,
        invoice_code: codes.join(", ") || null,
        // What is still to be paid at the cashier.
        total: billed.reduce(
            (sum, row) =>
                sum +
                (isPaid(row.admission_charge!)
                    ? 0
                    : row.admission_charge!.billed_amount),
            0,
        ),
        charges: billed.map((row) => {
            const charge = row.admission_charge!;

            return {
                id: row.uuid,
                type_label: charge.case_title ?? "Diagnosis case",
                description:
                    [row.diagnosis, charge.description]
                        .filter(Boolean)
                        .join(" · ") + (isPaid(charge) ? " (paid)" : ""),
                amount: charge.billed_amount,
            };
        }),
    };
}

function validate() {
    const next: Record<string, string> = {};

    entries.value.forEach((entry, index) => {
        const amount = Number(entry.amount);

        if (!entry.diagnosis_uuid) {
            next[`diagnoses.${index}.patient_diagnosis_uuid`] =
                "Select or add a diagnosis.";
        }
        if (!entry.case_uuid) {
            next[`diagnoses.${index}.diagnosis_case_uuid`] =
                "Select a diagnosis case.";
        }
        if (!entry.amount || Number.isNaN(amount) || amount <= 0) {
            next[`diagnoses.${index}.amount`] =
                "Enter an amount greater than zero.";
        }
    });

    errors.value = next;

    return Object.keys(next).length === 0;
}

async function submit() {
    submitError.value = "";

    if (submitting.value || !props.admissionId) return;

    if (!validate()) {
        const first = Object.keys(errors.value)
            .map((key) => Number(key.split(".")[1]))
            .filter((index) => Number.isInteger(index))
            .sort((a, b) => a - b)[0];

        if (first !== undefined) activeIndex.value = first;

        return;
    }

    submitting.value = true;

    try {
        const res = await admissionService.action({
            branch_uuid: route.params.uuid,
            p_uuid: props.patientUuid,
            admission_id: props.admissionId,
            action: "diagnosis_cases",
            diagnoses: entries.value.map((entry) => ({
                patient_diagnosis_uuid: entry.diagnosis_uuid,
                diagnosis_case_uuid: entry.case_uuid,
                amount: Number(entry.amount),
                description: entry.description.trim() || undefined,
            })),
        });

        success(res?.message ?? "Diagnosis case added and invoiced.");
        emit("saved", res?.data);

        await load();

        // The slip for what was just billed, ready for the cashier.
        const billedNow = diagnoses.value.filter(
            (row) => row.admission_charge?.invoice_code === res?.invoice_code,
        );

        openSlip(billedNow);
    } catch (err: any) {
        const raw = err?.errors ?? {};

        errors.value = Object.fromEntries(
            Object.entries(raw).map(([key, value]: any) => [
                key,
                Array.isArray(value) ? value[0] : value,
            ]),
        );

        submitError.value = Object.keys(raw).length
            ? ""
            : (err?.data?.message ?? err?.message ?? "Couldn't add the diagnosis cases.");
    } finally {
        submitting.value = false;
    }
}

async function load() {
    loading.value = true;
    loadError.value = "";

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

        entries.value = [newEntry()];
        activeIndex.value = 0;
        errors.value = {};
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
        if (!open) return;

        submitError.value = "";
        cancelNewDiagnosis();
        load();
    },
    { immediate: true },
);
</script>
