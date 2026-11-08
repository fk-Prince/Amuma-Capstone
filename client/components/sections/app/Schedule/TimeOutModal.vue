<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { CheckCheck, Clock, Loader2, LogOut, Users, Wallet } from "lucide-vue-next";
import { formatTime } from "~/utils/time";

const props = defineProps<{
    open: boolean;
    scheduleCode: string;
    patientName: string;
    services: string[];
    scannerName: string;
    scannerTimedOut: boolean;
    othersPresent: string[];
    endsAt: string | null;
    unpaid: boolean;
    loading?: boolean;
}>();

const emit = defineEmits<{
    close: [];
    submit: [complete: boolean];
}>();

const step = ref<"choose" | "confirm">("choose");
const complete = ref(true);
const now = ref(Date.now());

const minutesEarly = computed(() => {
    const end = props.endsAt ? new Date(props.endsAt).getTime() : NaN;
    if (Number.isNaN(end)) return 0;
    const diff = end - now.value;
    return diff >= 60_000 ? Math.round(diff / 60_000) : 0;
});

const hasOthers = computed(() => props.othersPresent.length > 0);
const canJustTimeOut = computed(() => hasOthers.value && !props.scannerTimedOut);
const hasWarnings = computed(() => hasOthers.value || minutesEarly.value > 0 || props.unpaid);

const othersLabel = computed(() => joinNames(props.othersPresent));

const timedOutNames = computed(() =>
    joinNames([...(props.scannerTimedOut ? [] : [`${props.scannerName} (you)`]), ...props.othersPresent]),
);

function joinNames(names: string[]) {
    if (names.length <= 1) return names[0] ?? "";
    return `${names.slice(0, -1).join(", ")} and ${names[names.length - 1]}`;
}

function earlyLabel(minutes: number) {
    if (minutes < 60) return `${minutes} minute${minutes === 1 ? "" : "s"}`;
    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;
    return rest ? `${hours} hr ${rest} min` : `${hours} hr${hours === 1 ? "" : "s"}`;
}

watch(
    () => props.open,
    (open) => {
        if (!open) return;
        now.value = Date.now();
        complete.value = true;
        step.value = hasWarnings.value ? "choose" : "confirm";
    },
    { immediate: true },
);

function choose(value: boolean) {
    complete.value = value;
    step.value = "confirm";
}

function back() {
    if (hasWarnings.value) step.value = "choose";
    else emit("close");
}

