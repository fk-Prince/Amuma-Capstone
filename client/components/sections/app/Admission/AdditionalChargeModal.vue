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
                    class="flex w-full max-h-[88dvh] overflow-hidden rounded-2xl bg-white shadow-xl transition-[max-width] duration-300 dark:bg-secondary"
                    :class="showDiagnosisPanel ? 'md:max-w-5xl' : 'max-w-2xl'"
                >
                    <div class="flex min-w-0 flex-1 flex-col">
                    <div
                        class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold tracking-tight">
                                Add Charges
                            </h3>

                            <p
                                class="mt-1 text-sm text-slate-500 dark:text-gray-400"
                            >
                                Every charge added here is billed to
                                {{ patientName || "the patient" }} on one new
                                invoice.
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

                    <form
                        ref="formEl"
                        class="flex-1 overflow-y-auto px-6 py-5"
                        @submit.prevent="submit"
                    >
                        <div
                            v-if="lines.length > 1"
                            class="mb-6 flex flex-wrap items-center gap-2"
                        >
                            <button
                                v-for="(line, index) in lines"
                                :key="line.key"
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
                                        lineHasError(index)
                                            ? 'bg-red-500 text-white'
                                            : index === activeIndex
                                              ? 'bg-white/20 text-white'
                                              : 'bg-white text-slate-400 dark:bg-white/10 dark:text-gray-500'
                                    "
                                >
                                    {{ index + 1 }}
                                </span>

                                Charge {{ index + 1 }}

                                <XIcon
                                    class="h-3.5 w-3.5 opacity-0 transition-opacity group-hover:opacity-100"
                                    :class="
                                        index === activeIndex
                                            ? 'text-white/80 hover:text-white'
                                            : 'text-slate-400 hover:text-red-500 dark:text-gray-500'
                                    "
                                    @click.stop="removeLine(index)"
                                />
                            </button>

                            <button
                                type="button"
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-50 text-slate-400 transition hover:bg-primary/10 hover:text-primary disabled:opacity-50 dark:bg-secondary dark:text-gray-500"
                                :disabled="submitting || lines.length >= MAX_LINES"
                                @click="addLine"
                            >
                                <Plus class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="space-y-8">
                            <div class="space-y-5">
                                <div
                                    v-if="lines.length > 1"
                                    class="flex items-center justify-between"
                                >
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="flex h-7 w-7 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary"
                                        >
                                            {{ activeIndex + 1 }}
                                        </span>

                                        <h3
                                            class="text-sm font-semibold text-slate-700 dark:text-gray-300"
                                        >
                                            Charge {{ activeIndex + 1 }}
                                        </h3>
                                    </div>

                                    <button
                                        type="button"
                                        class="text-sm font-medium text-slate-400 transition hover:text-red-500 dark:text-gray-500"
                                        :disabled="submitting"
                                        @click="removeLine(activeIndex)"
                                    >
                                        Remove
                                    </button>
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <label
                                        class="text-sm font-semibold text-slate-700 dark:text-gray-300"
                                    >
                                        Type <span class="text-red-500">*</span>
                                    </label>

                                    <div
                                        class="grid grid-cols-2 gap-1 rounded-xl border sm:grid-cols-4 border-slate-200 p-1 dark:border-white/10"
                                    >
                                        <button
                                            v-for="option in ADDITIONAL_CHARGE_TYPES"
                                            :key="option.value"
                                            type="button"
                                            class="rounded-lg px-2 py-2 text-xs font-medium transition sm:text-sm"
                                            :class="
                                                activeLine.type === option.value
                                                    ? 'bg-primary text-white shadow-sm'
                                                    : 'text-slate-500 hover:bg-slate-50 dark:text-gray-400 dark:hover:bg-white/5'
                                            "
                                            @click="selectType(option.value)"
                                        >
                                            {{ option.label }}
                                        </button>
                                    </div>

                                    <p
                                        v-if="errorFor(activeIndex, 'type')"
                                        class="text-xs text-red-500"
                                    >
                                        {{ errorFor(activeIndex, "type") }}
                                    </p>
                                </div>

                                <template
                                    v-if="activeLine.type === 'diagnosis_case'"
                                >
                                    <div class="relative">
                                        <button
                                            type="button"
                                            class="absolute right-0 top-0 z-10 inline-flex items-center gap-1 text-xs font-semibold text-primary transition hover:opacity-80"
                                            @click="diagnosisPanelOpen = !diagnosisPanelOpen"
                                        >
                                            {{
                                                diagnosisPanelOpen
                                                    ? "Hide list"
                                                    : "View all diagnoses"
                                            }}
                                            <ChevronRight
                                                class="h-3.5 w-3.5 transition-transform"
                                                :class="diagnosisPanelOpen ? 'rotate-180' : ''"
                                            />
                                        </button>

                                        <Combobox
                                            :key="`diagnosis-${activeLine.key}`"
                                            :model-value="
                                                activeLine.new_diagnosis
                                                    ? PENDING_DIAGNOSIS
                                                    : activeLine.patient_diagnosis_uuid
                                            "
                                            label="Patient diagnosis"
                                            placeholder="Select or add a diagnosis"
                                            required
                                            search-bar
                                            search-placeholder="Search diagnoses"
                                            :items="diagnosisItems"
                                            :error="
                                                errorFor(activeIndex, 'patient_diagnosis_uuid') ||
                                                errorFor(activeIndex, 'new_diagnosis.diagnosis') ||
                                                errorFor(activeIndex, 'new_diagnosis.diagnosis_date') ||
                                                errorFor(activeIndex, 'new_diagnosis.diagnosis_file')
                                            "
                                            @update:model-value="selectDiagnosis"
                                        />
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

                                        <p
                                            class="-mt-2 text-xs text-slate-500 dark:text-gray-400"
                                        >
                                            This diagnosis is saved to the
                                            patient's record together with the
                                            charge.
                                        </p>

                                        <BaseInput
                                            v-model="newDiagnosis.diagnosis"
                                            label="Primary Diagnosis"
                                            placeholder="e.g. Type 2 Diabetes"
                                            :error="diagnosisErrors.diagnosis"
                                            required
                                            @update:modelValue="delete diagnosisErrors.diagnosis"
                                        />

                                        <DatePickerField
                                            v-model="newDiagnosis.diagnosis_date"
                                            label="Date Diagnosed"
                                            placeholder="Select date diagnosed"
                                            :max="todayStr"
                                            :default-to-today="false"
                                            :error="diagnosisErrors.diagnosis_date"
                                            required
                                        />

                                        <BaseInput
                                            v-model="newDiagnosis.diagnosis_notes"
                                            label="Diagnosis Notes"
                                            placeholder="Additional details about the diagnosis"
                                            :error="diagnosisErrors.diagnosis_notes"
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
                                                    diagnosisErrors.diagnosis_file
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
                                                v-if="diagnosisErrors.diagnosis_file"
                                                class="text-xs text-red-500"
                                            >
                                                {{ diagnosisErrors.diagnosis_file }}
                                            </p>
                                        </div>

                                        <div class="flex justify-end gap-2">
                                            <button
                                                type="button"
                                                class="rounded-lg border px-3 py-1.5 text-xs font-medium transition hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5"
                                                @click="addingDiagnosis = false"
                                            >
                                                Cancel
                                            </button>

                                            <button
                                                type="button"
                                                class="rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-white transition hover:opacity-90"
                                                @click="stageDiagnosis"
                                            >
                                                Use this diagnosis
                                            </button>
                                        </div>
                                    </div>

                                    <Combobox
                                        v-if="cases.length"
                                        :key="`case-${activeLine.key}`"
                                        :model-value="activeLine.diagnosis_case_uuid"
                                        label="Diagnosis case"
                                        placeholder="Select a diagnosis case"
                                        required
                                        search-bar
                                        search-placeholder="Search diagnosis cases"
                                        :items="caseItems"
                                        :error="errorFor(activeIndex, 'diagnosis_case_uuid')"
                                        @update:model-value="selectCase"
                                    />

                                    <p
                                        v-else
                                        class="rounded-xl border border-dashed border-slate-200 px-4 py-3 text-sm text-slate-400 dark:border-white/10 dark:text-gray-500"
                                    >
                                        No diagnosis cases yet. Add them from
                                        Contracts.
                                    </p>
                                </template>

                                <BaseInput
                                    :key="`description-${activeLine.key}`"
                                    v-model="activeLine.description"
                                    label="Description"
                                    mode="textarea"
                                    :textMax="255"
                                    placeholder="e.g. Paracetamol 500mg × 10 tablets"
                                    :error="errorFor(activeIndex, 'description')"
                                    required
                                    @update:modelValue="
                                        clearError(activeIndex, 'description')
                                    "
                                />

                                <BaseInput
                                    :key="`amount-${activeLine.key}`"
                                    v-model="activeLine.amount"
                                    label="Amount"
                                    mode="number"
                                    placeholder="0.00"
                                    :error="errorFor(activeIndex, 'amount')"
                                    required
                                    @update:modelValue="
                                        clearError(activeIndex, 'amount')
                                    "
                                />
                            </div>

                            <button
                                type="button"
                                class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-200 py-4 text-sm font-semibold text-primary transition hover:border-primary/40 hover:bg-primary/5 disabled:opacity-50 dark:border-white/10"
                                :disabled="submitting || lines.length >= MAX_LINES"
                                @click="addLine"
                            >
                                <span class="text-xl leading-none">+</span>
                                Add Another Charge
                            </button>
                        </div>

                        <p
                            v-if="submitError"
                            class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-600 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"
                        >
                            {{ submitError }}
                        </p>
                    </form>

                    <div
                        class="border-t border-slate-100 px-6 py-4 dark:border-white/10"
                    >
                        <div
                            class="mb-3 flex items-center justify-between text-sm"
                        >
                            <span class="text-slate-500 dark:text-gray-400">
                                Total ({{ lines.length }}
                                {{ lines.length === 1 ? "charge" : "charges" }})
                            </span>
                            <span class="text-lg font-bold tracking-tight">
                                {{ formatCurrency(total) }}
                            </span>
                        </div>

                        <div class="flex gap-3">
                            <button
                                type="button"
                                class="flex-1 rounded-xl border py-2.5 text-sm font-medium transition hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5"
                                :disabled="submitting"
                                @click="$emit('close')"
                            >
                                Cancel
                            </button>

                            <button
                                type="button"
                                class="flex flex-[2] items-center justify-center gap-2 rounded-xl bg-primary py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="submitting"
                                @click="submit"
                            >
                                <LoaderCircle
                                    v-if="submitting"
                                    class="h-4 w-4 animate-spin"
                                />
                                {{
                                    submitting
                                        ? "Adding..."
                                        : lines.length === 1
                                          ? "Add Charge"
                                          : `Add ${lines.length} Charges`
                                }}
                            </button>
                        </div>
                    </div>
                    </div>

                    <DiagnosisPickerPanel
                        v-if="showDiagnosisPanel"
                        class="hidden w-80 shrink-0 flex-col border-l border-slate-100 dark:border-white/10 md:flex"
                        :diagnoses="diagnoses"
                        :selected-uuid="activeLine.patient_diagnosis_uuid"
                        :taken-ids="takenDiagnosisIds"
                        @select="pickDiagnosis"
                        @close="diagnosisPanelOpen = false"
                    />
                </div>
            </div>
        </Transition>

        <div
            v-if="open && showDiagnosisPanel"
            class="fixed inset-0 z-[80] flex items-end justify-center bg-black/40 p-5 md:hidden"
            @click.self="diagnosisPanelOpen = false"
        >
            <DiagnosisPickerPanel
                class="flex max-h-[80dvh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary"
                :diagnoses="diagnoses"
                :selected-uuid="activeLine.patient_diagnosis_uuid"
                :taken-ids="takenDiagnosisIds"
                @select="pickDiagnosis"
                @close="diagnosisPanelOpen = false"
            />
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, nextTick, reactive, ref, watch } from "vue";
import {
    ChevronRight,
    FileText,
    LoaderCircle,
    Plus,
    UploadCloud,
    X as XIcon,
} from "lucide-vue-next";
import DiagnosisPickerPanel from "~/components/sections/app/Admission/DiagnosisPickerPanel.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import DatePickerField from "~/components/ui/DatePickerField.vue";
import { formatDate } from "~/utils/time";
import { additionalChargeService } from "~/api/additional-charge/AdditionalChargeService";
import { diagnosisCaseService } from "~/api/diagnosis-case/DiagnosisCaseService";
import { formatCurrency } from "~/utils/currency";
import {
    ADDITIONAL_CHARGE_TYPES,
    type AdditionalCharge,
    type AdditionalChargeType,
    type DiagnosisCase,
} from "~/types/additional-charge";

