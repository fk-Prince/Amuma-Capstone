<template>
    <div class="w-full lg:h-full flex flex-col min-h-0">
        <div
            class="lg:h-full rounded-2xl bg-white border border-[#E4EFED] p-5 flex flex-col overflow-hidden dark:border-white/10 dark:bg-secondary"
        >
            <button
                type="button"
                class="lg:hidden flex items-center justify-between w-full mb-5"
                @click="open = !open"
            >
                <div>
                    <h3 class="font-semibold text-[#16302E] dark:text-white">
                        Schedule Overview
                    </h3>

                    <p class="text-xs text-[#6B8A87] mt-1 dark:text-gray-400">
                        Today's schedule activity
                    </p>
                </div>

                <svg
                    class="h-5 w-5 text-[#16302E] transition-transform duration-300 dark:text-white"
                    :class="{ 'rotate-180': open }"
                    viewBox="0 0 20 20"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                >
                    <path
                        d="M5 7.5L10 12.5L15 7.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </button>

            <div class="hidden lg:flex items-center justify-between mb-5">
                <div>
                    <h3 class="font-semibold text-[#16302E] dark:text-white">
                        Schedule Overview
                    </h3>

                    <p class="text-xs text-[#6B8A87] mt-1 dark:text-gray-400">
                        Today's schedule activity
                    </p>
                </div>

                <div
                    class="h-10 w-10 rounded-xl bg-[#EAF4F2] flex items-center justify-center text-[#0E7C7B] dark:text-accent-300 dark:bg-accent-500/15"
                >
                    <CalendarDays class="h-5 w-5" />
                </div>
            </div>

            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="max-h-0 opacity-0 -translate-y-2"
                enter-to-class="max-h-[1200px] opacity-100 translate-y-0"
                leave-active-class="transition-all duration-300 ease-in"
                leave-from-class="max-h-[1200px] opacity-100 translate-y-0"
                leave-to-class="max-h-0 opacity-0 -translate-y-2"
            >
                <div
                    v-show="open"
                    class="flex-1 overflow-hidden lg:overflow-auto"
                >
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <div
                            class="rounded-xl border border-[#EDF4F3] bg-[#FAFCFB] p-4 dark:border-white/10 dark:bg-white/5"
                        >
                            <div class="flex items-center justify-between">
                                <p
                                    class="text-xs uppercase tracking-wide text-[#6B8A87] dark:text-gray-400"
                                >
                                    Upcoming
                                </p>
                                <Clock class="h-4 w-4 text-amber-500 dark:text-amber-300" />
                            </div>

                            <p
                                class="mt-2 text-2xl font-semibold text-[#16302E] dark:text-white"
                            >
                                {{ overview?.schedule?.upcoming ?? 0 }}
                            </p>

                            <p class="text-xs text-[#6B8A87] dark:text-gray-400">
                                Not yet started
                            </p>
                        </div>

                        <div
                            class="rounded-xl border border-[#EDF4F3] bg-[#FAFCFB] p-4 dark:border-white/10 dark:bg-white/5"
                        >
                            <div class="flex items-center justify-between">
                                <p
                                    class="text-xs uppercase tracking-wide text-[#6B8A87] dark:text-gray-400"
                                >
                                    In Progress
                                </p>
                                <CalendarClock class="h-4 w-4 text-blue-500 dark:text-blue-300" />
                            </div>

                            <p
                                class="mt-2 text-2xl font-semibold text-[#16302E] dark:text-white"
                            >
                                {{ overview?.schedule?.in_progress ?? 0 }}
                            </p>

                            <p class="text-xs text-[#6B8A87] dark:text-gray-400">
                                Currently ongoing
                            </p>
                        </div>

                        <div
                            class="rounded-xl border border-[#EDF4F3] bg-[#FAFCFB] p-4 dark:border-white/10 dark:bg-white/5"
                        >
                            <div class="flex items-center justify-between">
                                <p
                                    class="text-xs uppercase tracking-wide text-[#6B8A87] dark:text-gray-400"
                                >
                                    Waiting
                                </p>
                                <Clock class="h-4 w-4 text-orange-500" />
                            </div>

                            <p
                                class="mt-2 text-2xl font-semibold text-[#16302E] dark:text-white"
                            >
                                {{ overview?.schedule?.waiting ?? 0 }}
                            </p>

                            <p class="text-xs text-[#6B8A87] dark:text-gray-400">
                                Checked in, waiting
                            </p>
                        </div>

                        <div
                            class="rounded-xl border border-[#EDF4F3] bg-[#FAFCFB] p-4 dark:border-white/10 dark:bg-white/5"
                        >
                            <div class="flex items-center justify-between">
                                <p
                                    class="text-xs uppercase tracking-wide text-[#6B8A87] dark:text-gray-400"
                                >
                                    Completed
                                </p>
                                <UserCheck class="h-4 w-4 text-emerald-600 dark:text-emerald-300" />
                            </div>

                            <p
                                class="mt-2 text-2xl font-semibold text-[#16302E] dark:text-white"
                            >
                                {{ overview?.schedule?.completed ?? 0 }}
                            </p>

                            <p class="text-xs text-[#6B8A87] dark:text-gray-400">Finished today</p>
                        </div>

                        <div
                            class="rounded-xl border border-[#EDF4F3] bg-[#FAFCFB] p-4 dark:border-white/10 dark:bg-white/5"
                        >
                            <div class="flex items-center justify-between">
                                <p
                                    class="text-xs uppercase tracking-wide text-[#6B8A87] dark:text-gray-400"
                                >
                                    Cancelled
                                </p>
                                <Clock class="h-4 w-4 text-red-500" />
                            </div>

                            <p
                                class="mt-2 text-2xl font-semibold text-[#16302E] dark:text-white"
                            >
                                {{ overview?.schedule?.cancelled ?? 0 }}
                            </p>

                            <p class="text-xs text-[#6B8A87] dark:text-gray-400">
                                Cancelled today
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
                                {{ overview?.schedule?.today ?? 0 }}
                            </p>

                            <p class="text-xs text-[#6B8A87] dark:text-gray-400">
                                Total appointments
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3">
                        <div
                            class="rounded-xl border border-[#EDF4F3] bg-[#FAFCFB] p-4 dark:border-white/10 dark:bg-white/5"
                        >
                            <p
                                class="text-xs uppercase tracking-wide text-[#6B8A87] dark:text-gray-400"
                            >
                                Next Slot
                            </p>

                            <p
                                class="mt-2 text-2xl font-semibold text-[#16302E] dark:text-white"
                            >
                                {{
                                    overview?.schedule?.next_slot_time ??
                                    "--:--"
                                }}
                            </p>

                            <p class="text-xs text-[#6B8A87] dark:text-gray-400">
                                {{
                                    overview?.schedule?.next_slot_patient ??
                                    "No upcoming slot"
                                }}
                            </p>
                        </div>

                        <div
                            class="rounded-xl border border-[#EDF4F3] bg-[#FAFCFB] p-4 dark:border-white/10 dark:bg-white/5"
                        >
                            <p
                                class="text-xs uppercase tracking-wide text-[#6B8A87] dark:text-gray-400"
                            >
                                Providers
                            </p>

                            <p
                                class="mt-2 text-2xl font-semibold text-[#16302E] dark:text-white"
                            >
                                {{ overview?.providers?.active ?? 0 }}
                            </p>

                            <p class="text-xs text-[#6B8A87] dark:text-gray-400">On duty today</p>
                        </div>
                    </div>

                    <div class="mt-5 border-t border-[#EDF4F3] pt-4 dark:border-white/10">
                        <p
                            class="text-xs uppercase tracking-wide text-[#6B8A87] mb-3 dark:text-gray-400"
                        >
                            Upcoming Schedule
                        </p>

                        <div class="space-y-3">
                            <div
                                v-for="item in overview?.schedule
                                    ?.upcoming_list ?? []"
                                :key="item.schedule_id"
                                class="flex items-center justify-between"
                            >
                                <div>
                                    <p
                                        class="text-sm font-medium text-[#16302E] dark:text-white"
                                    >
                                        {{
                                            item.patient_name ??
                                            item.reference_id
                                        }}
                                    </p>

                                    <p
                                        class="text-xs text-[#6B8A87] capitalize dark:text-gray-400"
                                    >
                                        {{ item.category }} · {{ item.status }}
                                    </p>
                                </div>

                                <p class="text-xs text-[#6B8A87] dark:text-gray-400">
                                    {{ formatTime(item.scheduled_at) }}
                                </p>
                            </div>

                            <p
                                v-if="
                                    !(overview?.schedule?.upcoming_list ?? [])
                                        .length
                                "
                                class="text-sm text-[#6B8A87] dark:text-gray-400"
                            >
                                No upcoming schedule
                            </p>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed, watch } from "vue";
