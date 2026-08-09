<script setup lang="ts">
import { ref, onMounted, computed, onBeforeUnmount, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import BaseInput from "~/components/ui/BaseInput.vue";
import {
    Search,
    SlidersHorizontal,
    Plus,
    MoreVertical,
    Eye,
    Pencil,
    LayoutGrid,
    List,
} from "lucide-vue-next";
import { calculateAge } from "~/utils/user";
import { patientService } from "~/api/patient/PatientService";
import type { PatientRetrieve } from "~/types/patient";
import PageHeader from "~/components/ui/PageHeader.vue";
import PatientCard from "~/components/sections/app/Patient/PatientCard.vue";

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

const currentPage = ref(1);
const pageSize = 10;
const totalItems = ref(0);
const totalPages = computed(() =>
    Math.max(Math.ceil(totalItems.value / pageSize), 1),
);
const rangeStart = computed(() =>
    totalItems.value === 0 ? 0 : (currentPage.value - 1) * pageSize + 1,
);
const rangeEnd = computed(() =>
    Math.min(currentPage.value * pageSize, totalItems.value),
);
const canGoPrev = computed(() => currentPage.value > 1);
const canGoNext = computed(() => currentPage.value < totalPages.value);

const pageNumbers = computed(() => {
    const total = totalPages.value;
    const current = currentPage.value;
    const delta = 2;
    const pages: number[] = [];

    for (
        let p = Math.max(1, current - delta);
        p <= Math.min(total, current + delta);
        p++
    ) {
        pages.push(p);
    }

    return pages;
});

const activeMenu = ref<number | null>(null);
const menuPosition = ref({ x: 0, y: 0 });

const b_uuid = computed(() => route.params.uuid as string);

async function fetchPatients(page = 1) {
    try {
        isLoading.value = true;

        const res: any = await patientService.list({
            branch_uuid: b_uuid.value,
            page,
            per_page: pageSize,
            search: searchQuery.value.trim() || undefined,
        });

        patients.value = res.data ?? [];
        totalItems.value =
            res.meta?.total ?? res.total ?? patients.value.length;
        currentPage.value = page;
    } catch (error) {
        console.error("Failed fetching patients", error);
        patients.value = [];
    } finally {
        isLoading.value = false;
    }
}

function goToPage(page: number) {
    if (page < 1 || page > totalPages.value) return;
    fetchPatients(page);
}

let searchDebounce: ReturnType<typeof setTimeout>;
watch(searchQuery, () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => fetchPatients(1), 350);
});

onMounted(() => {
    fetchPatients(1);
});

function closeMenu() {
    activeMenu.value = null;
}

function toggleMenu(id: number, event: MouseEvent) {
    const button = event.currentTarget as HTMLElement;
    const rect = button.getBoundingClientRect();

    menuPosition.value = { x: rect.right - 160, y: rect.bottom + 8 };
    activeMenu.value = activeMenu.value === id ? null : id;
}

function handleOutsideClick(event: MouseEvent) {
    const target = event.target as HTMLElement;
    if (!target.closest(".action-menu")) closeMenu();
}

onMounted(() => document.addEventListener("click", handleOutsideClick));
onBeforeUnmount(() =>
    document.removeEventListener("click", handleOutsideClick),
);

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
    return (
        contract?.accommodation_type ??
        contract?.category ??
        "Homecare Services"
    );
}

const actionMenuItems = [
    {
        label: "View Information",
        icon: Eye,
        class: "text-slate-600 hover:bg-slate-50",
        action: (patient: PatientRetrieve) => {
            router.push(
                `/app/branches/${b_uuid.value}/patients/${patient.uuid}`,
            );
            closeMenu();
        },
    },
    {
        label: "View Medication",
        icon: Eye,
        class: "text-slate-600 hover:bg-slate-50",
        action: (patient: PatientRetrieve) => {
            router.push(
                `/app/branches/${b_uuid.value}/patients/${patient.uuid}/medications`,
            );
            closeMenu();
        },
    },
    {
        label: "Schedules",
        icon: Pencil,
        class: "text-slate-600 hover:bg-slate-50",
        action: (patient: PatientRetrieve) => {
            router.push(
                `/app/branches/${b_uuid.value}/patients/${patient.uuid}/schedules`,
            );
            closeMenu();
        },
    },
];
</script>

