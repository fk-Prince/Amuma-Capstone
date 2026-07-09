<script setup lang="ts">
import { ref, reactive, computed, watch } from "vue";

/* Mga types                                                            */

type RoomType = "VIP" | "COMMON";
type RoomStatus = "available" | "partial" | "occupied" | "maintenance";
type RoomStatusOverride = "auto" | "maintenance";

interface Resident {
    id: string;
    name: string;
    age: number;
    admittedDate: string;
    careType: string;
    caregiver: string;
}

interface Bed {
    id: string;
    label: string;
    resident?: Resident;
    description?: string;
}

interface Room {
    id: string;
    name: string;
    floor: number;
    type: RoomType;
    status?: "maintenance";
    notes?: string;
    beds: Bed[];
}

interface Facility {
    name: string;
    location: string;
}

interface Branch {
    id: string;
    name: string;
    location: string;
    isMain?: boolean;
}

const props = defineProps<{
    facility?: Facility;
    rooms?: Room[];
    branches?: Branch[];
    activeBranchId?: string;
}>();

const emit = defineEmits<{
    (
        e: "edit-room",
        payload: {
            id: string;
            name: string;
            floor: number;
            type: RoomType;
            notes: string;
            capacity: number;
            status: RoomStatusOverride;
        },
    ): void;
    (e: "delete-room", roomId: string): void;
    (
        e: "assign-bed",
        payload: {
            roomId: string;
            bedId: string;
            resident: Omit<Resident, "id">;
        },
    ): void;
    (
        e: "update-resident",
        payload: { roomId: string; bedId: string; resident: Resident },
    ): void;
    (e: "unassign-bed", payload: { roomId: string; bedId: string }): void;
    (e: "view-resident-profile", residentId: string): void;
    (e: "switch-branch", branchId: string): void;
}>();

const defaultFacility: Facility = {
    name: "Davao Veil Geriatric Home Care Center",
    location: "Mandug, Buhangin, Davao City",
};

const facility = computed(() => props.facility ?? defaultFacility);

/* Branch switcher                                                     */
/* mo-gawas ang switcher dropdown para sa mga multi-branch owner.  pero depende saimo if dili
anaon HAHHAHAHAHHAHAHHAHA    */

const branches = computed<Branch[]>(() =>
    props.branches && props.branches.length
        ? props.branches
        : [
              {
                  id: "main",
                  name: facility.value.name,
                  location: facility.value.location,
                  isMain: true,
              },
          ],
);

const activeBranchId = ref(props.activeBranchId ?? branches.value[0]?.id);
watch(
    () => props.activeBranchId,
    (val) => {
        if (val) activeBranchId.value = val;
    },
);

const activeBranch = computed(
    () =>
        branches.value.find((b) => b.id === activeBranchId.value) ??
        branches.value[0],
);

const branchMenuOpen = ref(false);
function selectBranch(id: string) {
    activeBranchId.value = id;
    branchMenuOpen.value = false;
    emit("switch-branch", id);
}

/* Mock data */

function defaultRooms(): Room[] {
    return [
        {
            id: "vip-001",
            name: "VIP 001",
            floor: 1,
            type: "VIP",
            beds: [
                {
                    id: "vip-001-a1",
                    label: "Bed A1",
                    description:
                        "Premium private suite with garden view, en-suite bathroom, and dedicated caregiver.",
                    resident: {
                        id: "res-vicenta",
                        name: "Vicenta Libunao",
                        age: 92,
                        admittedDate: "Jun 1, 2026",
                        careType: "Long-term premium care",
                        caregiver: "Ante Kier",
                    },
                },
            ],
        },
        {
            id: "vip-010",
            name: "VIP 010",
            floor: 3,
            type: "VIP",
            beds: [
                {
                    id: "vip-010-a1",
                    label: "Bed A1",
                    description:
                        "Premium private suite with balcony access and en-suite bathroom.",
                    resident: {
                        id: "res-rogelio",
                        name: "Rogelio Sarmiento",
                        age: 88,
                        admittedDate: "Apr 14, 2026",
                        careType: "Long-term premium care",
                        caregiver: "Marisol Dungca",
                    },
                },
            ],
        },
        {
            id: "common-001",
            name: "COMMON 001",
            floor: 0,
            type: "COMMON",
            beds: makeCommonBeds(6, 0),
        },
        {
            id: "common-005",
            name: "COMMON 005",
            floor: 2,
            type: "COMMON",
            beds: makeCommonBeds(6, 3),
        },
        {
            id: "common-010",
            name: "COMMON 010",
            floor: 3,
            type: "COMMON",
            beds: makeCommonBeds(6, 0),
        },
    ];
}