import { CalendarDays, Clock, CalendarClock, UserCheck } from "lucide-vue-next";
import { useRoute } from "vue-router";
import { notifcationFormatDate } from "~/utils/notification-time";
import { useAuthUser } from "~/composables/useAuthUser";

const open = ref(true);

const route = useRoute();
const user = useAuthUser();
const { $echo } = useNuxtApp();

const props = defineProps<{
    overview?: any;
}>();
let channel: any = null;
let handler: ((e: any) => void) | null = null;

const branchUuid = computed(() => route.params.uuid as string);

const formatTime = (value?: string) => {
    if (!value) return "--:--";

    const date = new Date(value);
    if (isNaN(date.getTime())) return notifcationFormatDate(value);

    return date.toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
    });
};

const bindNotification = () => {
    const uuid = user.value?.uuid;

    if (channel && handler) {
        channel.stopListening(".NotificationEvent", handler);
        channel = null;
    }

    if (!uuid || !$echo) return;

    handler = (e: any) => {
        if (e.branch_uuid !== branchUuid.value) {
            return;
        }

        if (!e.schedule) return;

        props.overview.schedule.upcoming_list = [
            e.schedule,
            ...(props.overview.schedule.upcoming_list ?? []),
        ].slice(0, 5);

        emit("newSchedule", e.schedule);
    };

    channel = $echo
        .private(`Notification.${uuid}`)
        .listen(".NotificationEvent", handler);
};

watch(() => user.value?.uuid, bindNotification, { immediate: true });

const emit = defineEmits<{
    (e: "newSchedule", schedule: any): void;
}>();

const isDesktop = ref(false);

const checkScreen = () => {
    isDesktop.value = window.innerWidth >= 1024;

    if (isDesktop.value) {
        open.value = true;
    }
};

onMounted(() => {
    checkScreen();
    window.addEventListener("resize", checkScreen);
});

// stopListening with the stored handler, not leave(): the header bell and
// other components share this channel and leave() would tear it down for all.
onBeforeUnmount(() => {
    window.removeEventListener("resize", checkScreen);

    if (channel && handler) {
        channel.stopListening(".NotificationEvent", handler);
    }
});
</script>