const MAX_LINES = 50;

interface ChargeLine {
    key: number;
    type: AdditionalChargeType | "";
    description: string;
    amount: string | number;
    diagnosis_case_uuid: string;
    patient_diagnosis_uuid: string;
    new_diagnosis: StagedDiagnosis | null;
}

interface StagedDiagnosis {
    diagnosis: string;
    diagnosis_date: string;
    diagnosis_notes: string;
    file: File | null;
}

interface PatientDiagnosisOption {
    uuid: string;
    diagnosis: string;
    diagnosis_date: string | null;
    charged: boolean;
    paid: boolean;
    invoice_code: string | null;
}

const NEW_DIAGNOSIS = "__new__";
const PENDING_DIAGNOSIS = "__pending__";

const props = defineProps<{
    open: boolean;
    branchUuid: string;
    patientUuid: string;
    patientName?: string | null;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "created", charges: AdditionalCharge[]): void;
}>();

let nextKey = 0;

const newLine = (): ChargeLine => ({
    key: nextKey++,
    type: "",
    description: "",
    amount: "",
    diagnosis_case_uuid: "",
    patient_diagnosis_uuid: "",
    new_diagnosis: null,
});

const lines = ref<ChargeLine[]>([newLine()]);
const activeIndex = ref(0);
const errors = ref<Record<string, string>>({});
const submitError = ref("");
const submitting = ref(false);
const formEl = ref<HTMLFormElement | null>(null);