function makeCommonBeds(total: number, occupied: number): Bed[] {
    const letters = ["A", "B", "C", "D", "E", "F"];
    return Array.from({ length: total }, (_, i) => {
        const id = `bed-${letters[i]}`;
        if (i < occupied) {
            return {
                id,
                label: `Bed ${letters[i]}`,
                description:
                    "Shared common room bed with communal dining and activity access.",
                resident: {
                    id: `res-${id}`,
                    name: "Resident " + letters[i],
                    age: 70 + i,
                    admittedDate: "May " + (i + 1) + ", 2026",
                    careType: "Standard care",
                    caregiver: "Staff Caregiver",
                },
            };
        }
        return {
            id,
            label: `Bed ${letters[i]}`,
            description:
                "Shared common room bed with communal dining and activity access.",
        };
    });
}

const localRooms = ref<Room[]>(
    props.rooms ? structuredClone(props.rooms) : defaultRooms(),
);
watch(
    () => props.rooms,
    (val) => {
        if (val) localRooms.value = structuredClone(val);
    },
);

/* Mga stats nga gikan sa rooms data                                   */

const allBeds = computed(() => localRooms.value.flatMap((r) => r.beds));
const totalRooms = computed(() => localRooms.value.length);
const occupiedBeds = computed(
    () => allBeds.value.filter((b) => !!b.resident).length,
);
const availableBeds = computed(() => allBeds.value.length - occupiedBeds.value);
const maintenanceRoomsCount = computed(
    () => localRooms.value.filter((r) => r.status === "maintenance").length,
);

/* Filter + search + expand state                                      */

type TabId = "all" | "vip" | "common";
const activeTab = ref<TabId>("all");
const tabs: { id: TabId; label: string }[] = [
    { id: "all", label: "All Rooms" },
    { id: "vip", label: "VIP Rooms" },
    { id: "common", label: "Common Rooms" },
];

const search = ref("");

const filteredRooms = computed(() => {
    return localRooms.value.filter((room) => {
        if (activeTab.value === "vip" && room.type !== "VIP") return false;
        if (activeTab.value === "common" && room.type !== "COMMON")
            return false;
        if (search.value.trim()) {
            const q = search.value.toLowerCase();
            const matchesRoom = room.name.toLowerCase().includes(q);
            const matchesResident = room.beds.some((b) =>
                b.resident?.name.toLowerCase().includes(q),
            );
            if (!matchesRoom && !matchesResident) return false;
        }
        return true;
    });
});

function roomStatus(room: Room): RoomStatus {
    if (room.status === "maintenance") return "maintenance";
    const occ = room.beds.filter((b) => !!b.resident).length;
    if (occ === 0) return "available";
    if (occ === room.beds.length) return "occupied";
    return "partial";
}

const statusStyles: Record<
    RoomStatus,
    { dot: string; text: string; label: string }
> = {
    available: {
        dot: "bg-emerald-500",
        text: "text-emerald-600",
        label: "Available",
    },
    partial: {
        dot: "bg-orange-500",
        text: "text-orange-600",
        label: "Partially Occupied",
    },
    occupied: { dot: "bg-sky-500", text: "text-sky-600", label: "Occupied" },
    maintenance: {
        dot: "bg-rose-500",
        text: "text-rose-600",
        label: "Maintenance",
    },
};

const typeStyles: Record<
    RoomType,
    { border: string; text: string; label: string }
> = {
    VIP: { border: "border-l-amber-400", text: "text-amber-500", label: "VIP" },
    COMMON: {
        border: "border-l-violet-500",
        text: "text-violet-500",
        label: "COMMON",
    },
};

/*drop down beds*/

const expandedRoomIds = ref<Set<string>>(new Set());

function toggleRoom(id: string) {
    const next = new Set(expandedRoomIds.value);
    next.has(id) ? next.delete(id) : next.add(id);
    expandedRoomIds.value = next;
}

/* Edit room modal*/

