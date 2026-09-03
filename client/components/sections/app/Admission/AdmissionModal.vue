<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 bg-primary-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
                @click.self="$emit('close')"
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
                        class="bg-white rounded-2xl shadow-[0_0_40px_rgba(10,40,87,0.15)] ring-1 ring-primary-100/60 w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden dark:bg-secondary dark:ring-primary-500/20"
                        role="dialog"
                        aria-modal="true"
                        aria-label="Patient details"
                    >
                        <!-- Header-->
                        <div
                            class="flex items-start justify-between px-5 py-4 border-b border-primary-100/80 bg-primary-50/40 shrink-0 dark:border-primary-500/20 dark:bg-primary-500/10"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        class="w-5 h-5"
                                    >
                                        <path
                                            d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"
                                        />
                                        <circle cx="9" cy="7" r="4" />
                                        <path d="M22 21v-2a4 4 0 00-3-3.87" />
                                        <path d="M16 3.13a4 4 0 010 7.75" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h2
                                        class="text-base font-semibold leading-tight text-primary-900 truncate dark:text-primary-300"
                                    >
                                        {{ patient?.full_name || "Patient" }}
                                    </h2>
                                    <p class="text-xs text-muted mt-0.5 dark:text-gray-400">
                                        Patient details &amp; current admission
                                    </p>
                                </div>
                            </div>

                            <button
                                aria-label="Close dialog"
                                class="shrink-0 w-8 h-8 flex items-center justify-center rounded-full text-primary-400 transition-colors duration-200 hover:bg-primary-100 hover:text-primary-700 dark:hover:bg-primary-500/15 dark:hover:text-primary-300"
                                @click="$emit('close')"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="w-4 h-4"
                                >
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                            </button>
                        </div>

                        <div
                            v-if="loading"
                            class="flex-1 flex flex-col items-center justify-center gap-2 py-16"
                        >
                            <svg
                                class="w-6 h-6 animate-spin text-primary"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                />
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                />
                            </svg>
                            <p class="text-xs text-muted dark:text-gray-400">
                                Loading patient details…
                            </p>
                        </div>

                        <div
                            v-else-if="patient"
                            class="flex-1 overflow-y-auto p-5 space-y-5 patient-scroll"
                        >
                            <div
                                v-if="admission"
                                class="rounded-xl border border-primary-100 bg-primary-50/40 p-4 flex items-center justify-between gap-3 dark:border-primary-500/20 dark:bg-primary-500/10"
                            >
                                <div>
                                    <p
                                        class="text-[11px] uppercase tracking-wide text-muted font-semibold dark:text-gray-400"
                                    >
                                        Admission status
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-semibold text-primary-900 dark:text-primary-300"
                                    >
                                        {{
                                            admission.room?.room_no
                                                ? `Room ${admission.room.room_no}`
                                                : "No room assigned"
                                        }}
                                        <span
                                            v-if="admission.bed?.bed_no"
                                            class="text-muted font-normal dark:text-gray-400"
                                        >
                                            · Bed
                                            {{ admission.bed.bed_no }}
                                        </span>
                                    </p>
                                    <p class="text-xs text-muted mt-0.5 dark:text-gray-400">
                                        Admitted
                                        {{ formatDate(admission.admitted_at) }}
                                        <span v-if="admission.end_date">
                                            — Ended
                                            {{ formatDate(admission.end_date) }}
                                        </span>
                                    </p>
                                </div>

                                <span
                                    class="shrink-0 text-xs font-medium capitalize rounded-full px-2.5 py-1"
                                    :class="statusBadgeClass(admission.status)"
                                >
                                    {{ admission.status }}
                                </span>
                            </div>

                            <!-- Personal information -->
                            <section>
                                <h3
                                    class="text-[11px] uppercase tracking-wide text-muted font-semibold mb-2 dark:text-gray-400"
                                >
                                    Personal information
                                </h3>
                                <div
                                    class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-3 rounded-xl border border-primary-100 p-4 dark:border-primary-500/20"
                                >
                                    <InfoField
                                        label="Gender"
                                        :value="patient.gender"
                                    />
                                    <InfoField
                                        label="Date of birth"
                                        :value="
                                            formatDate(patient.date_of_birth)
                                        "
                                    />
                                    <InfoField
                                        label="Age"
                                        :value="patient.age"
                                    />
                                    <InfoField
                                        label="Blood type"
                                        :value="patient.blood_type"
                                    />
                                    <InfoField
                                        label="Height"
                                        :value="patient.height"
                                    />
                                    <InfoField
                                        label="Weight"
                                        :value="patient.weight"
                                    />
                                    <InfoField
                                        label="Citizenship"
                                        :value="patient.citizenship"
                                    />
                                    <InfoField
                                        label="Phone number"
                                        :value="patient.phone_number"
                                    />
                                    <InfoField
                                        label="Address"
                                        :value="patient.location?.full_address"
                                        class="col-span-2 sm:col-span-3"
                                    />
                                </div>
                            </section>

                            <section v-if="medicationEntries.length">
                                <h3
                                    class="text-[11px] uppercase tracking-wide text-muted font-semibold mb-2 dark:text-gray-400"
                                >
                                    Assessment
                                </h3>
                                <div
                                    class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-3 rounded-xl border border-primary-100 p-4 dark:border-primary-500/20"
                                >
                                    <InfoField
                                        v-for="[
                                            key,
                                            value,
                                        ] in medicationEntries"
                                        :key="key"
                                        :label="formatKey(key)"
                                        :value="value"
                                    />
                                </div>
                            </section>
                        </div>

                        <!-- Empty -->
                        <div
                            v-else
                            class="flex-1 flex flex-col items-center justify-center gap-2 py-16 px-6 text-center"
                        >
                            <p class="text-sm font-medium text-primary-900 dark:text-primary-300">
                                No patient data
                            </p>
                            <p class="text-xs text-muted max-w-[220px] dark:text-gray-400">
                                We couldn't load details for this patient.
                            </p>
                        </div>

                        <div
                            class="flex items-center justify-end gap-2 px-5 py-3.5 border-t border-primary-100/80 shrink-0 dark:border-primary-500/20"
                        >
                            <button
                                type="button"
                                class="rounded-lg px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 transition-colors duration-200 dark:text-gray-400 dark:hover:text-gray-400"
                                @click="$emit('close')"
                            >
                                Close
                            </button>
                            <NuxtLink
                                v-if="patient?.uuid"
                                :to="`/app/branches/${branchUuid}/patients/${patient.uuid}`"
                                class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:opacity-90 transition"
                            >
                                Open full profile
                            </NuxtLink>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, defineComponent, h } from "vue";
