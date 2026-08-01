<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-5"
        @click.self="close"
    >
        <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        Select Bed
                    </h2>

                    <p v-if="room" class="text-sm text-slate-500 mt-1">
                        Room {{ room.room_no }} · {{ room.room_type }} Room
                    </p>
                </div>

                <button
                    class="h-8 w-8 rounded-lg hover:bg-slate-100"
                    @click="close"
                >
                    ✕
                </button>
            </div>

            <!-- Beds -->
            <div v-if="room" class="space-y-3">
                <button
                    v-for="bed in room.beds"
                    :key="bed.bed_id"
                    class="w-full rounded-xl border p-4 flex items-center justify-between transition"
                    :disabled="!isAvailable(bed)"
                    :class="
                        isAvailable(bed)
                            ? 'hover:border-primary hover:bg-primary/5'
                            : 'bg-slate-50 opacity-50 cursor-not-allowed'
                    "
                    @click="select(bed)"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-lg flex items-center justify-center"
                            :class="
                                isAvailable(bed)
                                    ? 'bg-emerald-50 text-emerald-600'
                                    : 'bg-slate-200 text-slate-400'
                            "
                        >
                            🛏
                        </div>

                        <div class="text-left">
                            <p class="font-medium">Bed {{ bed.bed_no }}</p>

                            <p
                                class="text-xs"
                                :class="
                                    isAvailable(bed)
                                        ? 'text-emerald-600'
                                        : 'text-slate-400'
                                "
                            >
                                {{ bed.status }}
                            </p>
                        </div>
                    </div>

                    <span
                        v-if="isAvailable(bed)"
                        class="text-xs font-medium text-primary"
                    >
                        Select
                    </span>

                    <span v-else class="text-xs text-slate-400">
                        Unavailable
                    </span>
                </button>
            </div>

            <!-- Empty -->
            <div
                v-if="room && !availableBeds.length"
                class="rounded-xl bg-slate-50 p-5 text-center text-sm text-slate-500"
            >
                No beds available in this room.
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { Room } from "~/types/room";
import type { Bed } from "~/types/bed";

const props = defineProps<{
    open: boolean;
    room: Room | null;
}>();

const emit = defineEmits<{
    (e: "close"): void;

    (e: "select", bed: Bed): void;
}>();

function close() {
    emit("close");
}

function isAvailable(bed: Bed) {
    return bed.status === "Available";
}

const availableBeds = computed(() => {
    if (!props.room) return [];

    return props.room.beds.filter((bed) => isAvailable(bed));
});

function select(bed: Bed) {
    if (!isAvailable(bed)) return;

    emit("select", bed);
}
</script>
