<script setup lang="ts">
import { ref, onMounted, computed, onBeforeUnmount } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
    Search,
    SlidersHorizontal,
    Plus,
    MoreVertical,
    Eye,
    Pencil,
} from "lucide-vue-next";

import { stringToDate } from "~/utils/time";

import PageHeader from "~/components/ui/PageHeader.vue";
import { patientService } from "~/api/patient/PatientService";
import { usePagination } from "~/composables/usePagination";
import type { PatientRetrieve } from "~/types/patient";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({
    title: "Patients",
});

const route = useRoute();
const router = useRouter();

const patients = ref<PatientRetrieve[]>([]);
const isLoading = ref(true);
const searchQuery = ref("");

const activeMenu = ref<number | null>(null);

const pagination = usePagination({
    pageSize: 10,
});

const b_uuid = computed(() => route.params.uuid as string);

async function fetchPatients() {
    try {
        isLoading.value = true;
        const res: any = await patientService.list({
            branch_uuid: b_uuid.value,
            page: pagination.currentPage.value,
            per_page: pagination.pageSize.value,
            search: searchQuery.value.trim() || undefined,
        });
        patients.value = res.data ?? [];
        const total = res.meta?.total ?? res.total ?? patients.value.length;
        pagination.setTotal(total);
    } catch (error) {
        console.error("Failed fetching patients", error);

        patients.value = [];
    } finally {
        isLoading.value = false;
    }
}

function goToPage(page: number) {
    if (page < 1 || page > pagination.totalPages.value) {
        return;
    }
    pagination.currentPage.value = page;
    fetchPatients();
}

onMounted(() => {
    fetchPatients();
});

function closeMenu() {
    activeMenu.value = null;
}

const menuPosition = ref({
    x: 0,
    y: 0,
});

function toggleMenu(id: number, event: MouseEvent) {
    const button = event.currentTarget as HTMLElement;
    const rect = button.getBoundingClientRect();

    menuPosition.value = {
        x: rect.right - 160,
        y: rect.bottom + 8,
    };

    activeMenu.value = activeMenu.value === id ? null : id;
}

function handleOutsideClick(event: MouseEvent) {
    const target = event.target as HTMLElement;

    if (!target.closest(".action-menu")) {
        closeMenu();
    }
}

onMounted(() => {
    document.addEventListener("click", handleOutsideClick);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleOutsideClick);
});

const avatarPalette = [
    "bg-rose-100 text-rose-600",
    "bg-sky-100 text-sky-600",
    "bg-violet-100 text-violet-600",
    "bg-amber-100 text-amber-600",
    "bg-emerald-100 text-emerald-600",
];

function avatarColor(patient: PatientRetrieve) {
    const key = String(
        patient.patient_id ?? `${patient.first_name}${patient.last_name}`,
    );
    let hash = 0;
    for (let i = 0; i < key.length; i++) {
        hash = (hash + key.charCodeAt(i)) % avatarPalette.length;
    }
    return avatarPalette[hash];
}