const activeLine = computed(
    () => lines.value[activeIndex.value] ?? lines.value[0]!,
);

const cases = ref<DiagnosisCase[]>([]);


const diagnoses = ref<PatientDiagnosisOption[]>([]);
const addingDiagnosis = ref(false);
const newDiagnosis = reactive<{
    diagnosis: string;
    diagnosis_date: string;
    diagnosis_notes: string;
    file: File | null;
}>({ diagnosis: "", diagnosis_date: "", diagnosis_notes: "", file: null });

const MAX_DIAGNOSIS_FILE = 10 * 1024 * 1024;

function onDiagnosisFile(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    delete diagnosisErrors.diagnosis_file;
    input.value = "";

    if (!file) return;

    if (file.size > MAX_DIAGNOSIS_FILE) {
        diagnosisErrors.diagnosis_file = "The file must be 10MB or smaller.";
        return;
    }

    newDiagnosis.file = file;
}
const diagnosisErrors = reactive<Record<string, string>>({});
const todayStr = new Date().toISOString().slice(0, 10);

const diagnosisPanelOpen = ref(false);

const showDiagnosisPanel = computed(
    () => diagnosisPanelOpen.value && activeLine.value.type === "diagnosis_case",
);

const takenDiagnosisIds = computed(
    () =>
        new Set(
            lines.value
                .filter((line) => line !== activeLine.value)
                .map((line) => line.patient_diagnosis_uuid),
        ),
);

