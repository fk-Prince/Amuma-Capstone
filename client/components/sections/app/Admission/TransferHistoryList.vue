<template>
    <div
        v-if="!transfers?.length"
        class="py-10 text-center text-sm text-slate-400"
    >
        No room transfers on record.
    </div>

    <ol v-else class="relative pl-6">
        <span
            class="absolute left-[7px] top-2 bottom-2 w-px bg-primary-100"
            aria-hidden="true"
        />

        <li
            v-for="transfer in sortedTransfers"
            :key="transfer.room_transfer_id"
            class="relative pb-5 last:pb-0"
        >
            <span
                class="absolute -left-6 top-1.5 h-3.5 w-3.5 rounded-full bg-white border-2 border-primary-300"
            />

            <div class="rounded-xl border border-primary-100 p-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 text-sm min-w-0">
                        <span class="text-slate-500 truncate">
                            Room {{ transfer.from_room?.room_no ?? "—" }}
                            <span
                                v-if="transfer.from_bed?.bed_no"
                                class="text-slate-400"
                            >
                                · Bed {{ transfer.from_bed.bed_no }}
                            </span>
                        </span>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="w-3.5 h-3.5 text-slate-400 shrink-0"
                        >
                            <line x1="5" y1="12" x2="19" y2="12" />
                            <polyline points="12 5 19 12 12 19" />
                        </svg>
                        <span class="font-medium text-primary-900 truncate">
                            Room {{ transfer.to_room?.room_no ?? "—" }}
                            <span
                                v-if="transfer.to_bed?.bed_no"
                                class="text-slate-400 font-normal"
                            >
                                · Bed {{ transfer.to_bed.bed_no }}
                            </span>
                        </span>
                    </div>

                    <span class="shrink-0 text-xs text-muted">
                        {{ formatDate(transfer.created_at) }}
                    </span>
                </div>

                <p v-if="transfer.reason" class="mt-2 text-xs text-slate-500">
                    {{ transfer.reason }}
                </p>
            </div>
        </li>
    </ol>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { RoomTransfer } from "~/types/room";
import { formatDate } from "~/utils/time";

const props = defineProps<{
    transfers?: RoomTransfer[] | null;
}>();

const sortedTransfers = computed(() =>
    [...(props.transfers ?? [])].sort(
        (a, b) =>
            new Date(b.created_at).getTime() - new Date(a.created_at).getTime(),
    ),
);

</script>
