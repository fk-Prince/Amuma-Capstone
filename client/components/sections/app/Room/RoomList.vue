<script setup lang="ts">
import type { Room } from "~/types/room";
import { Modules } from "~/types/module";
import { usePermissions } from "~/composables/usePermission";
import {
    Building2,
    Crown,
    BedSingle,
    BedDouble,
    Layers3,
    ChevronDown,
    Pencil,
    Plus,
} from "lucide-vue-next";
import BedCard from "./BedCard.vue";
import CurrentAdmissionCard from "./CurrentCard.vue";
import ReservedAdmissionCard from "./ReservedCard.vue";
import type { Bed, BedForm } from "~/types/bed.js";
const { canUpdate } = usePermissions();

const props = defineProps<{
    loading: boolean;
    rooms: Room[];
    expandedRooms: number[];
    errors?: any;
}>();

const emit = defineEmits<{
    toggle: [room_id: number];
    edit: [room: Room];
    bedAction: [
        action: "create" | "update",
        room: Room,
        bed: BedForm,
        done: () => void,
    ];
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

const capacityOf = (room: Room) => {
    const n = parseInt(room.capacity, 10);
    return Number.isNaN(n) ? room.beds.length : n;
};

const remainingSlots = (room: Room) => {
    const remaining = capacityOf(room) - room.beds.length;
    return remaining > 0 ? Array.from({ length: remaining }) : [];
};

type BedState = "occupied" | "reserved" | "available" | "maintenance";

const bedState = (bed: Bed): BedState => {
    if (bed.status === "Maintenance") return "maintenance";
    if (bed.current_admission?.patient) return "occupied";
    if (bed.reserved_admission?.patient) return "reserved";
    return "available";
};

const isOccupied = (bed: Bed) => bedState(bed) === "occupied";
const isReserved = (bed: Bed) => bedState(bed) === "reserved";
const isAvailable = (bed: Bed) => bedState(bed) === "available";
const isMaintenance = (bed: Bed) => bedState(bed) === "maintenance";
const editingBedId = ref<number | null>(null);

const addingSlot = ref<{
    roomId: number;
    index: number;
} | null>(null);

const openAddBed = (room: Room, index: number) => {
    addingSlot.value = {
        roomId: room.room_id,
        index,
    };
};

const cancelAddBed = () => {
    addingSlot.value = null;
};
</script>

<template>
    <div class="space-y-3">
        <div class="overflow-y-auto pr-1 space-y-3 scroll-thin p-3">
            <template v-if="loading">
                <div
                    v-for="n in 3"
                    :key="n"
                    class="rounded-2xl border border-gray-100 border-l-4 border-l-gray-200 bg-white p-4 animate-pulse dark:border-white/10 dark:bg-secondary"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="h-5 w-24 bg-gray-200 rounded dark:bg-white/15"></div>
                            <div class="flex gap-2 mt-3">
                                <div class="h-3 w-20 bg-gray-200 rounded dark:bg-white/15"></div>
                                <div class="h-3 w-16 bg-gray-200 rounded dark:bg-white/15"></div>
                                <div class="h-3 w-24 bg-gray-200 rounded dark:bg-white/15"></div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-3 h-3 rounded-full bg-gray-200 dark:bg-white/15"></div>
                            <div class="w-4 h-4 rounded bg-gray-200 dark:bg-white/15"></div>
                        </div>
                    </div>
                </div>
            </template>

            <template v-else>
                <div
                    v-if="rooms.length === 0"
                    class="flex flex-col items-center justify-center py-14 text-center"
                >
                    <div
                        class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mb-3 dark:bg-white/5"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            class="w-8 h-8 text-gray-300 dark:text-gray-500"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M3 21h18M5 21V7l7-4 7 4v14" />
                            <path d="M9 21v-6h6v6" />
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                        No rooms found
                    </p>
                    <p class="text-xs text-gray-400 mt-1 max-w-[240px] dark:text-gray-500">
                        Try adjusting your search or filters, or add a new room.
                    </p>
                </div>

                <template v-else>
                    <div
                        v-for="room in rooms"
                        :key="room.room_id"
                        class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-lg dark:border-white/10 dark:bg-secondary dark:hover:border-white/10"
                    >
                        <button
                            type="button"
                            @click="toggleRoom(room.room_id)"
                            class="w-full text-left hover:bg-gray-50/70 transition-colors p-4 flex items-center justify-between gap-3 dark:hover:bg-white/5"
                        >
                            <div class="min-w-0 flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                    :class="
                                        room.room_type === 'VIP'
                                            ? 'bg-amber-50 text-amber-500 dark:bg-amber-500/10 dark:text-amber-300'
                                            : 'bg-violet-50 text-violet-500 dark:bg-violet-500/10 dark:text-violet-300'
                                    "
                                >
                                    <Crown
                                        v-if="room.room_type === 'VIP'"
                                        class="h-5 w-5"
                                    />
                                    <Building2 v-else class="h-5 w-5" />
                                </div>

                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h3
                                            class="truncate text-lg font-semibold tracking-tight text-slate-900 dark:text-white"
                                        >
                                            {{ room.room_no }}
                                        </h3>

                                        <span
                                            class="inline-flex items-center rounded-lg px-2.5 py-1 text-[11px] font-semibold"
                                            :class="
                                                room.room_type === 'VIP'
                                                    ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20'
                                                    : 'bg-violet-50 text-violet-700 ring-1 ring-violet-200 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-500/20'
                                            "
                                        >
                                            {{ room.room_type }}
                                        </span>
                                    </div>

                                    <div
                                        class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-400 dark:text-gray-500"
                                    >
                                        <span class="flex items-center gap-1">
                                            <Layers3 class="h-3.5 w-3.5" />
                                            {{ room.floor }} Floor
                                        </span>

                                        <span class="flex items-center gap-1">
                                            <BedDouble class="h-3.5 w-3.5" />
                                            {{
                                                room.beds.filter(
                                                    (b) =>
                                                        isOccupied(b) ||
                                                        isReserved(b),
                                                ).length
                                            }}
                                            / {{ capacityOf(room) }} occupied
                                        </span>

                                        <span
                                            v-if="
                                                room.beds.some((b) =>
                                                    isReserved(b),
                                                )
                                            "
                                            class="flex uppercase items-center gap-1 font-medium text-amber-500 dark:text-amber-300"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full bg-amber-400"
                                            ></span>
                                            {{
                                                room.beds.filter((b) =>
                                                    isReserved(b),
                                                ).length
                                            }}
                                            Reserved
                                        </span>

                                        <span
                                            v-if="
                                                room.beds.some((b) =>
                                                    isAvailable(b),
                                                )
                                            "
                                            class="flex items-center gap-1 font-medium text-emerald-500 dark:text-emerald-300"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full bg-emerald-400"
                                            ></span>
                                            {{
                                                room.beds.filter((b) =>
                                                    isAvailable(b),
                                                ).length
                                            }}
                                            Available
                                        </span>

                                        <span
                                            v-if="remainingSlots(room).length"
                                            class="flex items-center uppercase gap-1 font-medium text-amber-500 dark:text-amber-300"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full bg-amber-400"
                                            ></span>
                                            {{ remainingSlots(room).length }}
                                            open
                                            {{
                                                remainingSlots(room).length ===
                                                1
                                                    ? "slot"
                                                    : "slots"
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 shrink-0">
                                <span
                                    class="w-2.5 h-2.5 rounded-full shrink-0"
                                    :class="{
                                        'bg-pink-400':
                                            room.status === 'Maintenance' ||
                                            room.beds.some((bed) =>
                                                isMaintenance(bed),
                                            ),

                                        'bg-sky-400':
                                            room.status !== 'Maintenance' &&
                                            room.beds.length > 0 &&
                                            room.beds.every((bed) =>
                                                isOccupied(bed),
                                            ),

                                        'bg-amber-400':
                                            room.status !== 'Maintenance' &&
                                            room.beds.some(
                                                (bed) =>
                                                    isOccupied(bed) ||
                                                    isReserved(bed),
                                            ) &&
                                            !room.beds.every((bed) =>
                                                isOccupied(bed),
                                            ),

                                        'bg-emerald-400':
                                            room.status !== 'Maintenance' &&
                                            room.beds.length > 0 &&
                                            room.beds.every((bed) =>
                                                isAvailable(bed),
                                            ),
                                    }"
                                ></span>

                                <ChevronDown
                                    class="h-4 w-4 text-gray-400 transition-transform duration-200 dark:text-gray-500"
                                    :class="{
                                        'rotate-180': isExpanded(room.room_id),
                                    }"
                                />
                            </div>
                        </button>

                        <div
                            v-if="isExpanded(room.room_id)"
                            class="px-4 pb-4 border-t border-gray-100 pt-4 bg-gray-50/40 dark:border-white/10 dark:bg-white/5"
                        >
                            <div class="flex items-center justify-between mb-4">
                                <p class="text-xs text-gray-400 mb-3 dark:text-gray-500">
                                    Capacity: {{ capacityOf(room) }} bed{{
                                        capacityOf(room) > 1 ? "s" : ""
                                    }}
                                    ·
                                    {{
                                        room.beds.filter((b) => isOccupied(b))
                                            .length
                                    }}
                                    assigned ·
                                    {{
                                        room.beds.filter((b) => isReserved(b))
                                            .length
                                    }}
                                    reserved ·
                                    {{
                                        room.beds.filter((b) =>
                                            isMaintenance(b),
                                        ).length
                                    }}
                                    maintenance ·
                                    {{ remainingSlots(room).length }} open
                                    slot{{
                                        remainingSlots(room).length !== 1
                                            ? "s"
                                            : ""
                                    }}
                                </p>

                                <div
                                    class="flex items-center gap-2"
                                    v-if="canUpdate(Modules.RoomsAndBeds)"
                                >
                                    <button
                                        type="button"
                                        @click.stop="openEditRoom(room)"
                                        class="flex items-center gap-1.5 text-xs font-medium text-gray-600 border border-gray-200 rounded-lg px-3 py-1.5 bg-white hover:border-blue-300 hover:text-blue-600 transition-colors dark:text-gray-400 dark:border-white/10 dark:bg-secondary dark:hover:text-blue-300"
                                    >
                                        <Pencil class="h-3.5 w-3.5" />
                                        Edit Room
                                    </button>
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div
                                    v-for="bed in room.beds"
                                    :key="bed.bed_id"
                                    class="rounded-xl p-3.5 bg-white transition-shadow hover:shadow-sm dark:bg-secondary"
                                    :class="{
                                        'border border-gray-100 dark:border-white/10':
                                            isOccupied(bed),
                                        'border border-dashed border-indigo-200 bg-indigo-50/20 dark:border-indigo-500/20 dark:bg-indigo-500/15':
                                            isReserved(bed),
                                        'border border-dashed border-emerald-200 bg-emerald-50/20 dark:border-emerald-500/20 dark:bg-emerald-500/10':
                                            isAvailable(bed),
                                        'border border-dashed border-pink-200 bg-pink-50/30':
                                            isMaintenance(bed),
                                    }"
                                >
                                    <div
                                        class="flex items-center justify-between mb-2.5"
                                    >
                                        <span
                                            class="flex items-center gap-1.5 text-sm font-semibold text-gray-700 dark:text-gray-400"
                                        >
                                            <BedSingle
                                                class="h-3.5 w-3.5 text-gray-400 dark:text-gray-500"
                                            />
                                            {{ bed.bed_no }}
                                        </span>

                                        <span
                                            class="flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                            :class="{
                                                'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300':
                                                    isOccupied(bed),
                                                'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300':
                                                    isReserved(bed),
                                                'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300':
                                                    isAvailable(bed),
                                                'bg-pink-100 text-pink-600':
                                                    isMaintenance(bed),
                                            }"
                                        >
                                            {{
                                                isOccupied(bed)
                                                    ? "Occupied"
                                                    : isReserved(bed)
                                                      ? "Reserved"
                                                      : isMaintenance(bed)
                                                        ? "Maintenance"
                                                        : "Available"
                                            }}
                                        </span>
                                    </div>

                                    <BedCard
                                        v-if="editingBedId === bed.bed_id"
                                        :bedData="bed"
                                        action="update"
                                        @cancel="editingBedId = null"
                                        :loading="loading"
                                        :errors="errors"
                                        @bedAction="
                                            (action, updatedBed, done) =>
                                                emit(
                                                    'bedAction',
                                                    action,
                                                    room,
                                                    updatedBed,
                                                    done,
                                                )
                                        "
                                    />

                                    <template v-else>
                                        <CurrentAdmissionCard
                                            v-if="
                                                bed.current_admission?.patient
                                            "
                                            :bed="bed"
                                            @edit-bed="editingBedId = $event"
                                        />

                                        <ReservedAdmissionCard
                                            v-else-if="bed.reserved_admission"
                                            :bed="bed"
                                            @edit-bed="editingBedId = $event"
                                        />

                                        <template v-else>
                                            <button
                                                type="button"
                                                @click="
                                                    editingBedId = bed.bed_id
                                                "
                                                class="w-full flex items-center justify-center gap-1.5 text-xs font-medium text-blue-600 bg-white border border-blue-200 rounded-lg py-2 mt-1 hover:bg-blue-100 transition-colors dark:text-blue-300 dark:bg-secondary dark:border-blue-500/20 dark:hover:bg-blue-500/15"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                                Update Bed
                                            </button>
                                        </template>
                                    </template>
                                </div>

                                <template
                                    v-for="(_, i) in remainingSlots(room)"
                                    :key="'slot-' + i"
                                >
                                    <button
                                        v-if="
                                            !addingSlot ||
                                            addingSlot.roomId !==
                                                room.room_id ||
                                            addingSlot.index !== i
                                        "
                                        v-show="canUpdate(Modules.RoomsAndBeds)"
                                        type="button"
                                        @click.stop="openAddBed(room, i)"
                                        class="group flex min-h-[140px] flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-gradient-to-br from-white to-slate-50 dark:from-secondary dark:to-white/5 p-5 transition-all duration-300 hover:-translate-y-1 hover:border-primary hover:shadow-lg dark:border-white/10"
                                    >
                                        <div
                                            class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 transition-all duration-300 group-hover:scale-110 group-hover:bg-primary group-hover:text-white"
                                        >
                                            <Plus class="h-7 w-7" />
                                        </div>

                                        <h4
                                            class="text-sm font-semibold text-slate-700 dark:text-gray-400"
                                        >
                                            Add New Bed
                                        </h4>

                                        <p
                                            class="mt-1 text-center text-xs text-slate-500 dark:text-gray-400"
                                        >
                                            Create another bed for this room.
                                        </p>
                                    </button>

                                    <BedCard
                                        v-else
                                        action="create"
                                        :loading="loading"
                                        :errors="errors"
                                        @cancel="cancelAddBed"
                                        @bedAction="
                                            (action, bed, done) =>
                                                emit(
                                                    'bedAction',
                                                    action,
                                                    room,
                                                    bed,
                                                    done,
                                                )
                                        "
                                    />
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </template>
        </div>
    </div>
</template>
