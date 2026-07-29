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
    UserPlus,
    Plus,
    Phone,
    Briefcase,
    Heart,
    Globe2,
    Droplet,
    CalendarClock,
    StickyNote,
    Ruler,
    Weight,
    MapPin,
} from "lucide-vue-next";
import BedCard from "./BedCard.vue";
import type { Bed, BedForm } from "~/types/bed.js";
const { canUpdate } = usePermissions();
import { stringToDate } from "~/utils/time";

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

const initials = (first: string, last: string) => {
    return `${first?.[0] ?? ""}${last?.[0] ?? ""}`.toUpperCase();
};

const age = (dob?: string) => {
    if (!dob) return null;
    const birth = new Date(dob);
    if (Number.isNaN(birth.getTime())) return null;
    const diffMs = Date.now() - birth.getTime();
    const years = diffMs / (1000 * 60 * 60 * 24 * 365.25);
    return Math.floor(years);
};

const admissionStatusClasses = (status?: string) => {
    switch (status?.toLowerCase()) {
        case "admitted":
            return "bg-sky-100 text-sky-700";
        case "discharged":
            return "bg-gray-100 text-gray-500";
        case "pending":
            return "bg-amber-100 text-amber-700";
        default:
            return "bg-gray-100 text-gray-500";
    }
};

const expandedPatientId = ref<number | null>(null);

const toggleDetails = (patientId?: number) => {
    if (!patientId) return;
    expandedPatientId.value =
        expandedPatientId.value === patientId ? null : patientId;
};

