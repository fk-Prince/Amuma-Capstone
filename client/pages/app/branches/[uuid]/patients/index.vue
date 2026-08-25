<script setup lang="ts">
import { ref, onMounted, computed, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
    Eye,
    Pill,
    CalendarDays,
    LayoutGrid,
    List,
    Activity,
} from "lucide-vue-next";
import { calculateAge } from "~/utils/user";
import { patientService } from "~/api/patient/PatientService";
import type { PatientRetrieve } from "~/types/patient";
import PatientCard from "~/components/sections/app/Patient/PatientCard.vue";
import PatientFilter from "~/components/sections/app/Patient/PatientFilter.vue";
import { usePagination } from "~/composables/usePagination";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Patients" });

const route = useRoute();
const router = useRouter();

const viewMode = ref<"table" | "card">("table");

const patients = ref<PatientRetrieve[]>([]);
const isLoading = ref(true);
const isFetching = ref(false);
const searchQuery = ref("");
const typeFilter = ref("all");
const statusFilter = ref("all");
const dateFrom = ref("");
const dateTo = ref("");

const pagination = usePagination({ pageSize: 10 });

const b_uuid = computed(() => route.params.uuid as string);

const actionMenuItems = [
    {
        label: "View Information",
        icon: Eye,
        class: "text-slate-500 hover:bg-slate-100 hover:text-slate-700",
        route: (patient: PatientRetrieve) => ({
            path: `/app/branches/${b_uuid.value}/patients/${patient.uuid}`,
            query: { tab: "overview" },
        }),
    },
    {
        label: "View Medication",
        icon: Pill,
        class: "text-slate-500 hover:bg-slate-100 hover:text-slate-700",
        route: (patient: PatientRetrieve) => ({
            path: `/app/branches/${b_uuid.value}/patients/${patient.uuid}`,
            query: { tab: "medication" },
        }),
    },
    {
        label: "Schedules",
        icon: CalendarDays,
        class: "text-slate-500 hover:bg-slate-100 hover:text-slate-700",
        route: (patient: PatientRetrieve) => ({
            path: `/app/branches/${b_uuid.value}/patients/${patient.uuid}`,
            query: { tab: "schedule" },
        }),
    },
    {
        label: "Vitals",
        icon: Activity,
        class: "text-slate-500 hover:bg-slate-100 hover:text-slate-700",
        route: (patient: PatientRetrieve) => ({
            path: `/app/branches/${b_uuid.value}/patients/${patient.uuid}`,
            query: { tab: "vitals" },
        }),
    },
];

function goTo(destination: { path: string; query: Record<string, string> }) {
    router.push(destination);
}

async function fetchPatients(page = 1) {
    const isFirstLoad = patients.value.length === 0;
    isFirstLoad ? (isLoading.value = true) : (isFetching.value = true);

    try {
        const res: any = await patientService.list({
            branch_uuid: b_uuid.value,
            page,
            per_page: pagination.pageSize.value,
            search: searchQuery.value.trim() || undefined,
            category: typeFilter.value !== "all" ? typeFilter.value : undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        });

        patients.value = res.data ?? [];
        pagination.setTotal(
            res.meta?.total ?? res.total ?? patients.value.length,
        );
        pagination.currentPage.value = page;
    } catch (err) {
        console.error("Failed fetching patients", err);
        patients.value = [];
        pagination.setTotal(0);
    } finally {
        isLoading.value = false;
        isFetching.value = false;
    }
}

function goToPage(page: number) {
    fetchPatients(page);
}

watch([typeFilter, statusFilter, dateFrom, dateTo, searchQuery], () => {
    fetchPatients(1);
});

onMounted(() => {
    fetchPatients(1);
});

function currentAdmission(patient: PatientRetrieve) {
    const admissions = patient.admissions ?? [];
    if (!admissions.length) return undefined;

    const active = admissions.find((a) => a.status === "admitted");
    if (active) return active;

    return [...admissions].sort(
        (a, b) =>
            new Date(b.admitted_at).getTime() -
            new Date(a.admitted_at).getTime(),
    )[0];
}