<template>
    <div class="flex min-h-[calc(100vh-10vh)] w-full flex-col">
        <div class="flex-1 space-y-5 p-6 pb-0">
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
                            :model-value="searchQuery"
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

            <div
                v-if="isLoading && viewMode === 'table'"
                class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm"
            >
                <div
                    v-for="n in 6"
                    :key="n"
                    class="border-b border-slate-50 px-6 py-4"
                >
                    <div class="h-6 animate-pulse rounded-md bg-slate-100" />
                </div>
            </div>

            <div
                v-else-if="isLoading"
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"
            >
                <div
                    v-for="n in 6"
                    :key="n"
                    class="animate-pulse rounded-2xl border border-slate-100 bg-white p-5 shadow-sm space-y-4"
                >
                    <div class="flex items-center gap-3">
                        <div class="h-12 w-12 rounded-full bg-slate-100" />
                        <div class="flex-1 space-y-2">
                            <div class="h-4 w-2/3 rounded bg-slate-100" />
                            <div class="h-3 w-1/2 rounded bg-slate-100" />
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
                v-else-if="viewMode === 'table'"
                class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm"
            >
                <div class="overflow-x-auto overflow-y-visible">
                    <table class="w-full min-w-[900px] text-left">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th
                                    class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-400"
                                >
                                    Patient
                                </th>
                                <th
                                    class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-400"
                                >
                                    Gender
                                </th>
                                <th
                                    class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-400"
                                >
                                    Birthdate
                                </th>
                                <th
                                    class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-400"
                                >
                                    Citizen
                                </th>
                                <th
                                    class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-400"
                                >
                                    Care Type
                                </th>
                                <th
                                    class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-400"
                                >
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-50">
                            <tr
                                v-for="patient in patients"
                                :key="patient.patient_id"
                                class="transition hover:bg-slate-50/60"
                            >
                                <td class="px-6 py-4">
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
                                                class="text-sm font-semibold text-slate-900"
                                            >
                                                {{ patient.full_name }}
                                            </p>
                                            <p class="text-xs text-slate-400">
                                                {{
                                                    patient.location
                                                        ?.full_address
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-4 text-sm text-slate-600">
                                    {{ patient.gender ?? "—" }}
                                </td>

                                <td class="px-4 py-4 text-sm text-slate-600">
                                    {{ calculateAge(patient.date_of_birth) }}
                                </td>

                                <td class="px-4 py-4 text-sm text-slate-600">
                                    {{ patient.citizenship ?? "—" }}
                                </td>

                                <td class="px-4 py-4 text-sm text-slate-600">
                                    {{ careType(patient) }}
                                </td>

                                <td class="px-6 py-4">
                                    <div
                                        class="action-menu relative flex justify-end"
                                    >
                                        <button
                                            type="button"
                                            class="rounded-md p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                            @click.stop="
                                                toggleMenu(
                                                    patient.patient_id,
                                                    $event,
                                                )
                                            "
                                        >
                                            <MoreVertical class="h-4 w-4" />
                                        </button>

                                        <Teleport to="body">
                                            <div
                                                v-if="
                                                    activeMenu ===
                                                    patient.patient_id
                                                "
                                                class="fixed z-[9999] w-40 rounded-lg border bg-white shadow-lg"
                                                :style="{
                                                    top: `${menuPosition.y}px`,
                                                    left: `${menuPosition.x}px`,
                                                }"
                                            >
                                                <button
                                                    v-for="item in actionMenuItems"
                                                    :key="item.label"
                                                    type="button"
                                                    class="flex w-full items-center gap-2 px-4 py-2 text-sm"
                                                    :class="item.class"
                                                    @click="
                                                        item.action(patient)
                                                    "
                                                >
                                                    <component
                                                        :is="item.icon"
                                                        class="h-4 w-4"
                                                    />
                                                    {{ item.label }}
                                                </button>
                                            </div>
                                        </Teleport>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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
                    :is-menu-open="activeMenu === patient.patient_id"
                    :menu-position="menuPosition"
                    :action-menu-items="actionMenuItems"
                    @toggle-menu="toggleMenu"
                />
            </div>
        </div>

        <div
            v-if="!isLoading && patients.length"
            class="sticky bottom-0 z-10 mt-6 flex flex-col items-center justify-between gap-3 border-t border-slate-100 bg-white/95 px-6 py-4 backdrop-blur sm:flex-row"
        >
            <p class="text-xs text-slate-400">
                Showing {{ rangeStart }} - {{ rangeEnd }} of {{ totalItems }}
            </p>

            <div class="flex items-center gap-1">
                <button
                    type="button"
                    class="rounded-md border px-3 py-1.5 text-xs disabled:opacity-40"
                    :disabled="!canGoPrev"
                    @click="goToPage(currentPage - 1)"
                >
                    Prev
                </button>

                <button
                    v-for="p in pageNumbers"
                    :key="p"
                    type="button"
                    class="h-8 w-8 rounded-md border text-xs"
                    :class="p === currentPage ? 'bg-primary text-white' : ''"
                    @click="goToPage(p)"
                >
                    {{ p }}
                </button>

                <button
                    type="button"
                    class="rounded-md border px-3 py-1.5 text-xs disabled:opacity-40"
                    :disabled="!canGoNext"
                    @click="goToPage(currentPage + 1)"
                >
                    Next
                </button>
            </div>
        </div>
    </div>
</template>