function close() {
    if (!props.loading) emit("close");
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 p-4"
            @click.self="close"
        >
            <div
                role="alertdialog"
                aria-modal="true"
                class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary"
            >
                <template v-if="step === 'choose'">
                    <div class="flex items-center gap-3 p-5 pb-3">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <LogOut class="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-base font-semibold text-slate-800 dark:text-white">
                                Time out of <span class="font-mono">{{ scheduleCode }}</span>
                            </p>
                            <p class="truncate text-xs text-slate-500 dark:text-gray-400">
                                {{ patientName }}<template v-if="services.length"> · {{ services.join(", ") }}</template>
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2 px-5">
                        <div
                            v-if="hasOthers"
                            class="flex items-start gap-2.5 rounded-xl bg-sky-50 px-3 py-2.5 text-sm text-sky-800 dark:bg-sky-500/10 dark:text-sky-300"
                        >
                            <Users class="mt-0.5 h-4 w-4 shrink-0" />
                            <p>
                                <span class="font-semibold">
                                    {{ othersLabel }} {{ othersPresent.length > 1 ? "are" : "is" }} still timed in.
                                </span>
                                <br />
                                <span class="text-slate-600 dark:text-gray-300">
                                    Completing will time them out too.
                                </span>
                            </p>
                        </div>

                        <div
                            v-if="minutesEarly > 0 && endsAt"
                            class="flex items-start gap-2.5 rounded-xl bg-amber-50 px-3 py-2.5 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-300"
                        >
                            <Clock class="mt-0.5 h-4 w-4 shrink-0" />
                            <p>
                                <span class="font-semibold">Scheduled until {{ formatTime(endsAt) }}.</span>
                                <br />
                                <span class="text-slate-600 dark:text-gray-300">
                                    Completing now is {{ earlyLabel(minutesEarly) }} early.
                                </span>
                            </p>
                        </div>

                        <div
                            v-if="unpaid"
                            class="flex items-start gap-2.5 rounded-xl bg-rose-50 px-3 py-2.5 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300"
                        >
                            <Wallet class="mt-0.5 h-4 w-4 shrink-0" />
                            <p class="font-semibold">This service hasn't been fully paid yet.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 p-5">
                        <button
                            type="button"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-white/5"
                            @click="close"
                        >
                            Cancel
                        </button>
                        <button
                            v-if="canJustTimeOut"
                            type="button"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5"
                            @click="choose(false)"
                        >
                            Just time out
                        </button>
                        <button
                            type="button"
                            class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90"
                            @click="choose(true)"
                        >
                            {{ scannerTimedOut ? "Complete visit" : "Time out & complete" }}
                        </button>
                    </div>

                    <p
                        v-if="canJustTimeOut"
                        class="-mt-3 px-5 pb-4 text-right text-[11px] text-slate-400 dark:text-gray-500"
                    >
                        Just time out: only you. Complete: everyone, and the visit ends.
                    </p>
                </template>

                <template v-else>
                    <div class="flex items-center gap-3 p-5 pb-3">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                            :class="
                                complete
                                    ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300'
                                    : 'bg-primary/10 text-primary'
                            "
                        >
                            <CheckCheck v-if="complete" class="h-5 w-5" />
                            <LogOut v-else class="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-base font-semibold text-slate-800 dark:text-white">
                                {{ complete ? "Complete this visit?" : "Time out only you?" }}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-gray-400">
                                Please check before you confirm. This can't be undone from here.
                            </p>
                        </div>
                    </div>

                    <ul
                        class="mx-5 list-disc space-y-1.5 rounded-xl border border-slate-200 bg-slate-50 py-3 pl-8 pr-4 text-sm text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300"
                    >
                        <template v-if="complete">
                            <li v-if="timedOutNames">
                                Time out <span class="font-semibold text-slate-800 dark:text-white">{{ timedOutNames }}</span> now
                            </li>
                            <li>
                                Mark <span class="font-mono font-semibold text-slate-800 dark:text-white">{{ scheduleCode }}</span>
                                as <span class="font-semibold text-slate-800 dark:text-white">completed</span>
                                <template v-if="minutesEarly > 0">, {{ earlyLabel(minutesEarly) }} before its scheduled end</template>
                            </li>
                            <li v-if="unpaid">
                                Complete it while the service is
                                <span class="font-semibold text-slate-800 dark:text-white">not fully paid</span>
                            </li>
                            <li>Notify the family that the visit is done</li>
                        </template>
                        <template v-else>
                            <li>
                                Time out <span class="font-semibold text-slate-800 dark:text-white">only you ({{ scannerName }})</span> now
                            </li>
                            <li>{{ othersLabel }} {{ othersPresent.length > 1 ? "stay" : "stays" }} timed in</li>
                            <li>The visit stays <span class="font-semibold text-slate-800 dark:text-white">ongoing</span></li>
                        </template>
                    </ul>

                    <div class="flex justify-end gap-2 p-5">
                        <button
                            type="button"
                            :disabled="loading"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                            @click="back"
                        >
                            {{ hasWarnings ? "Back" : "Cancel" }}
                        </button>
                        <button
                            type="button"
                            :disabled="loading"
                            class="flex min-w-[9rem] items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90 disabled:opacity-70"
                            @click="emit('submit', complete)"
                        >
                            <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
                            {{ complete ? "Yes, complete visit" : "Yes, time me out" }}
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </Teleport>
</template>