function careType(patient: PatientRetrieve) {
    const status = patient.current_admission?.status.toLowerCase();
    const latestStatus = patient.latest_admission?.status.toLowerCase();

    if (status === "admitted") {
        return "Inhouse Facility";
    }

    if (patient.has_homecare) {
        return "Homecare Services";
    }

    if (status === "discharge" || status === "discharged") {
        return "Inhouse Facility";
    }

    if (
        latestStatus === "discharged" ||
        latestStatus === "waiting" ||
        latestStatus === "cancelled"
    ) {
        return "Inhouse Facility";
    }

    return "—";
}

const emptyStateTitle = computed(() =>
    searchQuery.value ||
    typeFilter.value !== "all" ||
    statusFilter.value !== "all"
        ? "No matching patients"
        : "No patients yet",
);
const emptyStateSubtitle = computed(() =>
    searchQuery.value ||
    typeFilter.value !== "all" ||
    statusFilter.value !== "all"
        ? "Try a different search term or filter."
        : "Patients for this branch will show up here.",
);
</script>

<template>
    <div class="min-h-[calc(100vh-90px)] bg-slate-100 p-2">
        <div class="max-w-8xl h-[calc(100vh-110px)] flex flex-col gap-4">
            <div
                class="bg-white rounded-lg shadow-sm border border-[#E4EFED] overflow-hidden flex-1 min-h-0 flex flex-col"
            >
                <div
                    class="flex flex-col gap-3 px-6 py-4 border-b border-[#E4EFED]"
                >
                    <div class="flex gap-3 items-center">
                        <PatientFilter
                            :search="searchQuery"
                            :type="typeFilter"
                            :date-from="dateFrom"
                            :date-to="dateTo"
                            @update:search="searchQuery = $event"
                            @update:type="typeFilter = $event"
                            @update:dateFrom="dateFrom = $event"
                            @update:dateTo="dateTo = $event"
                        />

                        <div
                            class="inline-flex items-center rounded-lg border border-[#E4EFED] bg-white p-1 shrink-0"
                        >
                            <button
                                type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-md transition"
                                :class="
                                    viewMode === 'table'
                                        ? 'bg-primary text-white'
                                        : 'text-slate-400 hover:text-slate-600'
                                "
                                @click="viewMode = 'table'"
                            >
                                <List class="h-4 w-4" />
                            </button>

                            <button
                                type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-md transition"
                                :class="
                                    viewMode === 'card'
                                        ? 'bg-primary text-white'
                                        : 'text-slate-400 hover:text-slate-600'
                                "
                                @click="viewMode = 'card'"
                            >
                                <LayoutGrid class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <template v-if="viewMode === 'table'">
                    <div class="flex-1 min-h-0 overflow-y-auto relative">
                        <div
                            v-if="isFetching && !isLoading"
                            class="absolute inset-0 bg-white/50 z-20 pointer-events-none"
                        />

                        <table class="w-full text-left border-collapse">
                            <thead class="sticky top-0 z-10">
                                <tr
                                    class="border-b border-[#E4EFED] bg-[#F7FAF9]"
                                >
                                    <th
                                        class="py-3 pl-6 pr-3 text-xs font-semibold text-muted uppercase tracking-wide"
                                    >
                                        Patient
                                    </th>
                                    <th
                                        class="py-3 px-3 text-xs font-semibold text-muted uppercase tracking-wide"
                                    >
                                        Gender
                                    </th>
                                    <th
                                        class="py-3 px-3 text-xs font-semibold text-muted uppercase tracking-wide"
                                    >
                                        Birthdate
                                    </th>
                                    <th
                                        class="py-3 px-3 text-xs font-semibold text-muted uppercase tracking-wide"
                                    >
                                        Citizen
                                    </th>
                                    <th
                                        class="py-3 px-3 text-xs font-semibold text-muted uppercase tracking-wide"
                                    >
                                        Care Type
                                    </th>
                                    <th
                                        class="py-3 pl-3 pr-6 text-xs font-semibold text-muted uppercase tracking-wide text-right"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-[#E4EFED]">
                                <template v-if="isLoading">
                                    <tr
                                        v-for="n in pagination.pageSize.value"
                                        :key="n"
                                    >
                                        <td colspan="6" class="py-4 px-6">
                                            <div
                                                class="h-6 rounded-md bg-slate-100 animate-pulse"
                                            />
                                        </td>
                                    </tr>
                                </template>

                                <tr
                                    v-else-if="
                                        !patients || patients.length === 0
                                    "
                                >
                                    <td colspan="6" class="py-16 text-center">
                                        <div
                                            class="flex flex-col items-center justify-center"
                                        >
                                            <svg
                                                viewBox="0 0 24 24"
                                                class="w-10 h-10 text-gray-300 mb-3"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.5"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <circle cx="11" cy="11" r="7" />
                                                <path d="m20 20-3.5-3.5" />
                                            </svg>

                                            <p
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                {{ emptyStateTitle }}
                                            </p>

                                            <p
                                                class="text-xs text-gray-400 mt-1"
                                            >
                                                {{ emptyStateSubtitle }}
                                            </p>
                                        </div>
                                    </td>
                                </tr>

                                <tr
                                    v-else
                                    v-for="patient in patients"
                                    :key="patient.patient_id"
                                    class="hover:bg-[#F7FAF9] transition"
                                >
                                    <td class="py-4 pl-6 pr-3">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="h-10 w-10 shrink-0 overflow-hidden rounded-full bg-slate-100"
                                            >
                                                <img
                                                    :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(patient.full_name)}&background=random&color=fff`"
                                                    :alt="patient.full_name"
                                                    class="h-full w-full object-cover"
                                                />
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-[#16302E]"
                                                >
                                                    {{ patient.full_name }}
                                                </p>
                                                <p
                                                    class="text-xs text-gray-400"
                                                >
                                                    {{
                                                        patient.location
                                                            ?.full_address ??
                                                        "—"
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td
                                        class="py-4 px-3 text-sm text-[#16302E]"
                                    >
                                        {{ patient.gender ?? "—" }}
                                    </td>

                                    <td
                                        class="py-4 px-3 text-sm text-[#16302E]"
                                    >
                                        {{
                                            calculateAge(patient.date_of_birth)
                                        }}
                                    </td>

                                    <td
                                        class="py-4 px-3 text-sm text-[#16302E]"
                                    >
                                        {{ patient.citizenship ?? "—" }}
                                    </td>

                                    <td
                                        class="py-4 px-3 text-sm text-[#16302E]"
                                    >
                                        {{ careType(patient) }}
                                    </td>

                                    <td class="py-4 pl-3 pr-6">
                                        <div
                                            class="flex items-center justify-end gap-1"
                                        >
                                            <button
                                                v-for="item in actionMenuItems"
                                                :key="item.label"
                                                type="button"
                                                :title="item.label"
                                                class="flex h-8 w-8 items-center justify-center rounded-md transition"
                                                :class="item.class"
                                                @click.stop="
                                                    goTo(item.route(patient))
                                                "
                                            >
                                                <component
                                                    :is="item.icon"
                                                    class="h-4 w-4"
                                                />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="!isLoading && patients && patients.length > 0"
                        class="shrink-0 flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-4 border-t border-[#E4EFED] bg-white"
                    >
                        <p class="text-xs text-muted">
                            Showing {{ pagination.rangeStart }}–{{
                                pagination.rangeEnd
                            }}
                            of {{ pagination.totalItems }}
                        </p>

                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                class="px-3 py-1.5 text-xs font-medium rounded-md border border-[#E4EFED] text-[#16302E] disabled:opacity-40 disabled:cursor-not-allowed hover:bg-[#F7FAF9] transition"
                                :disabled="!pagination.canGoPrev"
                                @click="
                                    goToPage(pagination.currentPage.value - 1)
                                "
                            >
                                Prev
                            </button>

                            <button
                                v-for="p in pagination.pageNumbers.value"
                                :key="p"
                                type="button"
                                class="w-8 h-8 text-xs font-medium rounded-md border transition"
                                :class="
                                    p === pagination.currentPage.value
                                        ? 'bg-primary text-white border-primary/80'
                                        : 'border-[#E4EFED] text-[#16302E] hover:bg-[#F7FAF9]'
                                "
                                @click="goToPage(p)"
                            >
                                {{ p }}
                            </button>

                            <button
                                type="button"
                                class="px-3 py-1.5 text-xs font-medium rounded-md border border-[#E4EFED] text-[#16302E] disabled:opacity-40 disabled:cursor-not-allowed hover:bg-[#F7FAF9] transition"
                                :disabled="!pagination.canGoNext"
                                @click="
                                    goToPage(pagination.currentPage.value + 1)
                                "
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </template>

                <div
                    v-else
                    class="flex-1 min-h-0 flex flex-col overflow-hidden"
                >
                    <div class="min-h-0 flex-1 overflow-y-auto p-4">
                        <div
                            v-if="isLoading"
                            class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"
                        >
                            <div
                                v-for="n in 6"
                                :key="n"
                                class="animate-pulse rounded-2xl border border-slate-100 bg-white p-5 shadow-sm space-y-4"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-12 w-12 rounded-full bg-slate-100"
                                    />
                                    <div class="flex-1 space-y-2">
                                        <div
                                            class="h-4 w-2/3 rounded bg-slate-100"
                                        />
                                        <div
                                            class="h-3 w-1/2 rounded bg-slate-100"
                                        />
                                    </div>
                                </div>
                                <div class="h-3 w-full rounded bg-slate-100" />
                                <div class="h-3 w-3/4 rounded bg-slate-100" />
                            </div>
                        </div>

                        <div
                            v-else-if="patients.length === 0"
                            class="flex flex-col items-center justify-center rounded-2xl border border-slate-100 bg-white py-20 shadow-sm"
                        >
                            <p class="text-sm font-medium text-slate-500">
                                {{ emptyStateTitle }}
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                {{ emptyStateSubtitle }}
                            </p>
                        </div>

                        <div
                            v-else
                            class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"
                        >
                            <PatientCard
                                v-for="patient in patients"
                                :key="patient.patient_id"
                                :patient="patient"
                                :care-type="careType(patient)"
                                :action-menu-items="
                                    actionMenuItems.map((item) => ({
                                        label: item.label,
                                        icon: item.icon,
                                        class: item.class,
                                        action: () => goTo(item.route(patient)),
                                    }))
                                "
                            />
                        </div>
                    </div>

                    <div
                        v-if="!isLoading && patients.length"
                        class="shrink-0 flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-4 border-t border-[#E4EFED] bg-white"
                    >
                        <p class="text-xs text-muted">
                            Showing {{ pagination.rangeStart }}–{{
                                pagination.rangeEnd
                            }}
                            of {{ pagination.totalItems }}
                        </p>

                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                class="px-3 py-1.5 text-xs font-medium rounded-md border border-[#E4EFED] text-[#16302E] disabled:opacity-40 disabled:cursor-not-allowed hover:bg-[#F7FAF9] transition"
                                :disabled="!pagination.canGoPrev"
                                @click="
                                    goToPage(pagination.currentPage.value - 1)
                                "
                            >
                                Prev
                            </button>

                            <button
                                v-for="p in pagination.pageNumbers.value"
                                :key="p"
                                type="button"
                                class="w-8 h-8 text-xs font-medium rounded-md border transition"
                                :class="
                                    p === pagination.currentPage.value
                                        ? 'bg-primary text-white border-primary/80'
                                        : 'border-[#E4EFED] text-[#16302E] hover:bg-[#F7FAF9]'
                                "
                                @click="goToPage(p)"
                            >
                                {{ p }}
                            </button>

                            <button
                                type="button"
                                class="px-3 py-1.5 text-xs font-medium rounded-md border border-[#E4EFED] text-[#16302E] disabled:opacity-40 disabled:cursor-not-allowed hover:bg-[#F7FAF9] transition"
                                :disabled="!pagination.canGoNext"
                                @click="
                                    goToPage(pagination.currentPage.value + 1)
                                "
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
