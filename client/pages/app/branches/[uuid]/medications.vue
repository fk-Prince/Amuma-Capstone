<script setup lang="ts">
import {
    ref,
    computed,
    watch,
    nextTick,
    onMounted,
    onBeforeUnmount,
} from "vue";
import { ChevronLeft, ChevronRight, ArrowUpRight } from "lucide-vue-next";
import { useDebounceFn } from "@vueuse/core";
import MedicationTable from "~/components/sections/app/Patient/MedicationTable.vue";
import VitalSignsTable from "~/components/sections/app/Patient/VitalSignsTable.vue";
import ActionMedicationModal from "~/components/sections/app/Patient/ActionMedicationModal.vue";
import ActionVitalModal from "~/components/sections/app/Patient/ActionVitalModal.vue";
import PatientFilter from "~/components/sections/app/Patient/PatientFilter.vue";
import PatientAvatar from "~/components/ui/PatientAvatar.vue";
import Combobox from "~/components/ui/Combobox.vue";
import Pagination from "~/components/ui/Pagination.vue";
import { patientService } from "~/api/patient/PatientService";
import { useToast } from "~/composables/useToast";
import type { PatientRetrieve } from "~/types/patient";
import type {
    MarkDosePayload,
    MedicationForm,
    Vital,
    VitalFormData,
} from "~/types/medication";

useHead({ title: "Medications" });

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

const route = useRoute();
const router = useRouter();
const b_uuid = computed(() => route.params.uuid as string);

const { success, error } = useToast();

const {
    medications,
    vitals,
    medicationsMeta,
    vitalsMeta,
    isFetchingMedications,
    isFetchingVitals,
    fetchMedications,
    fetchVitals,
    handleMedicationAction,
    handleVitalAction,
    handleDosageAction,
} = usePatient();

const tabs = ["Medication", "Vital Signs"] as const;
type Tab = (typeof tabs)[number];

const tabSlugMap: Record<Tab, string> = {
    Medication: "medication",
    "Vital Signs": "vitals",
};

const tabItems = tabs.map((tab) => ({ label: tab, value: tab }));

const activeTab = ref<Tab>(
    route.query.tab === tabSlugMap["Vital Signs"] ? "Vital Signs" : "Medication",
);

function setActiveTab(tab: Tab) {
    activeTab.value = tab;
    router.replace({ query: { ...route.query, tab: tabSlugMap[tab] } });
}

const PATIENTS_PER_PAGE = 20;

const isLoading = ref(true);
const isFetchingPatients = ref(false);
const isLoadingMore = ref(false);

const patients = ref<PatientRetrieve[]>([]);
const patientsPage = ref(1);
const patientsLastPage = ref(1);
const patientsTotal = ref(0);
const selectedIndex = ref(0);

const hasMorePatients = computed(
    () => patientsPage.value < patientsLastPage.value,
);

const searchQuery = ref("");
const typeFilter = ref("all");
const assignmentFilter = ref(route.query.assignment === "mine" ? "mine" : "all");
const dateFrom = ref("");
const dateTo = ref("");

const hasFilters = computed(
    () =>
        !!searchQuery.value.trim() ||
        typeFilter.value !== "all" ||
        assignmentFilter.value !== "all" ||
        !!dateFrom.value ||
        !!dateTo.value,
);

const { resolveIndex, syncQuery } = usePatientQuerySelection();

const patient = computed(() => patients.value[selectedIndex.value] ?? null);

