<template>
    <div class="w-full lg:h-full flex flex-col min-h-0">
        <div
            class="lg:h-full rounded-lg bg-white border border-[#E4EFED] p-5 flex flex-col overflow-hidden dark:bg-secondary dark:border-white/10"
        >
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="font-semibold text-[#16302E] dark:text-white">
                        Booking Overview
                    </h3>

                    <p class="text-xs text-[#6B8A87] mt-1 dark:text-gray-400">
                        Today's booking activity
                    </p>
                </div>

                <div
                    class="h-10 w-10 rounded-xl bg-primary-50 flex items-center justify-center text-primary dark:bg-primary-500/10"
                >
                    <CalendarDays class="h-5 w-5" />
                </div>
            </div>

            <div class="flex-1 overflow-auto">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div
                            class="rounded-xl border border-[#EDF4F3] bg-[#FAFCFB] p-4 dark:border-white/10 dark:bg-white/5"
                        >
                            <div class="flex items-center justify-between">
                                <p
                                    class="text-xs uppercase tracking-wide text-[#6B8A87] dark:text-gray-400"
                                >
                                    Pending
                                </p>
                                <Clock class="h-4 w-4 text-amber-500 dark:text-amber-300" />
                            </div>

                            <p
                                class="mt-2 text-2xl font-semibold text-[#16302E] dark:text-white"
                            >
                                {{
                                    overview?.bookings?.pending_confirmation ??
                                    0
                                }}
                            </p>

                            <p class="text-xs text-[#6B8A87] dark:text-gray-400">
                                Need confirmation
                            </p>
                        </div>

                        <div
                            class="rounded-xl border border-[#EDF4F3] bg-[#FAFCFB] p-4 dark:border-white/10 dark:bg-white/5"
                        >
                            <div class="flex items-center justify-between">
                                <p
                                    class="text-xs uppercase tracking-wide text-[#6B8A87] dark:text-gray-400"
                                >
                                    Approved
                                </p>
                                <CalendarClock class="h-4 w-4 text-blue-500 dark:text-blue-300" />
                            </div>

                            <p
                                class="mt-2 text-2xl font-semibold text-[#16302E] dark:text-white"
                            >
                                {{ overview?.bookings?.approved ?? 0 }}
                            </p>

                            <p class="text-xs text-[#6B8A87] dark:text-gray-400">
                                Waiting payment
                            </p>
                        </div>

                        <div
                            class="rounded-xl border border-[#EDF4F3] bg-[#FAFCFB] p-4 dark:border-white/10 dark:bg-white/5"
                        >
                            <div class="flex items-center justify-between">
                                <p
                                    class="text-xs uppercase tracking-wide text-[#6B8A87] dark:text-gray-400"
                                >
                                    Rejected
                                </p>
                                <UserX class="h-4 w-4 text-rose-500 dark:text-rose-300" />
                            </div>

                            <p
                                class="mt-2 text-2xl font-semibold text-[#16302E] dark:text-white"
                            >
                                {{ overview?.bookings?.rejected ?? 0 }}
                            </p>

                            <p class="text-xs text-[#6B8A87] dark:text-gray-400">
                                Rejected bookings
                            </p>
                        </div>

                        <div
                            class="rounded-xl border border-[#EDF4F3] bg-[#FAFCFB] p-4 dark:border-white/10 dark:bg-white/5"
                        >
                            <div class="flex items-center justify-between">
                                <p
                                    class="text-xs uppercase tracking-wide text-[#6B8A87] dark:text-gray-400"
                                >
                                    Expiring
                                </p>
                                <Clock class="h-4 w-4 text-red-500" />
                            </div>

                            <p
                                class="mt-2 text-2xl font-semibold text-[#16302E] dark:text-white"
                            >
                                {{ overview?.bookings?.expiring_soon ?? 0 }}
                            </p>

                            <p class="text-xs text-[#6B8A87] dark:text-gray-400">
                                Within 24 hours
                            </p>
                        </div>

                        <div
                            class="rounded-xl border border-[#EDF4F3] bg-[#FAFCFB] p-4 dark:border-white/10 dark:bg-white/5"
                        >
                            <div class="flex items-center justify-between">
                                <p
                                    class="text-xs uppercase tracking-wide text-[#6B8A87] dark:text-gray-400"
                                >
                                    Today
                                </p>
                                <CalendarDays class="h-4 w-4 text-[#0E7C7B] dark:text-accent-300" />
                            </div>

                            <p
                                class="mt-2 text-2xl font-semibold text-[#16302E] dark:text-white"
                            >
                                {{ overview?.bookings?.today ?? 0 }}
                            </p>

                            <p class="text-xs text-[#6B8A87] dark:text-gray-400">New bookings</p>
                        </div>
                        <div
                            class="rounded-xl border border-[#EDF4F3] bg-[#FAFCFB] p-4 dark:border-white/10 dark:bg-white/5"
                        >
                            <p
                                class="text-xs uppercase tracking-wide text-[#6B8A87] dark:text-gray-400"
                            >
                                Schedule Today
                            </p>

                            <p
                                class="mt-2 text-2xl font-semibold text-[#16302E] dark:text-white"
                            >
                                {{ overview?.schedule?.today ?? 0 }}
                            </p>

                            <p class="text-xs text-[#6B8A87] dark:text-gray-400">Appointments</p>
                        </div>
                    </div>

                    <div class="mt-5 border-t border-[#EDF4F3] pt-4 dark:border-white/10">
                        <p
                            class="text-xs uppercase tracking-wide text-[#6B8A87] mb-3 dark:text-gray-400"
                        >
                            Recent Bookings
                        </p>

                        <div
                            class="min-h-[280px] max-h-[440px] space-y-3 overflow-y-auto pr-1"
                        >
                            <div
                                v-for="item in overview?.bookings?.recent ?? []"
                                :key="item.booking_id"
                                class="flex items-center justify-between"
                            >
                                <div>
                                    <p
                                        class="text-sm font-medium text-[#16302E] dark:text-white"
                                    >
                                        {{ item.reference_id }}
                                    </p>

                                    <p
                                        class="text-xs text-[#6B8A87] capitalize dark:text-gray-400"
                                    >
                                        {{ item.category }} ·
                                        {{ formatStatus(item.status) }}
                                    </p>
                                </div>

                                <p class="text-xs text-[#6B8A87] dark:text-gray-400">
                                    {{ notifcationFormatDate(item.created_at) }}
                                </p>
                            </div>

                            <p
                                v-if="
                                    !(overview?.bookings?.recent ?? []).length
                                "
                                class="text-sm text-[#6B8A87] dark:text-gray-400"
                            >
                                No recent bookings
                            </p>
                        </div>
                    </div>
                </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, computed, watch } from "vue";