const addingBedRoomId = ref<number | null>(null);
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
                    class="rounded-2xl border border-gray-100 border-l-4 border-l-gray-200 bg-white p-4 animate-pulse"
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
                    class="flex flex-col items-center justify-center py-14 text-center"
                >
                    <div
                        class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mb-3"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            class="w-8 h-8 text-gray-300"
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
                    <p class="text-sm font-semibold text-gray-600">
                        No rooms found
                    </p>
                    <p class="text-xs text-gray-400 mt-1 max-w-[240px]">
                        Try adjusting your search or filters, or add a new room.
                    </p>
                </div>

                <template v-else>
                    <div
                        v-for="room in rooms"
                        :key="room.room_id"
                        class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-lg"
                    >
                        <button
                            type="button"
                            @click="toggleRoom(room.room_id)"
                            class="w-full text-left hover:bg-gray-50/70 transition-colors p-4 flex items-center justify-between gap-3"
                        >
                            <div class="min-w-0 flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                    :class="
                                        room.room_type === 'VIP'
                                            ? 'bg-amber-50 text-amber-500'
                                            : 'bg-violet-50 text-violet-500'
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
                                            class="truncate text-lg font-semibold tracking-tight text-slate-900"
                                        >
                                            {{ room.room_no }}
                                        </h3>

                                        <span
                                            class="inline-flex items-center rounded-lg px-2.5 py-1 text-[11px] font-semibold"
                                            :class="
                                                room.room_type === 'VIP'
                                                    ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'
                                                    : 'bg-violet-50 text-violet-700 ring-1 ring-violet-200'
                                            "
                                        >
                                            {{ room.room_type }}
                                        </span>
                                    </div>

                                    <div
                                        class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-400"
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
                                                        b.current_admission
                                                            ?.patient,
                                                ).length
                                            }}
                                            / {{ capacityOf(room) }} occupied
                                        </span>

                                        <span
                                            v-if="
                                                room.beds.some(
                                                    (b) =>
                                                        !b.current_admission
                                                            ?.patient,
                                                )
                                            "
                                            class="flex items-center gap-1 font-medium text-emerald-500"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full bg-emerald-400"
                                            ></span>
                                            {{
                                                room.beds.filter(
                                                    (b) =>
                                                        !b.current_admission
                                                            ?.patient,
                                                ).length
                                            }}
                                            Available
                                        </span>

                                        <span
                                            v-if="remainingSlots(room).length"
                                            class="flex items-center uppercase gap-1 font-medium text-amber-500"
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
                                        'bg-red-400':
                                            room.status === 'Maintenance',

                                        'bg-blue-400':
                                            room.beds.length > 0 &&
                                            room.beds.every(
                                                (bed) =>
                                                    bed.current_admission
                                                        ?.patient,
                                            ),

                                        'bg-orange-400':
                                            room.beds.some(
                                                (bed) =>
                                                    bed.current_admission
                                                        ?.patient,
                                            ) &&
                                            !room.beds.every(
                                                (bed) =>
                                                    bed.current_admission
                                                        ?.patient,
                                            ),

                                        'bg-emerald-400':
                                            room.beds.length > 0 &&
                                            room.beds.every(
                                                (bed) =>
                                                    !bed.current_admission
                                                        ?.patient,
                                            ),
                                    }"
                                ></span>

                                <ChevronDown
                                    class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                    :class="{
                                        'rotate-180': isExpanded(room.room_id),
                                    }"
                                />
                            </div>
                        </button>

                        <div
                            v-if="isExpanded(room.room_id)"
                            class="px-4 pb-4 border-t border-gray-100 pt-4 bg-gray-50/40"
                        >
                            <div class="flex items-center justify-between mb-4">
                                <p class="text-xs text-gray-400 mb-3">
                                    Capacity: {{ capacityOf(room) }} bed{{
                                        capacityOf(room) > 1 ? "s" : ""
                                    }}
                                    ·
                                    {{
                                        room.beds.filter(
                                            (b) => b.current_admission?.patient,
                                        ).length
                                    }}
                                    assigned ·
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
                                        class="flex items-center gap-1.5 text-xs font-medium text-gray-600 border border-gray-200 rounded-lg px-3 py-1.5 bg-white hover:border-blue-300 hover:text-blue-600 transition-colors"
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
                                    class="rounded-xl p-3.5 bg-white transition-shadow hover:shadow-sm"
                                    :class="
                                        bed.current_admission?.patient
                                            ? 'border border-gray-100'
                                            : 'border border-dashed border-emerald-200 bg-emerald-50/20'
                                    "
                                >
                                    <div
                                        class="flex items-center justify-between mb-2.5"
                                    >
                                        <span
                                            class="flex items-center gap-1.5 text-sm font-semibold text-gray-700"
                                        >
                                            <BedSingle
                                                class="h-3.5 w-3.5 text-gray-400"
                                            />
                                            {{ bed.bed_no }}
                                        </span>

                                        <span
                                            class="flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                            :class="
                                                bed.current_admission?.patient
                                                    ? 'bg-sky-50 text-sky-600'
                                                    : 'bg-emerald-100 text-emerald-600'
                                            "
                                        >
                                            {{
                                                bed.current_admission?.patient
                                                    ? "Occupied"
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
                                        <template
                                            v-if="
                                                bed.current_admission?.patient
                                            "
                                        >
                                            <div class="space-y-2.5">
                                                <button
                                                    type="button"
                                                    class="w-full flex items-start gap-2.5 text-left"
                                                    @click="
                                                        toggleDetails(
                                                            bed
                                                                .current_admission
                                                                .patient
                                                                .patient_id,
                                                        )
                                                    "
                                                >
                                                    <div
                                                        class="w-9 h-9 rounded-full bg-sky-100 text-sky-600 flex items-center justify-center text-[11px] font-semibold shrink-0 ring-2 ring-white shadow-sm"
                                                    >
                                                        {{
                                                            initials(
                                                                bed
                                                                    .current_admission
                                                                    .patient
                                                                    .first_name,
                                                                bed
                                                                    .current_admission
                                                                    .patient
                                                                    .last_name,
                                                            )
                                                        }}
                                                    </div>

                                                    <div class="min-w-0 flex-1">
                                                        <p
                                                            class="text-xs font-semibold text-gray-800 truncate"
                                                        >
                                                            {{
                                                                bed
                                                                    .current_admission
                                                                    .patient
                                                                    .first_name
                                                            }}
                                                            {{
                                                                bed
                                                                    .current_admission
                                                                    .patient
                                                                    .last_name
                                                            }}
                                                        </p>

                                                        <div
                                                            class="flex flex-wrap items-center gap-x-1.5 gap-y-0.5 mt-0.5 text-[11px] text-gray-400"
                                                        >
                                                            <span
                                                                v-if="
                                                                    bed
                                                                        .current_admission
                                                                        .patient
                                                                        .gender
                                                                "
                                                                >{{
                                                                    bed
                                                                        .current_admission
                                                                        .patient
                                                                        .gender
                                                                }}</span
                                                            >
                                                            <span
                                                                v-if="
                                                                    age(
                                                                        bed
                                                                            .current_admission
                                                                            .patient
                                                                            .date_of_birth,
                                                                    )
                                                                "
                                                                >·
                                                                {{
                                                                    age(
                                                                        bed
                                                                            .current_admission
                                                                            .patient
                                                                            .date_of_birth,
                                                                    )
                                                                }}y</span
                                                            >
                                                            <span
                                                                v-if="
                                                                    bed
                                                                        .current_admission
                                                                        .patient
                                                                        .blood_type
                                                                "
                                                                class="inline-flex items-center gap-0.5 font-medium text-rose-500"
                                                            >
                                                                <Droplet
                                                                    class="h-3 w-3"
                                                                />{{
                                                                    bed
                                                                        .current_admission
                                                                        .patient
                                                                        .blood_type
                                                                }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <ChevronDown
                                                        class="h-3.5 w-3.5 text-gray-300 shrink-0 mt-1 transition-transform duration-200"
                                                        :class="{
                                                            'rotate-180':
                                                                expandedPatientId ===
                                                                bed
                                                                    .current_admission
                                                                    .patient
                                                                    .patient_id,
                                                        }"
                                                    />
                                                </button>

                                                <div
                                                    v-if="
                                                        expandedPatientId ===
                                                        bed.current_admission
                                                            .patient.patient_id
                                                    "
                                                    class="grid grid-cols-2 gap-x-2 gap-y-1.5 text-[11px] text-gray-500 rounded-lg border border-dashed border-gray-200 p-2"
                                                >
                                                    <div
                                                        v-if="
                                                            bed
                                                                .current_admission
                                                                .patient
                                                                .phone_number
                                                        "
                                                        class="flex items-center gap-1 truncate"
                                                    >
                                                        <Phone
                                                            class="h-3 w-3 text-gray-400 shrink-0"
                                                        />
                                                        {{
                                                            bed
                                                                .current_admission
                                                                .patient
                                                                .phone_number
                                                        }}
                                                    </div>

                                                    <div
                                                        v-if="
                                                            bed
                                                                .current_admission
                                                                .patient
                                                                .citizenship
                                                        "
                                                        class="flex items-center gap-1 truncate"
                                                    >
                                                        <Globe2
                                                            class="h-3 w-3 text-gray-400 shrink-0"
                                                        />
                                                        {{
                                                            bed
                                                                .current_admission
                                                                .patient
                                                                .citizenship
                                                        }}
                                                    </div>

                                                    <div
                                                        v-if="
                                                            bed
                                                                .current_admission
                                                                .patient
                                                                .phone_number
                                                        "
                                                        class="flex items-center gap-1 truncate"
                                                    >
                                                        <Phone
                                                            class="h-3 w-3 text-gray-400 shrink-0"
                                                        />
                                                        {{
                                                            bed
                                                                .current_admission
                                                                .patient
                                                                .phone_number
                                                        }}
                                                    </div>

                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <div
                                                            v-if="
                                                                bed
                                                                    .current_admission
                                                                    .patient
                                                                    .height
                                                            "
                                                            class="flex items-center gap-1 truncate"
                                                        >
                                                            <Ruler
                                                                class="h-3 w-3 text-gray-400 shrink-0"
                                                            />
                                                            {{
                                                                bed
                                                                    .current_admission
                                                                    .patient
                                                                    .height
                                                            }}
                                                            cm
                                                        </div>
                                                        <div
                                                            v-if="
                                                                bed
                                                                    .current_admission
                                                                    .patient
                                                                    .weight
                                                            "
                                                            class="flex items-center gap-1 truncate"
                                                        >
                                                            <Weight
                                                                class="h-3 w-3 text-gray-400 shrink-0"
                                                            />
                                                            {{
                                                                bed
                                                                    .current_admission
                                                                    .patient
                                                                    .weight
                                                            }}
                                                            kg
                                                        </div>
                                                    </div>
                                                </div>

                                                <div
                                                    class="flex items-center justify-between text-[11px] pt-1.5 border-t border-gray-100"
                                                >
                                                    <span
                                                        class="flex items-center gap-1 text-gray-400"
                                                    >
                                                        <CalendarClock
                                                            class="h-3 w-3"
                                                        />

                                                        <span
                                                            class="font-medium text-gray-500"
                                                        >
                                                            Admission Period:
                                                        </span>

                                                        <span>
                                                            {{
                                                                stringToDate(
                                                                    bed
                                                                        .current_admission
                                                                        .admitted_at,
                                                                )
                                                            }}

                                                            <template
                                                                v-if="
                                                                    bed
                                                                        .current_admission
                                                                        .end_date
                                                                "
                                                            >
                                                                →
                                                                {{
                                                                    stringToDate(
                                                                        bed
                                                                            .current_admission
                                                                            .end_date,
                                                                    )
                                                                }}
                                                            </template>
                                                        </span>
                                                    </span>
                                                </div>

                                                <button
                                                    type="button"
                                                    @click="
                                                        editingBedId =
                                                            bed.bed_id
                                                    "
                                                    class="w-full flex items-center justify-center gap-1.5 text-xs font-medium text-blue-600 bg-white border border-blue-200 rounded-lg py-2 hover:bg-blue-50 transition-colors"
                                                >
                                                    <Pencil
                                                        class="h-3.5 w-3.5"
                                                    />
                                                    Update Bed
                                                </button>
                                            </div>
                                        </template>

                                        <template v-else>
                                            <button
                                                type="button"
                                                class="w-full flex items-center justify-center gap-1.5 text-xs font-medium text-violet-600 bg-white border border-violet-200 rounded-lg py-2 mt-1 hover:bg-violet-50 transition-colors"
                                            >
                                                <UserPlus class="h-3.5 w-3.5" />
                                                Assign Patient
                                            </button>

                                            <button
                                                type="button"
                                                @click="
                                                    editingBedId = bed.bed_id
                                                "
                                                class="w-full flex items-center justify-center gap-1.5 text-xs font-medium text-blue-600 bg-white border border-blue-200 rounded-lg py-2 mt-1 hover:bg-blue-100 transition-colors"
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
                                        class="group flex min-h-[140px] flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-gradient-to-br from-white to-slate-50 p-5 transition-all duration-300 hover:-translate-y-1 hover:border-primary hover:shadow-lg"
                                    >
                                        <div
                                            class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 transition-all duration-300 group-hover:scale-110 group-hover:bg-primary group-hover:text-white"
                                        >
                                            <Plus class="h-7 w-7" />
                                        </div>

                                        <h4
                                            class="text-sm font-semibold text-slate-700"
                                        >
                                            Add New Bed
                                        </h4>

                                        <p
                                            class="mt-1 text-center text-xs text-slate-500"
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