const patientsScrollRef = ref<HTMLElement | null>(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(false);

function updateScrollState() {
    const el = patientsScrollRef.value;

    if (!el) {
        canScrollLeft.value = false;
        canScrollRight.value = false;
        return;
    }

    canScrollLeft.value = el.scrollLeft > 4;
    canScrollRight.value = el.scrollLeft + el.clientWidth < el.scrollWidth - 4;

    if (el.scrollLeft + el.clientWidth >= el.scrollWidth - 200) {
        loadMorePatients();
    }
}

function scrollPatients(direction: 1 | -1) {
    patientsScrollRef.value?.scrollBy({
        left: direction * 220,
        behavior: "smooth",
    });
}

async function loadActiveTab(page = 1) {
    const uuid = patient.value?.uuid;

    if (!uuid) return;

    if (activeTab.value === "Medication") {
        await fetchMedications(uuid, page);
    } else {
        await fetchVitals(uuid, page);
    }
}

let patientsRequestId = 0;

function listPatients(page: number) {
    return patientService.list({
        branch_uuid: b_uuid.value,
        sections: "profile",
        page,
        per_page: PATIENTS_PER_PAGE,
        search: searchQuery.value.trim() || undefined,
        category: typeFilter.value !== "all" ? typeFilter.value : undefined,
        assigned_only: assignmentFilter.value === "mine" ? 1 : undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    });
}

function setPatientsMeta(res: any, page: number) {
    patientsPage.value = res.meta?.current_page ?? page;
    patientsLastPage.value = res.meta?.last_page ?? page;
    patientsTotal.value = res.meta?.total ?? patients.value.length;
}

async function fetchPatients() {
    const requestId = ++patientsRequestId;
    isFetchingPatients.value = true;

    try {
        const res: any = await listPatients(1);

        if (requestId !== patientsRequestId) return;

        patients.value = res.data ?? [];
        setPatientsMeta(res, 1);
    } catch (err) {
        if (requestId !== patientsRequestId) return;

        console.error("Failed fetching patients", err);
        patients.value = [];
        setPatientsMeta({}, 1);
    } finally {
        if (requestId === patientsRequestId) {
            isFetchingPatients.value = false;
        }
    }
}

async function loadMorePatients() {
    if (
        !hasMorePatients.value ||
        isLoadingMore.value ||
        isFetchingPatients.value
    ) {
        return;
    }

    const requestId = patientsRequestId;
    const nextPage = patientsPage.value + 1;
    isLoadingMore.value = true;

    try {
        const res: any = await listPatients(nextPage);

        if (requestId !== patientsRequestId) return;

        const known = new Set(patients.value.map((p) => p.uuid));
        patients.value.push(
            ...(res.data ?? []).filter(
                (p: PatientRetrieve) => !known.has(p.uuid),
            ),
        );
        setPatientsMeta(res, nextPage);
    } catch (err) {
        console.error("Failed loading more patients", err);
    } finally {
        isLoadingMore.value = false;
    }

    await nextTick();
    updateScrollState();
}

async function selectIndex(index: number) {
    if (selectedIndex.value === index) {
        syncQuery(patient.value?.uuid);
        await loadActiveTab();
    } else {
        selectedIndex.value = index;
    }

    await nextTick();
    updateScrollState();
}

async function refreshPatients() {
    const currentUuid = patient.value?.uuid;

    await fetchPatients();

    patientsScrollRef.value?.scrollTo({ left: 0 });

    const kept = patients.value.findIndex((p) => p.uuid === currentUuid);

    await selectIndex(kept >= 0 ? kept : 0);
}

const debouncedRefresh = useDebounceFn(refreshPatients, 400);

watch(searchQuery, debouncedRefresh);
watch([typeFilter, assignmentFilter, dateFrom, dateTo], refreshPatients);

watch(selectedIndex, () => {
    syncQuery(patient.value?.uuid);
    loadActiveTab();
});

watch(activeTab, () => loadActiveTab());

const showAddMedication = ref(false);
const savingMedication = ref(false);
const savingDosage = ref(false);

const showRecordVital = ref(false);
const savingVital = ref(false);
const selectedVital = ref<Vital | null>(null);

async function onMedicationSubmit(
    action: "create" | "update",
    payload: MedicationForm,
    id?: string,
) {
    if (!patient.value) return;

    savingMedication.value = true;
    try {
        const res = await handleMedicationAction(
            action,
            payload,
            patient.value.uuid,
            id,
        );
        success(res?.message);
        showAddMedication.value = false;
    } catch (err: any) {
        error(err.error);
    } finally {
        savingMedication.value = false;
    }
}

async function onDosageSubmit(payload: MarkDosePayload) {
    if (!patient.value) return;

    savingDosage.value = true;
    try {
        const res = await handleDosageAction(payload, patient.value.uuid);
        success(res.message);
    } catch (err: any) {
        error(err.error);
    } finally {
        savingDosage.value = false;
    }
}

function editVital(vital: Vital) {
    selectedVital.value = vital;
    showRecordVital.value = true;
}

async function onVitalSubmit(
    action: "create" | "update",
    payload: VitalFormData,
    id?: string,
) {
    if (!patient.value) return;

    savingVital.value = true;
    try {
        const res: any = await handleVitalAction(
            action,
            payload,
            patient.value.uuid,
            id,
        );

        if (!res) {
            error("Vital signs could not be saved.");
            return;
        }

        success(res.message);
        showRecordVital.value = false;
        selectedVital.value = null;
    } catch (err: any) {
        error(err.error);
    } finally {
        savingVital.value = false;
    }
}

function openProfile() {
    if (!patient.value) return;

    router.push({
        path: `/app/branches/${b_uuid.value}/patients/${patient.value.uuid}`,
        query: { tab: tabSlugMap[activeTab.value] },
    });
}

onMounted(async () => {
    await fetchPatients();
    isLoading.value = false;
    await selectIndex(resolveIndex(patients.value));
    window.addEventListener("resize", updateScrollState);
});

onBeforeUnmount(() => {
    window.removeEventListener("resize", updateScrollState);
});
</script>

<template>
    <div class="min-h-screen-header rounded-lg bg-slate-100 dark:bg-surface">
        <div
            class="flex min-h-[calc(100dvh-var(--header-h)-1rem)] w-full flex-col gap-4"
        >
            <div
                class="overflow-hidden rounded-lg border border-[#E4EFED] bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
                :class="patient ? 'shrink-0' : 'flex-1'"
            >
                <div
                    class="flex flex-col gap-3 border-b border-[#E4EFED] px-3 py-4 sm:px-6 dark:border-white/10"
                >
                    <div class="flex flex-wrap items-center gap-3">
                        <PatientFilter
                            :search="searchQuery"
                            :type="typeFilter"
                            :assignment="assignmentFilter"
                            :date-from="dateFrom"
                            :date-to="dateTo"
                            search-placeholder="Search by patient name or code"
                            @update:search="searchQuery = $event"
                            @update:type="typeFilter = $event"
                            @update:assignment="assignmentFilter = $event"
                            @update:dateFrom="dateFrom = $event"
                            @update:dateTo="dateTo = $event"
                        />

                        <span
                            v-if="!isLoading && patients.length"
                            class="shrink-0 whitespace-nowrap rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-white/10 dark:text-gray-400"
                        >
                            {{ selectedIndex + 1 }} of {{ patientsTotal }}
                        </span>
                    </div>
                </div>

                <div v-if="isLoading" class="flex gap-2 overflow-hidden px-3 py-4 sm:px-6">
                    <div
                        v-for="chip in 5"
                        :key="chip"
                        class="flex shrink-0 animate-pulse items-center gap-2.5 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5 dark:border-white/10 dark:bg-white/5"
                    >
                        <div class="h-8 w-8 rounded-full bg-slate-200 dark:bg-white/15" />
                        <div class="h-3 w-24 rounded bg-slate-200 dark:bg-white/15" />
                    </div>
                </div>

                <div
                    v-else-if="!patients.length"
                    class="flex flex-col items-center justify-center py-12 text-center"
                >
                    <svg
                        viewBox="0 0 24 24"
                        class="mb-3 h-10 w-10 text-gray-300 dark:text-gray-500"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>

                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ hasFilters ? "No matching patients" : "No patients yet" }}
                    </p>

                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                        {{
                            hasFilters
                                ? "Try a different search term or filter."
                                : "Patients for this branch will show up here."
                        }}
                    </p>
                </div>

                <div
                    v-else
                    class="relative transition-opacity"
                    :class="isFetchingPatients ? 'pointer-events-none opacity-50' : ''"
                >
                    <button
                        v-if="canScrollLeft"
                        type="button"
                        aria-label="Scroll left"
                        class="absolute left-1 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border border-slate-100 bg-white text-slate-500 shadow-md transition hover:bg-slate-50 dark:border-white/10 dark:bg-secondary dark:text-gray-400 dark:hover:bg-white/5"
                        @click="scrollPatients(-1)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>

                    <div
                        ref="patientsScrollRef"
                        class="flex gap-2 overflow-x-auto scroll-smooth px-3 py-4 scrollbar-none sm:px-6"
                        @scroll="updateScrollState"
                    >
                        <button
                            v-for="(p, idx) in patients"
                            :key="p.uuid"
                            type="button"
                            class="flex min-w-fit items-center gap-2.5 rounded-xl border px-3 py-2 text-left transition-all"
                            :class="
                                selectedIndex === idx
                                    ? 'border-primary bg-primary/5 shadow-sm dark:bg-primary/10'
                                    : 'border-[#E4EFED] bg-white hover:bg-[#F7FAF9] dark:border-white/10 dark:bg-secondary dark:hover:bg-white/5'
                            "
                            @click="selectedIndex = idx"
                        >
                            <PatientAvatar :src="p.avatar" :name="p.full_name" />

                            <span class="min-w-0">
                                <span
                                    class="block max-w-40 truncate text-sm font-semibold"
                                    :class="
                                        selectedIndex === idx
                                            ? 'text-primary'
                                            : 'text-[#16302E] dark:text-white'
                                    "
                                >
                                    {{ p.full_name }}
                                </span>
                                <span
                                    class="block font-mono text-xs text-gray-400 dark:text-gray-500"
                                >
                                    {{ p.patient_code ?? "—" }}
                                </span>
                            </span>
                        </button>

                        <div
                            v-if="isLoadingMore"
                            class="flex shrink-0 animate-pulse items-center gap-2.5 rounded-xl border border-[#E4EFED] bg-white px-3 py-2 dark:border-white/10 dark:bg-secondary"
                        >
                            <div class="h-8 w-8 rounded-full bg-slate-200 dark:bg-white/15" />
                            <div class="space-y-1.5">
                                <div class="h-3 w-24 rounded bg-slate-200 dark:bg-white/15" />
                                <div class="h-2.5 w-16 rounded bg-slate-100 dark:bg-white/10" />
                            </div>
                        </div>
                    </div>

                    <button
                        v-if="canScrollRight"
                        type="button"
                        aria-label="Scroll right"
                        class="absolute right-1 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border border-slate-100 bg-white text-slate-500 shadow-md transition hover:bg-slate-50 dark:border-white/10 dark:bg-secondary dark:text-gray-400 dark:hover:bg-white/5"
                        @click="scrollPatients(1)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <div
                v-if="patient"
                class="min-w-0 flex-1 overflow-hidden rounded-lg border border-[#E4EFED] bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-[#E4EFED] px-3 py-4 sm:px-5 dark:border-white/10"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <PatientAvatar
                            :src="patient.avatar"
                            :name="patient.full_name"
                            size-class="h-11 w-11 text-sm"
                            rounded-class="rounded-xl"
                        />

                        <div class="min-w-0">
                            <h2
                                class="truncate text-base font-semibold text-[#16302E] dark:text-white"
                            >
                                {{ patient.full_name }}
                            </h2>
                            <p class="font-mono text-xs text-gray-400 dark:text-gray-500">
                                {{ patient.patient_code ?? "—" }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-[#E4EFED] px-3 py-2 text-xs font-semibold text-[#16302E] transition hover:bg-[#F7FAF9] dark:border-white/10 dark:text-white dark:hover:bg-white/5"
                        @click="openProfile"
                    >
                        View profile
                        <ArrowUpRight class="h-3.5 w-3.5" />
                    </button>
                </div>

                <div class="min-w-0 space-y-4 px-3 sm:px-5">
                    <div class="py-3 md:hidden">
                        <Combobox
                            :model-value="activeTab"
                            :items="tabItems"
                            label="Section"
                            placeholder="Select a section"
                            :search-bar="false"
                            @update:model-value="setActiveTab($event as Tab)"
                        />
                    </div>

                    <div class="hidden min-w-0 overflow-x-auto scrollbar-none md:block">
                        <nav class="flex w-max gap-4 sm:gap-7">
                            <button
                                v-for="tab in tabs"
                                :key="tab"
                                class="relative shrink-0 whitespace-nowrap py-4 text-sm font-medium"
                                :class="
                                    activeTab === tab
                                        ? 'text-primary'
                                        : 'text-gray-500 dark:text-gray-400'
                                "
                                @click="setActiveTab(tab)"
                            >
                                {{ tab }}

                                <span
                                    v-if="activeTab === tab"
                                    class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary"
                                />
                            </button>
                        </nav>
                    </div>

                    <template v-if="activeTab === 'Medication'">
                        <p
                            v-if="isFetchingMedications"
                            class="py-8 text-center text-sm text-gray-400 dark:text-gray-500"
                        >
                            Loading medications...
                        </p>

                        <template v-else>
                            <MedicationTable
                                :medications="medications"
                                :saving-dose="savingDosage"
                                @add-medication="showAddMedication = true"
                                @mark-dose="onDosageSubmit"
                            />

                            <Pagination
                                v-if="medicationsMeta && medicationsMeta.total > 0"
                                :current-page="medicationsMeta.current_page"
                                :total-pages="medicationsMeta.last_page"
                                :total-items="medicationsMeta.total"
                                :items-per-page="medicationsMeta.per_page"
                                class="pb-5"
                                @change-page="loadActiveTab"
                            />
                        </template>
                    </template>

                    <template v-else>
                        <p
                            v-if="isFetchingVitals"
                            class="py-8 text-center text-sm text-gray-400 dark:text-gray-500"
                        >
                            Loading vital signs...
                        </p>

                        <template v-else>
                            <VitalSignsTable
                                :vitals="vitals"
                                @add-vital="
                                    selectedVital = null;
                                    showRecordVital = true;
                                "
                                @edit-vital="editVital"
                            />

                            <Pagination
                                v-if="vitalsMeta && vitalsMeta.total > 0"
                                :current-page="vitalsMeta.current_page"
                                :total-pages="vitalsMeta.last_page"
                                :total-items="vitalsMeta.total"
                                :items-per-page="vitalsMeta.per_page"
                                class="pb-5"
                                @change-page="loadActiveTab"
                            />
                        </template>
                    </template>

                    <div class="pb-5" />
                </div>
            </div>
        </div>

        <ActionMedicationModal
            v-if="patient"
            :open="showAddMedication"
            :patient="patient"
            :submit-loading="savingMedication"
            @close="showAddMedication = false"
            @submit="onMedicationSubmit"
        />

        <ActionVitalModal
            v-if="patient"
            :patient="patient"
            :open="showRecordVital"
            :submit-loading="savingVital"
            :vital="selectedVital"
            @close="showRecordVital = false"
            @submit="onVitalSubmit"
        />
    </div>
</template>
