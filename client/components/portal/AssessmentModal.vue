<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                @click="emit('close')"
            />

            <div
                class="relative z-10 flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-secondary"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500">
                            Assessment
                        </p>

                        <h2
                            class="mt-0.5 truncate text-lg font-semibold text-gray-900 dark:text-white"
                        >
                            {{ patientName }}
                        </h2>
                    </div>

                    <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
                        @click="emit('close')"
                    >
                        <X class="h-4.5 w-4.5" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-6">
                    <div v-if="assessments.length" class="space-y-4">
                        <section
                            v-for="(assessment, index) in assessments"
                            :key="index"
                            class="rounded-xl border border-gray-100 p-4 dark:border-white/10"
                        >
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                    {{
                                        assessment.recorded_at
                                            ? formatDate(assessment.recorded_at)
                                            : "Undated assessment"
                                    }}
                                </p>

                                <span
                                    v-if="index === 0"
                                    class="rounded-full bg-primary-50 px-2 py-0.5 text-[10px] font-semibold text-primary-600 dark:bg-primary-500/10 dark:text-primary-300"
                                >
                                    Latest
                                </span>
                            </div>

                            <dl class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <div
                                    v-for="field in ASSESSMENT_FIELDS"
                                    :key="field.key"
                                    class="rounded-xl bg-gray-50 px-3 py-2 dark:bg-white/5"
                                >
                                    <dt class="text-[11px] text-gray-400 dark:text-gray-500">
                                        {{ field.label }}
                                    </dt>

                                    <dd
                                        class="mt-0.5 text-sm font-medium text-gray-800 dark:text-white"
                                    >
                                        {{
                                            assessmentLabel(
                                                assessment[field.key],
                                            ) || "—"
                                        }}
                                    </dd>
                                </div>
                            </dl>

                            <div
                                v-if="assessment.life_system_profile"
                                class="mt-4"
                            >
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <p
                                        class="text-xs font-semibold text-gray-700 dark:text-gray-200"
                                    >
                                        Activities of Daily Living
                                    </p>

                                    <span class="text-[11px] text-gray-400 dark:text-gray-500">
                                        0 dependent — 5 independent
                                    </span>
                                </div>

                                <ul class="mt-3 space-y-2.5">
                                    <li
                                        v-for="activity in LIFE_SYSTEM_ACTIVITIES"
                                        :key="activity"
                                    >
                                        <div
                                            class="flex items-center justify-between gap-2"
                                        >
                                            <p
                                                class="text-xs font-medium text-gray-700 dark:text-gray-200"
                                            >
                                                {{ activityLabel(activity) }}
                                            </p>

                                            <p class="text-[11px] text-gray-400 dark:text-gray-500">
                                                {{
                                                    scaleLabel(
                                                        assessment
                                                            .life_system_profile?.[
                                                            activity
                                                        ],
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10"
                                        >
                                            <div
                                                class="h-full rounded-full transition-all"
                                                :class="
                                                    scoreTone(
                                                        assessment
                                                            .life_system_profile?.[
                                                            activity
                                                        ],
                                                    )
                                                "
                                                :style="{
                                                    width: `${scoreWidth(assessment.life_system_profile?.[activity])}%`,
                                                }"
                                            />
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </section>
                    </div>

                    <p v-else class="py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                        No assessment has been recorded yet.
                    </p>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { X } from "lucide-vue-next";
import { formatDate } from "~/utils/time";
import {
    LIFE_SYSTEM_ACTIVITIES,
    LIFE_SYSTEM_SCALE,
    activityLabel,
    assessmentLabel,
} from "~/utils/assessment";
import type { PortalAssessment } from "~/types/patient";

withDefaults(
    defineProps<{
        open?: boolean;
        patientName?: string;
        assessments?: PortalAssessment[];
    }>(),
    {
        open: false,
        patientName: "",
        assessments: () => [],
    },
);

const emit = defineEmits<{ (e: "close"): void }>();

const ASSESSMENT_FIELDS = [
    { key: "condition", label: "Condition" },
    { key: "mental_state", label: "Mental state" },
    { key: "affect", label: "Affect" },
    { key: "behavior", label: "Behavior" },
    { key: "communication", label: "Communication" },
    { key: "speech", label: "Speech" },
] as const;

function scaleLabel(score?: number | null) {
    if (score === undefined || score === null) return "Not assessed";

    return (
        LIFE_SYSTEM_SCALE.find((step) => step.value === score)?.label ??
        String(score)
    );
}

function scoreWidth(score?: number | null) {
    if (score === undefined || score === null) return 0;

    return Math.max(0, Math.min(100, (score / 5) * 100));
}

function scoreTone(score?: number | null) {
    if (score === undefined || score === null) return "bg-gray-200 dark:bg-white/15";
    if (score >= 4) return "bg-emerald-500";
    if (score >= 2) return "bg-amber-400";

    return "bg-rose-400";
}
</script>
