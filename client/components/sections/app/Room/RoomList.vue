<script setup lang="ts">
import type { Room } from "~/types/room";

const props = defineProps<{
    loading: boolean;
    rooms: Room[];
    expandedRooms: number[];
}>();

const emit = defineEmits<{
    toggle: [room_id: number];
    edit: [room: Room];
}>();

const isExpanded = (room_id: number) => {
    return props.expandedRooms.includes(room_id);
};

const toggleRoom = (room_id: number) => {
    emit("toggle", room_id);
};

const openEditRoom = (room: Room) => {
    emit("edit", room);
};
</script>

<template>
    <div class="space-y-3 max-h-[560px]">
        <div class="overflow-hidden pr-1 space-y-3 scroll-thin p-3">
            <template v-if="loading">
                <div
                    v-for="n in 3"
                    :key="n"
                    class="rounded-xl border border-gray-100 border-l-4 border-l-gray-200 bg-white p-4 animate-pulse"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="h-5 w-24 bg-gray-200 rounded"></div>

                            <div class="flex gap-2 mt-3">
                                <div class="h-3 w-20 bg-gray-200 rounded"></div>
                                <div class="h-3 w-16 bg-gray-200 rounded"></div>
                                <div class="h-3 w-24 bg-gray-200 rounded"></div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="w-3 h-3 rounded-full bg-gray-200"></div>
                            <div class="w-4 h-4 rounded bg-gray-200"></div>
                        </div>
                    </div>
                </div>
            </template>
            <template v-else>
                <div
                    v-if="rooms.length === 0"
                    class="flex flex-col items-center justify-center py-12 text-center"
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
                        <path d="M3 21h18M5 21V7l7-4 7 4v14" />
                        <path d="M9 21v-6h6v6" />
                    </svg>
                    <p class="text-sm font-medium text-gray-500">
                        No rooms found
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        Try adjusting your search or filters, or add a new room.
                    </p>
                </div>

                <template v-else>
                    <div
                        v-for="room in rooms"
                        :key="room.room_id"
                        class="rounded-xl border border-gray-100 border-l-4 bg-gray-50/60 overflow-hidden bg-white"
                        :class="
                            room.room_type === 'VIP'
                                ? 'border-l-amber-400 text-amber-500'
                                : 'border-l-violet-500 text-violet-500'
                        "
                    >
                        <button
                            type="button"
                            @click="toggleRoom(room.room_id)"
                            class="w-full text-left hover:bg-gray-100/60 transition-colors p-4 flex items-center justify-between gap-3"
                        >
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-gray-800">
                                        {{ room.room_no }}
                                    </p>
                                </div>
                                <div
                                    class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1.5 text-xs text-gray-400"
                                >
                                    <span class="flex items-center gap-1">
                                        <svg
                                            viewBox="0 0 24 24"
                                            class="w-3.5 h-3.5"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path
                                                d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"
                                            />
                                            <path
                                                d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"
                                            />
                                            <path
                                                d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"
                                            />
                                        </svg>
                                        Floor {{ room.floor }}
                                    </span>
                                    <span class="text-gray-200">|</span>
                                    <span class="flex items-center gap-1">
                                        <svg
                                            v-if="room.room_type === 'VIP'"
                                            viewBox="0 0 24 24"
                                            class="w-3.5 h-3.5"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                d="M2.5 19h19l-1.7-9.3-4.8 4-3-6.7-3 6.7-4.8-4Z"
                                            />
                                        </svg>

                                        <svg
                                            v-else
                                            viewBox="0 0 24 24"
                                            class="w-3.5 h-3.5"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                d="M3 21h18M5 21V7l7-4 7 4v14"
                                            />
                                        </svg>

                                        {{ room.room_type }}
                                    </span>
                                    <span class="text-gray-200">|</span>
                                    <span class="flex items-center gap-1">
                                        <svg
                                            viewBox="0 0 24 24"
                                            class="w-3.5 h-3.5"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path
                                                d="M2 18v-6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v6"
                                            />
                                            <path
                                                d="M2 12V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v4"
                                            />
                                            <path d="M2 20h20" />
                                        </svg>
                                        {{
                                            room.beds.filter((b) => b.patient)
                                                .length
                                        }}/{{ room.beds.length }}
                                        Occupied
                                    </span>
                                    <template
                                        v-if="
                                            room.beds.length -
                                                room.beds.filter(
                                                    (b) => b.patient,
                                                ).length >
                                            0
                                        "
                                    >
                                        <span class="text-gray-200">|</span>
                                        <span
                                            class="text-emerald-500 font-medium"
                                        >
                                            {{
                                                room.beds.length -
                                                room.beds.filter(
                                                    (b) => b.patient,
                                                ).length
                                            }}
                                            Available
                                        </span>
                                    </template>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 shrink-0">
                                <span
                                    class="w-2.5 h-2.5 rounded-full shrink-0"
                                    :class="
                                        room.beds.some((bed) => bed.patient)
                                            ? 'bg-red-400'
                                            : 'bg-emerald-400'
                                    "
                                ></span>

                                <svg
                                    viewBox="0 0 24 24"
                                    class="w-4 h-4 text-gray-400 shrink-0 transition-transform duration-200"
                                    :class="{
                                        'rotate-180': isExpanded(room.room_id),
                                    }"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        d="M6 9l6 6 6-6"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </div>
                        </button>

                        <div
                            v-if="isExpanded(room.room_id)"
                            class="px-4 pb-4 border-t border-gray-100 pt-4"
                        >
                            <div class="flex items-center gap-2 mb-3">
                                <button
                                    type="button"
                                    @click.stop="openEditRoom(room)"
                                    class="flex items-center gap-1.5 text-xs font-medium text-gray-600 border border-gray-200 rounded-lg px-3 py-1.5 bg-white hover:border-blue-300 hover:text-blue-600 transition-colors"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        class="w-3.5 h-3.5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                    Edit Room
                                </button>
                                <!-- @click.stop="openDeleteRoom(room)" -->
                                <button
                                    type="button"
                                    class="flex items-center gap-1.5 text-xs font-medium text-rose-500 border border-rose-200 rounded-lg px-3 py-1.5 bg-white hover:bg-rose-50 transition-colors"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        class="w-3.5 h-3.5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                    Delete
                                </button>
                            </div>

                            <p class="text-xs text-gray-400 mb-3">
                                Capacity: {{ room.beds.length }} bed{{
                                    room.beds.length > 1 ? "s" : ""
                                }}
                                ·
                                {{ room.beds.filter((b) => b.patient).length }}
                                assigned
                            </p>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div
                                    v-for="bed in room.beds"
                                    :key="bed.bed_id"
                                    class="rounded-lg p-3 bg-white"
                                    :class="
                                        bed.patient
                                            ? 'border border-gray-100'
                                            : 'border border-dashed border-gray-200'
                                    "
                                >
                                    <div
                                        class="flex items-center justify-between mb-1.5"
                                    >
                                        <span
                                            class="flex items-center gap-1.5 text-sm font-medium text-gray-700"
                                        >
                                            <svg
                                                viewBox="0 0 24 24"
                                                class="w-3.5 h-3.5 text-gray-400"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.8"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <path
                                                    d="M2 18v-6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v6"
                                                />
                                                <path
                                                    d="M2 12V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v4"
                                                />
                                                <path d="M2 20h20" />
                                            </svg>
                                            {{ bed.bed_no }}
                                        </span>
                                        <span
                                            class="text-[10px] font-medium px-2 py-0.5 rounded-full"
                                            :class="
                                                bed.patient
                                                    ? 'bg-sky-100 text-sky-600'
                                                    : 'bg-emerald-100 text-emerald-600'
                                            "
                                        >
                                            {{
                                                bed.patient
                                                    ? "Occupied"
                                                    : "Available"
                                            }}
                                        </span>
                                    </div>

                                    <template v-if="bed.patient">
                                        <div
                                            class="space-y-1 text-xs text-gray-600"
                                        >
                                            <p
                                                class="flex items-center gap-1.5"
                                            >
                                                <svg
                                                    viewBox="0 0 24 24"
                                                    class="w-3 h-3 text-gray-400"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <circle
                                                        cx="12"
                                                        cy="8"
                                                        r="4"
                                                    />
                                                    <path
                                                        d="M4 21c0-4 4-6 8-6s8 2 8 6"
                                                        stroke-linecap="round"
                                                    />
                                                </svg>
                                                {{ bed.patient.first_name }}
                                                {{ bed.patient.last_name }},
                                                <!-- {{ bed.patient.age }} yrs -->
                                            </p>
                                            <p
                                                class="flex items-center gap-1.5"
                                            >
                                                <svg
                                                    viewBox="0 0 24 24"
                                                    class="w-3 h-3 text-gray-400"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <rect
                                                        x="3"
                                                        y="4"
                                                        width="18"
                                                        height="17"
                                                        rx="1"
                                                    />
                                                    <path
                                                        d="M3 9h18M8 3v3M16 3v3"
                                                        stroke-linecap="round"
                                                    />
                                                </svg>
                                                Admitted
                                                <!-- {{ bed.patient.admittedDate }} -->
                                            </p>
                                        </div>

                                        <!-- @click.stop="
                                        viewpatientProfile(bed.patient.id)
                                    " -->
                                        <button
                                            type="button"
                                            class="text-[11px] font-medium text-blue-600 hover:text-blue-700 hover:underline mt-2 flex items-center gap-1"
                                        >
                                            View Full Profile
                                            <svg
                                                viewBox="0 0 24 24"
                                                class="w-3 h-3"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path
                                                    d="M5 12h14M13 6l6 6-6 6"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                            </svg>
                                        </button>

                                        <div
                                            class="flex items-center justify-end gap-3 mt-2 pt-2 border-t border-gray-50"
                                        >
                                            <!-- @click.stop="
                                        openEditpatient(
                                            room.id,
                                            bed.id,
                                            bed.patient,
                                        )
                                    " -->
                                            <button
                                                type="button"
                                                class="text-[11px] text-gray-500 hover:text-gray-700 flex items-center gap-1"
                                            >
                                                <svg
                                                    viewBox="0 0 24 24"
                                                    class="w-3 h-3"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    />
                                                </svg>
                                                Edit
                                            </button>
                                            <!-- @click.stop="
                                            openRemoveConfirm(
                                                room.id,
                                                bed.id,
                                                bed.patient.name,
                                                bed.label,
                                            )
                                        " -->
                                            <button
                                                type="button"
                                                class="text-[11px] text-rose-500 hover:text-rose-600 flex items-center gap-1"
                                            >
                                                <svg
                                                    viewBox="0 0 24 24"
                                                    class="w-3 h-3"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    />
                                                </svg>
                                                Remove
                                            </button>
                                        </div>
                                    </template>

                                    <!-- @click.stop="openAssignBed(room.id, bed.id)" -->
                                    <button
                                        v-else
                                        type="button"
                                        class="w-full text-xs font-medium text-violet-600 border border-violet-200 rounded-lg py-1.5 mt-1 hover:bg-violet-50 transition-colors"
                                    >
                                        Assign patient
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </template>
        </div>
    </div>
</template>
