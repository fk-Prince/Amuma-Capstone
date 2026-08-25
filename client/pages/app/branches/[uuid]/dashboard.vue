<template>
    <div
        class="min-h-screen-header-header bg-light flex items-center justify-center p-8"
    >
        <SubscriptionReview v-if="isSubscriptionPending" />

        <!-- Normal Dashboard -->
        <template v-else>
            <!--
            <ScheduleCard
                date-label="Today, May 2, 2026"
                :now-hour="12.75"
                :branches="branches"
                @change-day="onChangeDay"
            />

            <RoomOccupancyCard :rooms="roomData" />
            -->
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

import SubscriptionReview from "~/components/sections/app/Dashboard/SubscriptionReview.vue";

import RoomOccupancyCard, {
    type RoomOccupancy,
} from "~/components/sections/app/Dashboard/RoomOccupancyCard.vue";

import ScheduleCard, {
    type Branch,
} from "~/components/sections/app/Dashboard/ScheduleCard.vue";

import { useBranchStore } from "~/stores/branch";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({
    title: "Dashboard",
});

const branchStore = useBranchStore();

const activeBranch = computed(() => branchStore.activeBranch);

const isSubscriptionPending = computed(() => {
    const branch = activeBranch.value;
    return !branch?.agency?.is_verified || !branch?.is_verified;
});

const roomData: RoomOccupancy[] = [
    { label: "VIP", occupied: 2, available: 1, reserved: 1 },
    { label: "Common", occupied: 1, available: 3, reserved: 5 },
];

const branches: Branch[] = [
    {
        staffInitials: ["SR", "AP", "JA"],
        extraStaff: 3,
        shifts: [
            {
                name: "Dr. Strange Revillame",
                role: "doctor",
                start: 8,
                end: 12,
            },
            {
                name: "Dr. Apple Pie Macutesy",
                role: "doctor",
                start: 14,
                end: 18,
            },
        ],
    },
    {
        staffInitials: ["LW", "JA"],
        extraStaff: 2,
        shifts: [
            { name: "Linda Walker", role: "nurse", start: 8, end: 15 },
            { name: "Jung Ahyeon", role: "doctor", start: 15, end: 18 },
        ],
    },
    {
        staffInitials: ["TS", "EA"],
        extraStaff: 1,
        shifts: [
            { name: "Taylor Swift", role: "caregiver", start: 8, end: 16 },
            { name: "Enami Asa", role: "nurse", start: 16, end: 18.5 },
        ],
    },
    {
        staffInitials: ["KM", "WK"],
        extraStaff: 2,
        shifts: [
            { name: "Kim Matanglawin", role: "doctor", start: 10, end: 14 },
            { name: "Wala nako kabalo", role: "caregiver", start: 14, end: 18 },
        ],
    },
];

function onChangeDay(direction: 1 | -1) {
    console.log("change day", direction);
}
</script>