function pickDiagnosis(uuid: string) {
    selectDiagnosis(uuid);
    diagnosisPanelOpen.value = false;
}

const diagnosisItems = computed(() => {
    const staged = activeLine.value.new_diagnosis;

    return [
        ...(staged
            ? [
                  {
                      label: `${staged.diagnosis} — ${formatDate(staged.diagnosis_date)} (new)`,
                      value: PENDING_DIAGNOSIS,
                  },
              ]
            : []),
        ...diagnoses.value
            .filter((item) => !takenDiagnosisIds.value.has(item.uuid))
            .map((item) => ({
                label: `${
                    item.diagnosis_date
                        ? `${item.diagnosis} — ${formatDate(item.diagnosis_date)}`
                        : item.diagnosis
                }${item.paid ? " (paid)" : item.charged ? " (charged)" : ""}`,
                value: item.uuid,
            })),
        { label: "+ Add new diagnosis", value: NEW_DIAGNOSIS },
    ];
});

const caseItems = computed(() => [
    ...cases.value.map((item) => ({
        label: `${item.title} — ${formatCurrency(item.price)}`,
        value: item.uuid,
    })),
]);

async function fetchCases() {
    try {
        const res = await diagnosisCaseService.list({
            branch_uuid: props.branchUuid,
            for: "charges",
        });

        cases.value = res.data ?? [];
    } catch {
        cases.value = [];
    }
}