const editModalOpen = ref(false);
const editForm = reactive({
    id: "",
    name: "",
    floor: 0,
    capacity: 1,
    type: "VIP" as RoomType,
    status: "auto" as RoomStatusOverride,
    notes: "",
});

function openEditRoom(room: Room) {
    editForm.id = room.id;
    editForm.name = room.name;
    editForm.floor = room.floor;
    editForm.capacity = room.beds.length;
    editForm.type = room.type;
    editForm.status = room.status === "maintenance" ? "maintenance" : "auto";
    editForm.notes = room.notes ?? "";
    editModalOpen.value = true;
}

function submitEditRoom() {
    const room = localRooms.value.find((r) => r.id === editForm.id);
    if (room) {
        room.name = editForm.name;
        room.floor = editForm.floor;
        room.type = editForm.type;
        room.notes = editForm.notes;
        room.status =
            editForm.status === "maintenance" ? "maintenance" : undefined;

        const diff = editForm.capacity - room.beds.length;
        if (diff > 0) {
            const letters = "ABCDEFGHIJKLMNOP".split("");
            for (let i = 0; i < diff; i++) {
                const nextIndex = room.beds.length;
                room.beds.push({
                    id: `${room.id}-bed-${nextIndex}`,
                    label: `Bed ${letters[nextIndex] ?? nextIndex + 1}`,
                });
            }
        } else if (diff < 0) {
            for (let i = 0; i < -diff; i++) {
                const reversedIdx = [...room.beds]
                    .reverse()
                    .findIndex((b) => !b.resident);
                if (reversedIdx === -1) break;
                room.beds.splice(room.beds.length - 1 - reversedIdx, 1);
            }
        }
    }
    emit("edit-room", {
        id: editForm.id,
        name: editForm.name,
        floor: editForm.floor,
        type: editForm.type,
        notes: editForm.notes,
        capacity: editForm.capacity,
        status: editForm.status,
    });
    editModalOpen.value = false;
}

/* Delete room modal*/

const deleteModalOpen = ref(false);
const roomPendingDelete = ref<Room | null>(null);

function openDeleteRoom(room: Room) {
    roomPendingDelete.value = room;
    deleteModalOpen.value = true;
}

function confirmDeleteRoom() {
    if (!roomPendingDelete.value) return;
    const id = roomPendingDelete.value.id;
    localRooms.value = localRooms.value.filter((r) => r.id !== id);
    emit("delete-room", id);
    deleteModalOpen.value = false;
    roomPendingDelete.value = null;

    if (expandedRoomIds.value.has(id)) {
        const next = new Set(expandedRoomIds.value);
        next.delete(id);
        expandedRoomIds.value = next;
    }
}

/* Assign / edit resident modal*/

const residentModalOpen = ref(false);
const residentModalMode = ref<"assign" | "edit">("assign");
const residentTarget = ref<{ roomId: string; bedId: string } | null>(null);
const residentForm = reactive({
    id: "",
    name: "",
    age: 70,
    admittedDate: "",
    careType: "",
    caregiver: "",
});

function today() {
    return new Date().toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
}

function openAssignBed(roomId: string, bedId: string) {
    residentModalMode.value = "assign";
    residentTarget.value = { roomId, bedId };
    Object.assign(residentForm, {
        id: "",
        name: "",
        age: 70,
        admittedDate: today(),
        careType: "",
        caregiver: "",
    });
    residentModalOpen.value = true;
}

function openEditResident(roomId: string, bedId: string, resident: Resident) {
    residentModalMode.value = "edit";
    residentTarget.value = { roomId, bedId };
    Object.assign(residentForm, resident);
    residentModalOpen.value = true;
}

function submitResidentForm() {
    if (!residentTarget.value) return;
    const { roomId, bedId } = residentTarget.value;
    const room = localRooms.value.find((r) => r.id === roomId);
    const bed = room?.beds.find((b) => b.id === bedId);
    if (!room || !bed) return;

    if (residentModalMode.value === "assign") {
        const newResident: Resident = {
            id: `res-${Date.now()}`,
            name: residentForm.name,
            age: residentForm.age,
            admittedDate: residentForm.admittedDate,
            careType: residentForm.careType,
            caregiver: residentForm.caregiver,
        };
        bed.resident = newResident;
        emit("assign-bed", {
            roomId,
            bedId,
            resident: {
                name: newResident.name,
                age: newResident.age,
                admittedDate: newResident.admittedDate,
                careType: newResident.careType,
                caregiver: newResident.caregiver,
            },
        });
    } else {
        const updated: Resident = {
            id: residentForm.id,
            name: residentForm.name,
            age: residentForm.age,
            admittedDate: residentForm.admittedDate,
            careType: residentForm.careType,
            caregiver: residentForm.caregiver,
        };
        bed.resident = updated;
        emit("update-resident", { roomId, bedId, resident: updated });
    }
    residentModalOpen.value = false;
}

