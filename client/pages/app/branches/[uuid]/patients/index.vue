<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import BaseInput from "~/components/ui/BaseInput.vue";
import DataTable, { type DataTableColumn } from "~/components/ui/DataTable.vue";
import {
    Search,
    SlidersHorizontal,
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
import PageHeader from "~/components/ui/PageHeader.vue";
import PatientCard from "~/components/sections/app/Patient/PatientCard.vue";
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
const searchQuery = ref("");

const pagination = usePagination({ pageSize: 10 });

const b_uuid = computed(() => route.params.uuid as string);

const columns: DataTableColumn[] = [
    { key: "patient", label: "Patient" },
    { key: "gender", label: "Gender" },
    { key: "birthdate", label: "Birthdate" },
    { key: "citizen", label: "Citizen" },
    { key: "careType", label: "Care Type" },
    { key: "action", label: "Action", align: "right" },
];

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
    try {
        isLoading.value = true;

        const res: any = await patientService.list({
            branch_uuid: b_uuid.value,
            page,
            per_page: pagination.pageSize.value,
            search: searchQuery.value.trim() || undefined,
        });

        patients.value = res.data ?? [];
        pagination.setTotal(
            res.meta?.total ?? res.total ?? patients.value.length,
        );
        pagination.currentPage.value = page;
    } catch (error) {
        console.error("Failed fetching patients", error);
        patients.value = [];
        pagination.setTotal(0);
    } finally {
        isLoading.value = false;
    }
}

function onSearch(query: string) {
    searchQuery.value = query;
    fetchPatients(1);
}

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
    const admission = currentAdmission(patient);
    const contract = admission?.invoices?.[0]?.contract;
    return contract?.category.toLowerCase() == "facility"
        ? "Inhouse Facility"
        : "Homecare Services";
}
</script>

<template>
    <div class="flex h-[calc(100vh-90px)] w-full flex-col overflow-hidden p-6">
        <div class="shrink-0 space-y-5 pb-5">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <PageHeader
                    title="Patients"
                    subtitle="Patient Management"
                    description="Manage patient information, care type, room assignments, and admission status."
                />

                <div class="flex items-center gap-3">
                    <div class="relative flex-1">
                        <Search
                            class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        />

                        <BaseInput
                            v-model="searchQuery"
                            placeholder="Search patients..."
                            input-class="pl-11"
                        />
                    </div>

                    <button
                        type="button"
                        class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50"
                    >
                        <SlidersHorizontal class="h-4 w-4" />
                    </button>

                    <div
                        class="inline-flex items-center rounded-lg border border-slate-200 bg-white p-1"
                    >
                        <button
                            type="button"
                            class="flex h-7 w-7 items-center justify-center rounded-md transition"
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
                            class="flex h-7 w-7 items-center justify-center rounded-md transition"
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
        </div>

        <div class="min-h-0 flex-1">
            <DataTable
                v-if="viewMode === 'table'"
                :columns="columns"
                :rows="patients"
                :pagination="pagination"
                :row-key="(row) => row.patient_id"
                :loading="isLoading"
                :searchable="false"
                :empty-title="
                    searchQuery ? 'No matching patients' : 'No patients found'
                "
                empty-description="Patients will appear here."
                @page-change="fetchPatients"
                @search="onSearch"
            >
                <template #cell-patient="{ row }">
                    <div class="flex items-center gap-3">
                        <div
                            class="h-10 w-10 shrink-0 overflow-hidden rounded-full bg-slate-100"
                        >
                            <img
                                :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(row.full_name)}&background=random&color=fff`"
                                :alt="row.full_name"
                                class="h-full w-full object-cover"
                            />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">
                                {{ row.full_name }}
                            </p>
                            <p class="text-xs text-slate-400">
                                {{ row.location?.full_address }}
                            </p>
                        </div>
                    </div>
                </template>

                <template #cell-gender="{ row }">
                    {{ row.gender ?? "—" }}
                </template>

                <template #cell-birthdate="{ row }">
                    {{ calculateAge(row.date_of_birth) }}
                </template>

                <template #cell-citizen="{ row }">
                    {{ row.citizenship ?? "—" }}
                </template>

                <template #cell-careType="{ row }">
                    {{ careType(row) }}
                </template>

                <template #cell-action="{ row }">
                    <div class="flex items-center justify-end gap-1">
                        <button
                            v-for="item in actionMenuItems"
                            :key="item.label"
                            type="button"
                            :title="item.label"
                            class="flex h-8 w-8 items-center justify-center rounded-md transition"
                            :class="item.class"
                            @click.stop="goTo(item.route(row))"
                        >
                            <component :is="item.icon" class="h-4 w-4" />
                        </button>
                    </div>
                </template>
            </DataTable>

            <div v-else class="flex h-full flex-col">
                <div class="min-h-0 flex-1 overflow-y-auto pb-5">
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
                            {{
                                searchQuery
                                    ? "No matching patients"
                                    : "No patients found"
                            }}
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            Patients will appear here.
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
                    class="shrink-0 flex flex-col items-center justify-between gap-3 border-t border-slate-100 bg-white px-1 py-4 sm:flex-row"
                >
                    <p class="text-xs text-slate-400">
                        Showing {{ pagination.rangeStart.value }} -
                        {{ pagination.rangeEnd.value }} of
                        {{ pagination.totalItems.value }}
                    </p>

                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            class="rounded-md border px-3 py-1.5 text-xs disabled:opacity-40"
                            :disabled="!pagination.canGoPrev.value"
                            @click="
                                fetchPatients(pagination.currentPage.value - 1)
                            "
                        >
                            Prev
                        </button>

                        <button
                            v-for="p in pagination.pageNumbers.value"
                            :key="p"
                            type="button"
                            class="h-8 w-8 rounded-md border text-xs"
                            :class="
                                p === pagination.currentPage.value
                                    ? 'bg-primary text-white'
                                    : ''
                            "
                            @click="fetchPatients(p)"
                        >
                            {{ p }}
                        </button>

                        <button
                            type="button"
                            class="rounded-md border px-3 py-1.5 text-xs disabled:opacity-40"
                            :disabled="!pagination.canGoNext.value"
                            @click="
                                fetchPatients(pagination.currentPage.value + 1)
                            "
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