async function fetchDiagnoses() {
    try {
        const res = await additionalChargeService.diagnoses({
            branch_uuid: props.branchUuid,
            patient_uuid: props.patientUuid,
        });

        diagnoses.value = res.data ?? [];
    } catch {
        diagnoses.value = [];
    }
}

function selectType(type: AdditionalChargeType) {
    const line = activeLine.value;

    if (line.type === "diagnosis_case" && type !== "diagnosis_case") {
        line.diagnosis_case_uuid = "";
        line.patient_diagnosis_uuid = "";
        line.new_diagnosis = null;
        addingDiagnosis.value = false;
    }

    line.type = type;
    clearError(activeIndex.value, "type");
}

function selectDiagnosis(uuid: string) {
    const line = activeLine.value;

    if (uuid === PENDING_DIAGNOSIS) {
        addingDiagnosis.value = false;
        return;
    }

    if (uuid === NEW_DIAGNOSIS) {
        Object.assign(
            newDiagnosis,
            line.new_diagnosis ?? {
                diagnosis: "",
                diagnosis_date: "",
                diagnosis_notes: "",
                file: null,
            },
        );
        Object.keys(diagnosisErrors).forEach((key) => delete diagnosisErrors[key]);
        addingDiagnosis.value = true;
        return;
    }

    addingDiagnosis.value = false;
    line.new_diagnosis = null;
    line.patient_diagnosis_uuid = uuid;
    clearError(activeIndex.value, "patient_diagnosis_uuid");
}

function stageDiagnosis() {
    Object.keys(diagnosisErrors).forEach((key) => delete diagnosisErrors[key]);

    if (!newDiagnosis.diagnosis.trim()) {
        diagnosisErrors.diagnosis = "Enter the diagnosis.";
    }
    if (!newDiagnosis.diagnosis_date) {
        diagnosisErrors.diagnosis_date = "Select the date diagnosed.";
    }
    if (Object.keys(diagnosisErrors).length) return;

    const line = activeLine.value;

    line.new_diagnosis = {
        diagnosis: newDiagnosis.diagnosis.trim(),
        diagnosis_date: newDiagnosis.diagnosis_date,
        diagnosis_notes: newDiagnosis.diagnosis_notes.trim(),
        file: newDiagnosis.file,
    };
    line.patient_diagnosis_uuid = "";
    clearError(activeIndex.value, "patient_diagnosis_uuid");
    addingDiagnosis.value = false;
}

function selectCase(uuid: string) {
    const line = activeLine.value;
    const item = cases.value.find((entry) => entry.uuid === uuid);

    line.diagnosis_case_uuid = uuid;
    if (item) {
        if (!line.description.trim()) line.description = item.title;
        line.amount = item.price;
    }

    clearError(activeIndex.value, "diagnosis_case_uuid");
    clearError(activeIndex.value, "amount");
}

