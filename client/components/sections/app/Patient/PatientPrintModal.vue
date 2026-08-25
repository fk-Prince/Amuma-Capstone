<template>
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="open"
                class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm no-print"
                @click.self="close"
            >
                <div
                    class="flex max-h-[90dvh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
                >
                    <div
                        class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4"
                    >
                        <div>
                            <h2 class="text-base font-semibold text-slate-800">
                                Print patient records
                            </h2>
                            <p class="mt-0.5 text-sm text-slate-500">
                                Choose what to include. Each section prints on
                                its own page.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100"
                            @click="close"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-5 py-4">
                        <div class="mb-3 flex items-center justify-between">
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                            >
                                Sections
                            </p>

                            <button
                                type="button"
                                class="text-xs font-medium text-primary hover:underline"
                                @click="toggleAll"
                            >
                                {{ allSelected ? "Clear all" : "Select all" }}
                            </button>
                        </div>

                        <div class="space-y-2">
                            <label
                                v-for="section in sections"
                                :key="section.key"
                                class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition"
                                :class="
                                    selected.includes(section.key)
                                        ? 'border-primary bg-primary/5'
                                        : 'border-slate-200 hover:border-primary/40'
                                "
                            >
                                <input
                                    type="checkbox"
                                    class="mt-0.5 h-4 w-4 shrink-0 accent-[#3182ED]"
                                    :value="section.key"
                                    v-model="selected"
                                />

                                <span class="min-w-0">
                                    <span
                                        class="block text-sm font-medium text-slate-800"
                                    >
                                        {{ section.label }}
                                    </span>
                                    <span class="block text-xs text-slate-500">
                                        {{ section.description }}
                                    </span>
                                </span>
                            </label>
                        </div>

                        <p
                            v-if="errorMessage"
                            class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-600"
                        >
                            {{ errorMessage }}
                        </p>
                    </div>

                    <div
                        class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3"
                    >
                        <button
                            type="button"
                            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                            @click="close"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="loading || !selected.length"
                            @click="generate"
                        >
                            <LoaderCircle
                                v-if="loading"
                                class="h-4 w-4 animate-spin"
                            />
                            <Printer v-else class="h-4 w-4" />
                            {{ loading ? "Preparing..." : "Print" }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <Teleport to="body">
        <PatientPrintReport v-if="report" :report="report" />
    </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, nextTick } from "vue";
import { X, Printer, LoaderCircle } from "lucide-vue-next";
import { patientService } from "~/api/patient/PatientService";
import PatientPrintReport from "./PatientPrintReport.vue";

const props = defineProps<{
    open: boolean;
    patientUuid: string;
    branchUuid: string;
}>();

const emit = defineEmits<{ close: [] }>();

const sections = [
    {
        key: "profile",
        label: "Patient Profile",
        description: "Demographics, allergies, and initial assessment.",
    },
    {
        key: "admission",
        label: "Admissions",
        description: "Admission history, room and bed assignments.",
    },
    {
        key: "billing",
        label: "Billing",
        description: "Invoices, payments, and outstanding balance.",
    },
    {
        key: "schedule",
        label: "Schedules",
        description: "Booked services with dates and durations.",
    },
    {
        key: "medication",
        label: "Medications",
        description: "Prescribed medications, dosage, and frequency.",
    },
    {
        key: "vitals",
        label: "Vital Signs",
        description: "Recorded vital sign readings.",
    },
    {
        key: "activity",
        label: "Activities",
        description: "Logged patient activities and notes.",
    },
];

const selected = ref<string[]>(["profile", "medication", "vitals"]);
const loading = ref(false);
const errorMessage = ref("");
const report = ref<any>(null);

const allSelected = computed(() => selected.value.length === sections.length);

function toggleAll() {
    selected.value = allSelected.value ? [] : sections.map((s) => s.key);
}

function close() {
    errorMessage.value = "";
    emit("close");
}

async function generate() {
    if (!selected.value.length) return;

    loading.value = true;
    errorMessage.value = "";

    try {
        const res = await patientService.report(props.patientUuid, {
            branch_uuid: props.branchUuid,
            sections: selected.value.join(","),
        });

        report.value = res.data ?? res;

        emit("close");

        await nextTick();

        document.body.classList.add("printing-report");

        await new Promise((resolve) =>
            requestAnimationFrame(() => requestAnimationFrame(resolve)),
        );

        try {
            window.print();
        } finally {
            document.body.classList.remove("printing-report");
        }
    } catch (err: any) {
        errorMessage.value =
            err?.response?.data?.message ??
            err?.message ??
            "Unable to prepare the report. Please try again.";
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
