<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[70] flex items-center justify-center bg-black/40 p-5"
            >
                <div
                    class="w-full max-w-2xl max-h-[88vh] overflow-y-auto rounded-2xl bg-white p-6 md:p-7 shadow-xl"
                >
                    <h3 class="text-lg font-semibold">Change Room / Bed</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Move this patient to a different room or bed within
                        {{
                            currentType ? currentType.toLowerCase() : "the same"
                        }}
                        accommodation.
                    </p>

                    <div
                        class="mt-4 rounded-xl bg-slate-50 p-3 flex items-center justify-between"
                    >
                        <div>
                            <p class="text-xs text-slate-500">
                                Current Room & Bed
                            </p>
                            <p class="font-semibold">
                                Room {{ admission?.room?.room_no ?? "—" }}
                                <span
                                    v-if="admission?.bed?.bed_no"
                                    class="text-slate-400 font-normal"
                                >
                                    · Bed {{ admission.bed.bed_no }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div v-if="loading" class="mt-6 grid sm:grid-cols-2 gap-4">
                        <div
                            v-for="i in 4"
                            :key="i"
                            class="animate-pulse rounded-xl border p-4 space-y-3"
                        >
                            <div class="h-4 w-28 rounded bg-slate-200" />
                            <div class="h-3 w-40 rounded bg-slate-100" />
                        </div>
                    </div>

                    <div
                        v-else-if="!roomsForType.length"
                        class="mt-6 rounded-xl border border-dashed py-10 text-center text-sm text-slate-500"
                    >
                        No rooms available for this accommodation type.
                    </div>

                    <div v-else class="mt-6 grid sm:grid-cols-2 gap-4">
                        <div
                            v-for="room in roomsForType"
                            :key="room.room_id"
                            class="rounded-xl border p-4 transition"
                            :class="
                                selectedRoom?.room_id === room.room_id
                                    ? 'border-primary ring-1 ring-primary/30'
                                    : ''
                            "
                        >
                            <div class="flex justify-between">
                                <div>
                                    <p class="font-semibold">
                                        Room {{ room.room_no }}
                                        <span
                                            v-if="isCurrentRoom(room)"
                                            class="text-xs font-normal text-slate-400"
                                        >
                                            (current)
                                        </span>
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ room.floor }} Floor
                                    </p>
                                </div>
                                <span
                                    class="text-xs h-fit rounded-full px-2.5 py-1 font-medium"
                                    :class="
                                        availableBeds(room).length > 0
                                            ? 'bg-emerald-50 text-emerald-700'
                                            : 'bg-rose-50 text-rose-700'
                                    "
                                >
                                    {{
                                        availableBeds(room).length > 0
                                            ? "Available"
                                            : "Fully Reserved"
                                    }}
                                </span>
                            </div>

                            <button
                                type="button"
                                :disabled="
                                    availableBeds(room).length === 0 &&
                                    !isCurrentRoom(room)
                                "
                                class="mt-4 w-full rounded-lg bg-primary text-white py-2 text-sm font-medium transition hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed"
                                @click="openBeds(room)"
                            >
                                {{
                                    selectedRoom?.room_id === room.room_id &&
                                    selectedBed
                                        ? `Bed ${selectedBed.bed_no} selected`
                                        : "Select Bed"
                                }}
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="selectedRoom && selectedBed"
                        class="mt-5 rounded-xl bg-primary/5 border border-primary/20 p-4 flex items-center justify-between"
                    >
                        <div>
                            <p class="text-xs text-slate-500">New Room & Bed</p>
                            <p class="font-semibold">
                                Room {{ selectedRoom.room_no }}
                                <span class="text-slate-400 font-normal">
                                    · Bed {{ selectedBed.bed_no }}
                                </span>
                            </p>
                        </div>
                        <span
                            class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700"
                        >
                            Selected
                        </span>
                    </div>

                    <div v-if="selectedRoom && selectedBed" class="mt-4">
                        <label
                            class="block text-xs font-medium text-slate-500 mb-1.5"
                        >
                            Reason for change
                            <span class="text-slate-400 font-normal"
                                >(optional)</span
                            >
                        </label>
                        <BaseInput
                            v-model="reason"
                            :rows="2"
                            maxlength="255"
                            mode="textarea"
                            placeholder="e.g. Patient requested a quieter room, bed maintenance..."
                            class="w-full p-3 text-sm resize-none"
                        />
                    </div>

                    <button
                        class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-2.5 text-sm font-medium text-white disabled:opacity-50"
                        :disabled="submitting || !selectedRoom || !selectedBed"
                        @click="confirmSelection"
                    >
                        <svg
                            v-if="submitting"
                            class="h-4 w-4 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                            />
                        </svg>
                        {{ submitting ? "Moving..." : "Confirm Move" }}
                    </button>
                    <button
                        class="mt-3 w-full rounded-xl border py-2.5 text-sm"
                        @click="$emit('close')"
                    >
                        Cancel
                    </button>
                </div>

                <transition
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition duration-100 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="showBeds"
                        class="fixed inset-0 z-[80] bg-black/40 flex items-center justify-center p-5"
                        @click.self="showBeds = false"
                    >
                        <div
                            class="bg-white rounded-2xl w-full max-w-lg p-6 shadow-xl"
                        >
                            <div class="flex justify-between items-center mb-5">
                                <div>
                                    <h3 class="font-semibold text-lg">
                                        Select Bed
                                    </h3>
                                    <p class="text-sm text-slate-500">
                                        Room {{ modalRoom?.room_no }}
                                    </p>
                                </div>
                                <button
                                    class="h-8 w-8 rounded-full flex items-center justify-center text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                                    aria-label="Close"
                                    @click="showBeds = false"
                                >
                                    ✕
                                </button>
                            </div>

                            <div class="space-y-3">
                                <button
                                    v-for="bed in modalRoom?.beds"
                                    :key="bed.bed_id"
                                    :disabled="
                                        bed.status.toLowerCase() !==
                                            'available' && !isCurrentBed(bed)
                                    "
                                    class="w-full rounded-xl border p-4 flex justify-between items-center transition"
                                    :class="
                                        bed.status.toLowerCase() ===
                                            'available' || isCurrentBed(bed)
                                            ? 'hover:border-primary hover:bg-primary/5'
                                            : 'opacity-50 cursor-not-allowed'
                                    "
                                    @click="chooseBed(bed)"
                                >
                                    <span class="font-medium">
                                        Bed {{ bed.bed_no }}
                                        <span
                                            v-if="isCurrentBed(bed)"
                                            class="text-xs font-normal text-slate-400"
                                        >
                                            (current)
                                        </span>
                                    </span>
                                    <span
                                        class="text-xs rounded-full px-3 py-1 font-medium"
                                        :class="
                                            isCurrentBed(bed)
                                                ? 'bg-slate-200 text-slate-600'
                                                : bed.status.toLowerCase() ===
                                                    'available'
                                                  ? 'bg-emerald-50 text-emerald-700'
                                                  : 'bg-rose-50 text-rose-700'
                                        "
                                    >
                                        {{
                                            isCurrentBed(bed)
                                                ? "Current"
                                                : bed.status
                                        }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, computed } from "vue";