function lineHasError(index: number) {
    return Object.keys(errors.value).some((key) =>
        key.startsWith(`charges.${index}.`),
    );
}

function scrollToTop() {
    nextTick(() => formEl.value?.scrollTo({ top: 0, behavior: "smooth" }));
}

const total = computed(() =>
    lines.value.reduce((sum, line) => {
        const amount = Number(line.amount);
        return sum + (Number.isFinite(amount) && amount > 0 ? amount : 0);
    }, 0),
);

function errorFor(index: number, field: string) {
    return errors.value[`charges.${index}.${field}`];
}

function clearError(index: number, field: string) {
    delete errors.value[`charges.${index}.${field}`];
}

function addLine() {
    if (lines.value.length >= MAX_LINES) return;
    lines.value.push(newLine());
    activeIndex.value = lines.value.length - 1;
    scrollToTop();
}

function removeLine(index: number) {
    if (lines.value.length <= 1) return;

    lines.value.splice(index, 1);
    errors.value = {};

    if (activeIndex.value >= lines.value.length) {
        activeIndex.value = lines.value.length - 1;
    } else if (index < activeIndex.value) {
        activeIndex.value -= 1;
    }
}

function focusFirstError() {
    const first = Object.keys(errors.value)
        .map((key) => Number(key.split(".")[1]))
        .filter((index) => Number.isInteger(index))
        .sort((a, b) => a - b)[0];

    if (first !== undefined) activeIndex.value = first;
}

function validate() {
    const next: Record<string, string> = {};

    lines.value.forEach((line, index) => {
        const amount = Number(line.amount);

        if (!line.type) next[`charges.${index}.type`] = "Choose a charge type.";
        if (line.type === "diagnosis_case") {
            if (!line.patient_diagnosis_uuid && !line.new_diagnosis) {
                next[`charges.${index}.patient_diagnosis_uuid`] = "Select or add a diagnosis.";
            }
            if (!line.diagnosis_case_uuid) {
                next[`charges.${index}.diagnosis_case_uuid`] = "Select a diagnosis case.";
            }
        }
        if (!line.description.trim()) {
            next[`charges.${index}.description`] = "Enter a description.";
        }
        if (!line.amount || Number.isNaN(amount) || amount <= 0) {
            next[`charges.${index}.amount`] = "Enter an amount greater than zero.";
        }
    });

    errors.value = next;

    return Object.keys(next).length === 0;
}

async function submit() {
    submitError.value = "";

    if (submitting.value) return;

    if (!validate()) {
        focusFirstError();
        return;
    }

    submitting.value = true;

    try {
        const res = await additionalChargeService.create({
            branch_uuid: props.branchUuid,
            patient_uuid: props.patientUuid,
            charges: lines.value.map((line) => ({
                type: line.type,
                description: line.description.trim(),
                amount: Number(line.amount),
                ...(line.type === "diagnosis_case" && {
                    diagnosis_case_uuid: line.diagnosis_case_uuid,
                    patient_diagnosis_uuid: line.new_diagnosis
                        ? undefined
                        : line.patient_diagnosis_uuid,
                    new_diagnosis: line.new_diagnosis
                        ? {
                              diagnosis: line.new_diagnosis.diagnosis,
                              diagnosis_date: line.new_diagnosis.diagnosis_date,
                              diagnosis_notes:
                                  line.new_diagnosis.diagnosis_notes || undefined,
                              diagnosis_file: line.new_diagnosis.file ?? undefined,
                          }
                        : undefined,
                }),
            })),
        });

        emit("created", res.data ?? []);
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
            : (err?.message ?? "Couldn't add the charges.");

        focusFirstError();
    } finally {
        submitting.value = false;
    }
}

watch(
    () => props.open,
    (open) => {
        if (!open) return;

        lines.value = [newLine()];
        activeIndex.value = 0;
        errors.value = {};
        submitError.value = "";
        addingDiagnosis.value = false;
        diagnosisPanelOpen.value = false;
        fetchCases();
        fetchDiagnoses();
    },
);
</script>