import { formatCurrency as formatCurrencyUtil } from "~/utils/currency";
import { formatDate } from "~/utils/time";
import type { PatientRetrieve, Admission } from "~/types/patient";

const props = defineProps<{
    open: boolean;
    patient: PatientRetrieve | null;
    loading?: boolean;
    branchUuid?: string;
}>();

defineEmits<{
    close: [];
}>();

const admission = computed<Admission | undefined>(
    () => props.patient?.latest_admission,
);

const assessment = computed(() => {
    const value = props.patient?.assessment;
    if (!value) return null;
    return Array.isArray(value) ? (value[0] ?? null) : value;
});

const medicationEntries = computed(() =>
    toEntries(
        assessment.value,
        (key) => key !== "diagnosis_file",
    ),
);

function toEntries(
    value: unknown,
    include: (key: string) => boolean,
): [string, string][] {
    if (!value || typeof value !== "object") return [];
    return Object.entries(value as Record<string, unknown>)
        .filter(
            ([k, v]) => include(k) && v !== null && v !== undefined && v !== "",
        )
        .map(([k, v]) => [k, String(v)]);
}

function formatKey(key: string) {
    return key.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
}

function formatCurrency(value?: string | number) {
    if (value === undefined || value === null || value === "") return "—";
    const num = Number(value);
    if (Number.isNaN(num)) return String(value);
    return formatCurrencyUtil(num);
}

function statusBadgeClass(status?: string) {
    switch (status?.toLowerCase()) {
        case "admitted":
            return "bg-green-50 text-accent";
        case "waiting":
            return "bg-orange-50 text-secondary dark:text-white";
        case "discharged":
            return "bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-gray-400";
        default:
            return "bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300";
    }
}

const InfoField = defineComponent({
    props: {
        label: { type: String, required: true },
        value: { type: [String, Number], default: null },
    },
    setup(p) {
        return () =>
            h("div", { class: "min-w-0" }, [
                h("p", { class: "text-[11px] text-muted" }, p.label),
                h(
                    "p",
                    {
                        class: "text-[13px] font-medium text-primary-900 truncate dark:text-primary-300",
                    },
                    p.value === null || p.value === undefined || p.value === ""
                        ? "—"
                        : String(p.value),
                ),
            ]);
    },
});
</script>

<style scoped>
.patient-scroll {
    scrollbar-width: thin;
    scrollbar-color: theme("colors.primary.300") transparent;
}

.patient-scroll::-webkit-scrollbar {
    width: 5px;
}

.patient-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.patient-scroll::-webkit-scrollbar-thumb {
    background-color: theme("colors.primary.300");
    border-radius: 999px;
}

.patient-scroll::-webkit-scrollbar-thumb:hover {
    background-color: theme("colors.primary.500");
}
</style>