function calculateAge(date?: string) {
    if (!date) {
        return "—";
    }
    const birthDate = new Date(date);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const month = today.getMonth() - birthDate.getMonth();
    if (month < 0 || (month === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return `${stringToDate(date)} (${age} years old)`;
}

function patientStatus(patient: PatientRetrieve) {
    const admission = currentAdmission(patient);

    if (admission) {
        return "Admitted";
    }

    if (patient.admission?.length) {
        return "Discharged";
    }

    return "Homecare";
}

function statusClass(patient: PatientRetrieve) {
    switch (patientStatus(patient)) {
        case "Admitted":
            return "bg-emerald-100 text-emerald-700";
        case "Homecare":
            return "bg-blue-100 text-blue-700";
        default:
            return "bg-slate-100 text-slate-600";
    }
}

function careType(patient: PatientRetrieve) {
    return (
        currentAdmission(patient)?.contract?.accommodation_type ??
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

function currentAdmission(patient: PatientRetrieve) {
    const admissions = patient.admission ?? [];

    if (!admissions.length) {
        return undefined;
    }

    const active = admissions.find(
        (admission) => admission.status === "admitted",
    );

    if (active) {
        return active;
    }

    return [...admissions].sort(
        (a, b) =>
            new Date(b.admitted_at).getTime() -
            new Date(a.admitted_at).getTime(),
    )[0];
}
</script>

<template>
    <div class="flex min-h-[calc(100vh-10vh)] w-full flex-col space-y-5 p-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <PageHeader
                title="Patients"
                subtitle="Patient Management"
                description="Manage patient information, care type, room assignments, and admission status."
            />

            <div class="flex items-center gap-3">
                <div class="relative">
                    <Search
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    />

                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search patients..."
                        class="w-64 rounded-lg border border-slate-200 py-2 pl-9 pr-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                    />
                </div>

                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50"
                >
                    <SlidersHorizontal class="h-4 w-4" />
                </button>

                <button
                    type="button"
                    class="flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:opacity-90"
                >
                    <Plus class="h-4 w-4" />
                    Add Patient
                </button>
            </div>
        </div>

        <div
            class="flex flex-1 flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm"
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
                                Room / Bed
                            </th>

                            <th
                                class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-400"
                            >
                                Care Type
                            </th>

                            <th
                                class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-400"
                            >
                                Status
                            </th>

                            <th
                                class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-400"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-50">
                        <template v-if="isLoading">
                            <tr v-for="n in 6" :key="n">
                                <td colspan="7" class="px-6 py-4">
                                    <div
                                        class="h-6 animate-pulse rounded-md bg-slate-100"
                                    />
                                </td>
                            </tr>
                        </template>

                        <tr v-else-if="patients.length === 0">
                            <td colspan="7" class="py-16 text-center">
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
                            </td>
                        </tr>

                        <tr
                            v-for="patient in patients"
                            :key="patient.patient_id"
                            class="transition hover:bg-slate-50/60"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold"
                                        :class="avatarColor(patient)"
                                    >
                                        {{ patient.first_name?.[0] }}
                                        {{ patient.last_name?.[0] }}
                                    </div>

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-900"
                                        >
                                            {{ patient.full_name }}
                                        </p>

                                        <p class="text-xs text-slate-400">
                                            {{
                                                patient.citizenship ?? "Patient"
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
                                <template v-if="patient.admission">
                                    <p>
                                        Room:

                                        <span class="font-medium">
                                            {{
                                                currentAdmission(patient)?.room
                                                    ?.room_no ?? "—"
                                            }}
                                        </span>
                                    </p>

                                    <p class="text-xs text-slate-400">
                                        Bed:

                                        {{
                                            currentAdmission(patient)?.bed
                                                ?.bed_no ?? "—"
                                        }}
                                    </p>
                                </template>

                                <template v-else>
                                    <span class="text-slate-400">
                                        Homecare
                                    </span>
                                </template>
                            </td>

                            <td class="px-4 py-4 text-sm text-slate-600">
                                {{ careType(patient) }}
                            </td>

                            <td class="px-4 py-4">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="statusClass(patient)"
                                >
                                    {{ patientStatus(patient) }}
                                </span>
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
                                                @click="item.action(patient)"
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

            <div
                v-if="!isLoading && patients.length"
                class="mt-auto flex flex-col items-center justify-between gap-3 border-t border-slate-100 px-6 py-4 sm:flex-row"
            >
                <p class="text-xs text-slate-400">
                    Showing
                    {{ pagination.rangeStart }}
                    -
                    {{ pagination.rangeEnd }}
                    of
                    {{ pagination.totalItems }}
                </p>

                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        class="rounded-md border px-3 py-1.5 text-xs disabled:opacity-40"
                        :disabled="!pagination.canGoPrev"
                        @click="goToPage(pagination.currentPage.value - 1)"
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
                        @click="goToPage(p)"
                    >
                        {{ p }}
                    </button>

                    <button
                        type="button"
                        class="rounded-md border px-3 py-1.5 text-xs disabled:opacity-40"
                        :disabled="!pagination.canGoNext"
                        @click="goToPage(pagination.currentPage.value + 1)"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
