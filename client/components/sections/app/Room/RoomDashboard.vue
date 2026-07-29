<script lang="ts" setup>
import { Modules } from "~/types/module";
import { usePermissions } from "~/composables/usePermission";
import PageHeader from "~/components/ui/PageHeader.vue";
import { type Overview } from "~/types/room";

const { canCreate } = usePermissions();

const props = defineProps<{
    overview?: Overview | null;
}>();

const overview = computed<Overview>(() => {
    return (
        props.overview ?? {
            total_rooms: {
                value: 0,
                secondary: "",
                trend: "",
            },
            available: {
                value: 0,
                secondary: "",
                trend: "",
            },
            occupied: {
                value: 0,
                secondary: "",
                trend: "",
            },
            maintenance: {
                value: 0,
                secondary: "",
                trend: "",
            },
        }
    );
});

const emit = defineEmits<{
    addRoom: [];
}>();
</script>

<template>
    <div class="flex justify-between items-end">
        <PageHeader
            title="Room and Bed Management"
            subtitle="Facility Management"
            description="View and manage available rooms, beds, and occupancy details."
        />

        <button
            v-if="canCreate(Modules.RoomsAndBeds)"
            @click="emit('addRoom')"
            class="inline-flex items-center gap-2 rounded-xl border border-primary bg-white px-5 py-2.5 text-sm font-medium text-primary transition-all duration-200 hover:bg-primary hover:text-white focus:outline-none focus:ring-2 focus:ring-primary/30"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 4v16m8-8H4"
                />
            </svg>
            <span>Add Room</span>
        </button>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div
            class="rounded-2xl border border-blue-200 bg-white p-4 shadow-sm transition-all duration-300 ease-in-out hover:shadow-lg hover:-translate-y-1 hover:border-blue-400"
        >
            <div
                class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center"
            >
                <svg
                    viewBox="0 0 24 24"
                    class="w-5.5 h-5.5 text-blue-600"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z" />
                    <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2" />
                    <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2" />
                    <path d="M10 6h4M10 10h4M10 14h4M10 18h4" />
                </svg>
            </div>

            <p
                class="mt-3 text-[11px] uppercase tracking-wide text-gray-400 font-medium"
            >
                Total Rooms
            </p>

            <p class="text-2xl font-bold text-gray-800">
                {{ overview.total_rooms.value }}
            </p>

            <p class="mt-1 text-xs text-emerald-600 flex items-center gap-1">
                <span v-if="overview.total_rooms.trend === 'up'">↑</span>
                <span class="text-gray-400 font-normal">
                    {{ overview.total_rooms.secondary }}
                </span>
            </p>
        </div>

        <div
            class="rounded-2xl border border-emerald-200 bg-white p-4 shadow-sm transition-all duration-300 ease-in-out hover:shadow-lg hover:-translate-y-1 hover:border-emerald-400"
        >
            <div
                class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center"
            >
                <svg
                    viewBox="0 0 24 24"
                    class="w-5.5 h-5.5 text-emerald-600"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M21.8 10A10 10 0 1 1 17 3.34" />
                    <path d="m9 11 3 3L22 4" />
                </svg>
            </div>

            <p
                class="mt-3 text-[11px] uppercase tracking-wide text-gray-400 font-medium"
            >
                Available
            </p>

            <p class="text-2xl font-bold text-gray-800">
                {{ overview.available.value }}
            </p>

            <p class="mt-1 text-xs text-emerald-600 flex items-center gap-1">
                <span v-if="overview.available.trend === 'up'">↑</span>
                <span class="text-gray-400 font-normal">
                    {{ overview.available.secondary }}
                </span>
            </p>
        </div>

        <div
            class="rounded-2xl border border-violet-200 bg-white p-4 shadow-sm transition-all duration-300 ease-in-out hover:shadow-lg hover:-translate-y-1 hover:border-violet-400"
        >
            <div
                class="w-11 h-11 rounded-xl bg-violet-50 flex items-center justify-center"
            >
                <svg
                    viewBox="0 0 24 24"
                    class="w-5.5 h-5.5 text-violet-600"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
            </div>

            <p
                class="mt-3 text-[11px] uppercase tracking-wide text-gray-400 font-medium"
            >
                Occupied
            </p>

            <p class="text-2xl font-bold text-gray-800">
                {{ overview.occupied.value }}
            </p>

            <p class="mt-1 text-xs text-emerald-600 flex items-center gap-1">
                <span v-if="overview.occupied.trend === 'up'">↑</span>
                <span class="text-gray-400 font-normal">
                    {{ overview.occupied.secondary }}
                </span>
            </p>
        </div>

        <div
            class="rounded-2xl border border-rose-200 bg-white p-4 shadow-sm transition-all duration-300 ease-in-out hover:shadow-lg hover:-translate-y-1 hover:border-rose-400"
        >
            <div
                class="w-11 h-11 rounded-xl bg-rose-50 flex items-center justify-center"
            >
                <svg
                    viewBox="0 0 24 24"
                    class="w-5.5 h-5.5 text-rose-600"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path
                        d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"
                    />
                    <path d="M12 9v4" />
                    <path d="M12 17h.01" />
                </svg>
            </div>

            <p
                class="mt-3 text-[11px] uppercase tracking-wide text-gray-400 font-medium"
            >
                Maintenance
            </p>

            <p class="text-2xl font-bold text-gray-800">
                {{ overview.maintenance.value }}
            </p>

            <p
                class="mt-1 text-xs font-medium"
                :class="
                    overview.maintenance.trend === 'success'
                        ? 'text-emerald-500'
                        : 'text-rose-500'
                "
            >
                {{ overview.maintenance.secondary }}
            </p>
        </div>
    </div>
</template>