import {
    CalendarDays,
    Clock,
    CalendarClock,
    UserX,
} from "lucide-vue-next";
import { useRoute } from "vue-router";
import { notifcationFormatDate } from "~/utils/notification-time";
import { useAuthUser } from "~/composables/useAuthUser";
import { formatStatus } from "~/types/booking";

const route = useRoute();
const user = useAuthUser();
const { $echo } = useNuxtApp();

const props = defineProps<{
    overview: any;
}>();

const emit = defineEmits<{
    (e: "newBooking", booking: any): void;
}>();

let channel: any = null;
let handler: ((e: any) => void) | null = null;

const branchUuid = computed(() => route.params.uuid as string);

const bindNotification = () => {
    const uuid = user.value?.uuid;

    if (channel && handler) {
        channel.stopListening(".NotificationEvent", handler);
        channel = null;
    }

    if (!uuid || !$echo) return;

    handler = (e: any) => {
        if (e.branch_uuid !== branchUuid.value || !e.booking) {
            return;
        }

        if (props.overview?.bookings) {
            props.overview.bookings.recent = [
                e.booking,
                ...(props.overview.bookings.recent ?? []),
            ].slice(0, 5);
        }

        emit("newBooking", e.booking);
    };

    channel = $echo
        .private(`Notification.${uuid}`)
        .listen(".NotificationEvent", handler);
};

watch(() => user.value?.uuid, bindNotification, { immediate: true });

// stopListening with the stored handler, not leave(): the header bell and
// other components share this channel and leave() would tear it down for all.
onBeforeUnmount(() => {
    if (channel && handler) {
        channel.stopListening(".NotificationEvent", handler);
        channel = null;
    }
});
</script>