const removeModalOpen = ref(false);
const removeTarget = ref<{
    roomId: string;
    bedId: string;
    residentName: string;
    bedLabel: string;
} | null>(null);

function openRemoveConfirm(
    roomId: string,
    bedId: string,
    residentName: string,
    bedLabel: string,
) {
    removeTarget.value = { roomId, bedId, residentName, bedLabel };
    removeModalOpen.value = true;
}

function confirmRemoveResident() {
    if (!removeTarget.value) return;
    const { roomId, bedId } = removeTarget.value;
    const room = localRooms.value.find((r) => r.id === roomId);
    const bed = room?.beds.find((b) => b.id === bedId);
    if (bed) bed.resident = undefined;
    emit("unassign-bed", { roomId, bedId });
    removeModalOpen.value = false;
    removeTarget.value = null;
}

function viewResidentProfile(residentId: string) {
    emit("view-resident-profile", residentId);
}
</script>

<template>
    <div class="w-full max-w-5xl mx-auto p-4 md:p-6 space-y-5 font-outfit">
        <!-- Mga stat cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div
                class="rounded-2xl border border-blue-200 border-t-4 border-t-blue-500 bg-white p-4 shadow-sm"
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
                <p class="text-2xl font-bold text-gray-800">{{ totalRooms }}</p>
                <p
                    class="mt-1 text-xs text-emerald-600 flex items-center gap-1"
                >
                    <span>↑ 3</span
                    ><span class="text-gray-400 font-normal"
                        >new this month</span
                    >
                </p>
            </div>

            <div
                class="rounded-2xl border border-emerald-200 border-t-4 border-t-emerald-500 bg-white p-4 shadow-sm"
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
                    {{ availableBeds }}
                </p>
                <p
                    class="mt-1 text-xs text-emerald-600 flex items-center gap-1"
                >
                    <span>↑ 15%</span
                    ><span class="text-gray-400 font-normal"
                        >of available rooms</span
                    >
                </p>
            </div>

            <div
                class="rounded-2xl border border-violet-200 border-t-4 border-t-violet-500 bg-white p-4 shadow-sm"
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
                    {{ occupiedBeds }}
                </p>
                <p
                    class="mt-1 text-xs text-emerald-600 flex items-center gap-1"
                >
                    <span>↑ 85%</span
                    ><span class="text-gray-400 font-normal"
                        >of occupied rooms</span
                    >
                </p>
            </div>

            <div
                class="rounded-2xl border border-rose-200 border-t-4 border-t-rose-500 bg-white p-4 shadow-sm"
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
                    {{ maintenanceRoomsCount }}
                </p>
                <p class="mt-1 text-xs text-rose-500 font-medium">
                    Requires Attention
                </p>
            </div>
        </div>

        <!-- Search box -->
        <div class="relative">
            <svg
                viewBox="0 0 24 24"
                class="w-4 h-4 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <circle cx="11" cy="11" r="7" />
                <path d="M21 21l-4.35-4.35" stroke-linecap="round" />
            </svg>
            <input
                v-model="search"
                type="text"
                placeholder="Search rooms, residents..."
                class="w-full rounded-full border border-gray-200 bg-white pl-11 pr-4 py-2.5 text-sm text-gray-700 placeholder:text-gray-400 transition-colors hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400"
            />
        </div>

        <!-- Facility card -->
        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-5">
            <!-- Header sa facility -->
            <div
                class="flex items-center justify-between gap-3 pb-4 border-b border-gray-100"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <div
                        class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center shrink-0"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            class="w-5 h-5 text-blue-600"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"
                            />
                            <path d="M10 6h4M10 10h4M10 14h4M10 18h4" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2
                                class="font-semibold text-gray-800 leading-tight truncate"
                            >
                                {{ activeBranch?.name }}
                            </h2>
                            <span
                                class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 border border-blue-100 shrink-0"
                            >
                                {{
                                    activeBranch?.isMain
                                        ? "Main Branch"
                                        : "Branch"
                                }}
                            </span>
                        </div>
                        <p
                            class="text-xs text-gray-400 flex items-center gap-1 mt-0.5"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                class="w-3.5 h-3.5 shrink-0"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    d="M12 21s-7-6.5-7-11a7 7 0 1 1 14 0c0 4.5-7 11-7 11z"
                                />
                                <circle cx="12" cy="10" r="2.5" />
                            </svg>
                            <span class="truncate">{{
                                activeBranch?.location
                            }}</span>
                        </p>
                    </div>
                </div>

                <!-- Branch switcher: mo-gawas ra ni kung naay 2+ branch ang owner -->
                <div v-if="branches.length > 1" class="relative shrink-0">
                    <button
                        type="button"
                        @click="branchMenuOpen = !branchMenuOpen"
                        class="flex items-center gap-1.5 text-xs font-medium text-gray-600 border border-gray-200 rounded-lg px-3 py-1.5 bg-white hover:border-blue-300 hover:text-blue-600 transition-colors"
                    >
                        Switch Branch
                        <svg
                            viewBox="0 0 24 24"
                            class="w-3.5 h-3.5 transition-transform duration-200"
                            :class="{ 'rotate-180': branchMenuOpen }"
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
                    </button>
                    <div
                        v-if="branchMenuOpen"
                        class="absolute right-0 mt-2 w-56 rounded-xl border border-gray-100 bg-white shadow-lg py-1.5 z-20"
                    >
                        <button
                            v-for="b in branches"
                            :key="b.id"
                            type="button"
                            @click="selectBranch(b.id)"
                            class="w-full text-left px-3.5 py-2 text-xs hover:bg-gray-50 flex items-center justify-between gap-2"
                            :class="
                                b.id === activeBranch?.id
                                    ? 'text-blue-600 font-medium'
                                    : 'text-gray-600'
                            "
                        >
                            <span class="truncate">{{ b.name }}</span>
                            <span
                                v-if="b.isMain"
                                class="text-[9px] text-gray-400 shrink-0"
                                >Main</span
                            >
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabs ug legend -->
            <div class="flex flex-wrap items-center justify-between gap-3 py-4">
                <div
                    class="flex items-center gap-1 bg-gray-50 rounded-full p-1"
                >
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        class="px-3.5 py-1.5 rounded-full text-xs font-medium transition-colors"
                        :class="
                            activeTab === tab.id
                                ? 'bg-white shadow text-gray-800'
                                : 'text-gray-400 hover:text-gray-600'
                        "
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <div
                    class="hidden lg:flex items-center gap-3 text-xs text-gray-500"
                >
                    <span class="flex items-center gap-1.5"
                        ><span
                            class="w-2 h-2 rounded-full bg-emerald-500"
                        ></span
                        >Available</span
                    >
                    <span class="flex items-center gap-1.5"
                        ><span class="w-2 h-2 rounded-full bg-orange-500"></span
                        >Partial</span
                    >
                    <span class="flex items-center gap-1.5"
                        ><span class="w-2 h-2 rounded-full bg-sky-500"></span
                        >Occupied</span
                    >
                    <span class="flex items-center gap-1.5"
                        ><span class="w-2 h-2 rounded-full bg-rose-500"></span
                        >Maintenance</span
                    >
                </div>
            </div>

            <!-- Listahan sa rooms -->
            <div class="max-h-560px overflow-y-auto pr-1 space-y-3 scroll-thin">
                <p
                    v-if="filteredRooms.length === 0"
                    class="text-sm text-gray-400 text-center py-10"
                >
                    No rooms match your search.
                </p>

                <div
                    v-for="room in filteredRooms"
                    :key="room.id"
                    class="rounded-xl border border-gray-100 border-l-4 bg-gray-50/60 overflow-hidden"
                    :class="typeStyles[room.type].border"
                >
                    <!-- Row header: pag-click, mo-drop down ang beds sa iyaha nga lugar -->
                    <button
                        type="button"
                        @click="toggleRoom(room.id)"
                        class="w-full text-left hover:bg-gray-100/60 transition-colors p-4 flex items-center justify-between gap-3"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-gray-800">
                                    {{ room.name }}
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
                                        v-if="room.type === 'VIP'"
                                        viewBox="0 0 24 24"
                                        class="w-3.5 h-3.5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path
                                            d="M2.5 19h19l-1.7-9.3-4.8 4-3-6.7-3 6.7-4.8-4Z"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        viewBox="0 0 24 24"
                                        class="w-3.5 h-3.5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path
                                            d="M3 21h18M5 21V7l7-4 7 4v14"
                                            stroke-linejoin="round"
                                        />
                                        <path
                                            d="M9 9h.01M9 13h.01M14 9h.01M14 13h.01"
                                        />
                                    </svg>
                                    {{ typeStyles[room.type].label }}
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
                                        room.beds.filter((b) => b.resident)
                                            .length
                                    }}/{{ room.beds.length }} Occupied
                                </span>
                                <template
                                    v-if="
                                        room.beds.length -
                                            room.beds.filter((b) => b.resident)
                                                .length >
                                        0
                                    "
                                >
                                    <span class="text-gray-200">|</span>
                                    <span class="text-emerald-500 font-medium">
                                        {{
                                            room.beds.length -
                                            room.beds.filter((b) => b.resident)
                                                .length
                                        }}
                                        Available
                                    </span>
                                </template>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 shrink-0">
                            <span
                                class="w-2.5 h-2.5 rounded-full shrink-0"
                                :class="statusStyles[roomStatus(room)].dot"
                                :title="statusStyles[roomStatus(room)].label"
                            ></span>
                            <svg
                                viewBox="0 0 24 24"
                                class="w-4 h-4 text-gray-400 shrink-0 transition-transform duration-200"
                                :class="{
                                    'rotate-180': expandedRoomIds.has(room.id),
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

                    <!-- Ang mo-drop down: beds + resident info, diretso ilawom sa row -->
                    <div
                        v-if="expandedRoomIds.has(room.id)"
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
                            <button
                                type="button"
                                @click.stop="openDeleteRoom(room)"
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
                            {{ room.beds.filter((b) => b.resident).length }}
                            assigned
                            <span v-if="room.notes"> · {{ room.notes }}</span>
                        </p>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div
                                v-for="bed in room.beds"
                                :key="bed.id"
                                class="rounded-lg p-3 bg-white"
                                :class="
                                    bed.resident
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
                                        {{ bed.label }}
                                    </span>
                                    <span
                                        class="text-[10px] font-medium px-2 py-0.5 rounded-full"
                                        :class="
                                            bed.resident
                                                ? 'bg-sky-100 text-sky-600'
                                                : 'bg-emerald-100 text-emerald-600'
                                        "
                                    >
                                        {{
                                            bed.resident
                                                ? "Occupied"
                                                : "Available"
                                        }}
                                    </span>
                                </div>

                                <p
                                    v-if="bed.description"
                                    class="text-xs text-gray-400 mb-2 leading-relaxed"
                                >
                                    {{ bed.description }}
                                </p>

                                <template v-if="bed.resident">
                                    <div
                                        class="space-y-1 text-xs text-gray-600"
                                    >
                                        <p class="flex items-center gap-1.5">
                                            <svg
                                                viewBox="0 0 24 24"
                                                class="w-3 h-3 text-gray-400"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <circle cx="12" cy="8" r="4" />
                                                <path
                                                    d="M4 21c0-4 4-6 8-6s8 2 8 6"
                                                    stroke-linecap="round"
                                                />
                                            </svg>
                                            {{ bed.resident.name }},
                                            {{ bed.resident.age }} yrs
                                        </p>
                                        <p class="flex items-center gap-1.5">
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
                                            {{ bed.resident.admittedDate }}
                                        </p>
                                        <p class="flex items-center gap-1.5">
                                            <svg
                                                viewBox="0 0 24 24"
                                                class="w-3 h-3 text-gray-400"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path
                                                    d="M12 21s-7-4.35-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 6c-2.5 4.65-9.5 9-9.5 9z"
                                                    stroke-linejoin="round"
                                                />
                                            </svg>
                                            {{ bed.resident.careType }}
                                        </p>
                                        <p class="flex items-center gap-1.5">
                                            <svg
                                                viewBox="0 0 24 24"
                                                class="w-3 h-3 text-gray-400"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <circle cx="9" cy="8" r="3" />
                                                <path
                                                    d="M2 20c0-3.3 3.1-5 7-5s7 1.7 7 5M17 8a3 3 0 1 1 0 6M23 20c0-2.6-2-4-4.5-4.7"
                                                    stroke-linecap="round"
                                                />
                                            </svg>
                                            {{ bed.resident.caregiver }}
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        @click.stop="
                                            viewResidentProfile(bed.resident.id)
                                        "
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
                                        <button
                                            type="button"
                                            @click.stop="
                                                openEditResident(
                                                    room.id,
                                                    bed.id,
                                                    bed.resident,
                                                )
                                            "
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
                                        <button
                                            type="button"
                                            @click.stop="
                                                openRemoveConfirm(
                                                    room.id,
                                                    bed.id,
                                                    bed.resident.name,
                                                    bed.label,
                                                )
                                            "
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

                                <button
                                    v-else
                                    type="button"
                                    @click.stop="openAssignBed(room.id, bed.id)"
                                    class="w-full text-xs font-medium text-violet-600 border border-violet-200 rounded-lg py-1.5 mt-1 hover:bg-violet-50 transition-colors"
                                >
                                    Assign Resident
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Room Modal -->
        <!-- Teleported pud ni: puwede siya i-open gikan sulod sa room list, so   -->
        <!-- kinahanglan ni siya og teleport para tarong ang render sa ibabaw.     -->
        <Teleport to="body">
            <div
                v-if="editModalOpen"
                class="fixed inset-0 z-60 flex items-center justify-center bg-black/40 p-4"
                @click.self="editModalOpen = false"
            >
                <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Edit Room
                        </h3>
                        <button
                            type="button"
                            @click="editModalOpen = false"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    d="M18 6L6 18M6 6l12 12"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </button>
                    </div>
                    <form class="space-y-4" @submit.prevent="submitEditRoom">
                        <div>
                            <label
                                class="block text-xs font-medium text-gray-500 mb-1"
                                >Room Name / Number</label
                            >
                            <input
                                v-model="editForm.name"
                                type="text"
                                required
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-500 mb-1"
                                    >Floor</label
                                >
                                <input
                                    v-model.number="editForm.floor"
                                    type="number"
                                    min="0"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-500 mb-1"
                                    >Capacity</label
                                >
                                <input
                                    v-model.number="editForm.capacity"
                                    type="number"
                                    min="1"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400"
                                />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-500 mb-1"
                                    >Room Type</label
                                >
                                <select
                                    v-model="editForm.type"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400"
                                >
                                    <option value="VIP">VIP</option>
                                    <option value="COMMON">Common</option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-500 mb-1"
                                    >Status</label
                                >
                                <select
                                    v-model="editForm.status"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400"
                                >
                                    <option value="auto">
                                        Auto (by occupancy)
                                    </option>
                                    <option value="maintenance">
                                        Under Maintenance
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label
                                class="block text-xs font-medium text-gray-500 mb-1"
                                >Notes</label
                            >
                            <textarea
                                v-model="editForm.notes"
                                rows="3"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400"
                            ></textarea>
                        </div>
                        <button
                            type="submit"
                            class="w-full rounded-lg bg-blue-500 text-white text-sm font-medium py-2.5 hover:bg-blue-600 transition-colors"
                        >
                            Update Room
                        </button>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Delete Room Confirmation Modal -->
        <Teleport to="body">
            <div
                v-if="deleteModalOpen"
                class="fixed inset-0 z-60 flex items-center justify-center bg-black/40 p-4"
                @click.self="deleteModalOpen = false"
            >
                <div
                    class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl text-center"
                >
                    <div
                        class="w-12 h-12 rounded-full bg-rose-100 flex items-center justify-center mx-auto mb-3"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            class="w-6 h-6 text-rose-500"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                d="M12 9v4M12 17h.01"
                                stroke-linecap="round"
                            />
                            <path
                                d="M10.29 3.86l-8.18 14.18A1 1 0 0 0 3 19.5h18a1 1 0 0 0 .89-1.46L13.71 3.86a1 1 0 0 0-1.72 0z"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-800 mb-1">
                        Delete {{ roomPendingDelete?.name }}?
                    </h3>
                    <p class="text-xs text-gray-500 mb-5">
                        This permanently removes the room and unassigns
                        {{
                            roomPendingDelete?.beds.filter((b) => b.resident)
                                .length || 0
                        }}
                        resident(s). This cannot be undone.
                    </p>
                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="deleteModalOpen = false"
                            class="flex-1 rounded-lg border border-gray-200 text-gray-600 text-sm font-medium py-2.5 hover:bg-gray-50 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="confirmDeleteRoom"
                            class="flex-1 rounded-lg bg-rose-500 text-white text-sm font-medium py-2.5 hover:bg-rose-600 transition-colors"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Assign / Edit Resident Modal -->
        <Teleport to="body">
            <div
                v-if="residentModalOpen"
                class="fixed inset-0 z-60 flex items-center justify-center bg-black/40 p-4"
                @click.self="residentModalOpen = false"
            >
                <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            {{
                                residentModalMode === "assign"
                                    ? "Assign Resident"
                                    : "Edit Resident"
                            }}
                        </h3>
                        <button
                            type="button"
                            @click="residentModalOpen = false"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    d="M18 6L6 18M6 6l12 12"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </button>
                    </div>
                    <form
                        class="space-y-4"
                        @submit.prevent="submitResidentForm"
                    >
                        <div>
                            <label
                                class="block text-xs font-medium text-gray-500 mb-1"
                                >Full Name</label
                            >
                            <input
                                v-model="residentForm.name"
                                type="text"
                                required
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-500 mb-1"
                                    >Age</label
                                >
                                <input
                                    v-model.number="residentForm.age"
                                    type="number"
                                    min="0"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-500 mb-1"
                                    >Admitted Date</label
                                >
                                <input
                                    v-model="residentForm.admittedDate"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400"
                                />
                            </div>
                        </div>
                        <div>
                            <label
                                class="block text-xs font-medium text-gray-500 mb-1"
                                >Care Type</label
                            >
                            <input
                                v-model="residentForm.careType"
                                type="text"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-xs font-medium text-gray-500 mb-1"
                                >Assigned Caregiver</label
                            >
                            <input
                                v-model="residentForm.caregiver"
                                type="text"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400"
                            />
                        </div>
                        <button
                            type="submit"
                            class="w-full rounded-lg bg-violet-500 text-white text-sm font-medium py-2.5 hover:bg-violet-600 transition-colors"
                        >
                            {{
                                residentModalMode === "assign"
                                    ? "Assign Resident"
                                    : "Save Changes"
                            }}
                        </button>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Remove Resident Confirmation Modal -->
        <Teleport to="body">
            <div
                v-if="removeModalOpen"
                class="fixed inset-0 z-60 flex items-center justify-center bg-black/40 p-4"
                @click.self="removeModalOpen = false"
            >
                <div
                    class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl text-center"
                >
                    <div
                        class="w-12 h-12 rounded-full bg-rose-100 flex items-center justify-center mx-auto mb-3"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            class="w-6 h-6 text-rose-500"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                d="M12 9v4M12 17h.01"
                                stroke-linecap="round"
                            />
                            <path
                                d="M10.29 3.86l-8.18 14.18A1 1 0 0 0 3 19.5h18a1 1 0 0 0 .89-1.46L13.71 3.86a1 1 0 0 0-1.72 0z"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-800 mb-1">
                        Remove {{ removeTarget?.residentName }}?
                    </h3>
                    <p class="text-xs text-gray-500 mb-5">
                        This unassigns {{ removeTarget?.residentName }} from
                        {{ removeTarget?.bedLabel }}, freeing it up for a new
                        resident. This cannot be undone.
                    </p>
                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="removeModalOpen = false"
                            class="flex-1 rounded-lg border border-gray-200 text-gray-600 text-sm font-medium py-2.5 hover:bg-gray-50 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="confirmRemoveResident"
                            class="flex-1 rounded-lg bg-rose-500 text-white text-sm font-medium py-2.5 hover:bg-rose-600 transition-colors"
                        >
                            Confirm
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap");

.font-outfit {
    font-family: "Outfit", sans-serif;
}

.scroll-thin::-webkit-scrollbar {
    width: 6px;
}
.scroll-thin::-webkit-scrollbar-thumb {
    background-color: #e5e7eb;
    border-radius: 9999px;
}
.scroll-thin::-webkit-scrollbar-track {
    background: transparent;
}
</style>