import { useRoute } from "vue-router";
import BaseInput from "~/components/ui/BaseInput.vue";

import { admissionService } from "~/api/admission/AdmissionService";
import type { RoomContract } from "~/types/contract";
import type { Admission } from "~/types/patient";
import type { Room } from "~/types/room";
import type { Bed } from "~/types/bed";

const route = useRoute();

const props = defineProps<{
    open: boolean;
    admission: Admission | null;
}>();

const emit = defineEmits<{
    (e: "select", payload: { room: Room; bed: Bed; reason: string }): void;
    (e: "close"): void;
}>();

const contracts = ref<RoomContract[]>([]);
const loading = ref(false);
const submitting = ref(false);

const selectedRoom = ref<Room | null>(null);
const selectedBed = ref<Bed | null>(null);
const modalRoom = ref<Room | null>(null);
const showBeds = ref(false);
const reason = ref("");

function normalizeType(type?: string | null) {
    return (type ?? "").trim().toUpperCase();
}

const currentType = computed(() =>
    normalizeType(props.admission?.current_contract?.accommodation_type),
);

const roomsForType = computed<Room[]>(() => {
    const seen = new Set<number>();
    const rooms: Room[] = [];

    for (const c of contracts.value) {
        if (normalizeType(c.accommodation_type) !== currentType.value) continue;
        for (const room of c.rooms ?? []) {
            if (seen.has(room.room_id)) continue;
            seen.add(room.room_id);
            rooms.push(room);
        }
    }

    return rooms;
});

function isCurrentRoom(room: Room) {
    return room.room_id === props.admission?.room?.room_id;
}

function isCurrentBed(bed: Bed) {
    return bed.bed_id === props.admission?.bed?.bed_id;
}

function availableBeds(room: Room) {
    if (!room.beds?.length) return [];
    return room.beds.filter(
        (b) => b.status?.toLowerCase() === "available" || isCurrentBed(b),
    );
}

function openBeds(room: Room) {
    modalRoom.value = room;
    showBeds.value = true;
}

function chooseBed(bed: Bed) {
    if (bed.status.toLowerCase() !== "available" && !isCurrentBed(bed)) return;
    if (!modalRoom.value) return;
    selectedRoom.value = modalRoom.value;
    selectedBed.value = bed;
    showBeds.value = false;
}

watch(
    [() => props.open, () => props.admission],
    async ([open, admission]) => {
        if (!open || !admission) return;

        selectedRoom.value = null;
        selectedBed.value = null;
        reason.value = "";

        loading.value = true;

        try {
            const res = await admissionService.action({
                branch_uuid: route.params.uuid,
                action: "branch_contract",
            });

            contracts.value = res.data?.data ?? res.data ?? res ?? [];
        } catch (err) {
            console.error("Failed loading contracts", err);
            contracts.value = [];
        } finally {
            loading.value = false;
        }
    },
    { immediate: true },
);

function confirmSelection() {
    if (!selectedRoom.value || !selectedBed.value) return;
    submitting.value = true;

    emit("select", {
        room: selectedRoom.value,
        bed: selectedBed.value,
        reason: reason.value.trim(),
    });
}

watch(
    () => props.open,
    (value) => {
        if (!value) submitting.value = false;
    },
);
</script>
