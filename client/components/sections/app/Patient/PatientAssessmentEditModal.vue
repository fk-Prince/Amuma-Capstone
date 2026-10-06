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
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
        >
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" />

            <form
                class="relative z-50 flex max-h-[90dvh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary"
                @submit.prevent="submit"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-4 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <p
                            class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500"
                        >
                            Edit assessment
                        </p>

                        <h2
                            class="mt-1 truncate text-lg font-semibold text-slate-800 dark:text-white"
                        >
                            {{ patientName }}
                        </h2>
                    </div>

                    <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                        @click="close"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 space-y-6 overflow-y-auto p-6">
                    <div class="space-y-4">
                        <h4
                            class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                        >
                            Condition &amp; Mental / Cognitive State
                        </h4>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                            <Combobox
                                v-for="field in stateFields"
                                :key="field.key"
                                :label="field.label"
                                :placeholder="field.placeholder"
                                :model-value="form[field.key]"
                                :error="errors[field.key]"
                                :items="assessmentOptions[field.key]"
                                @update:model-value="set(field.key, $event)"
                            />
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <h4
                                class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                            >
                                Life System Profile
                            </h4>

                            <p class="mt-1 text-[13px] text-muted dark:text-gray-400">
                                Rate how much help the patient needs with each
                                daily activity.
                            </p>
                        </div>

                        <ul
                            class="flex flex-wrap gap-x-4 gap-y-1 rounded-xl bg-slate-50 px-4 py-3 text-[11px] text-slate-500 dark:bg-white/5 dark:text-gray-400"
                        >
                            <li v-for="step in LIFE_SYSTEM_SCALE" :key="step.value">
                                <span class="font-bold text-slate-700 dark:text-gray-300">
                                    {{ step.value }}
                                </span>
                                — {{ step.label }}
                            </li>
                        </ul>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <Combobox
                                v-for="activity in LIFE_SYSTEM_ACTIVITIES"
                                :key="activity"
                                :label="activityLabel(activity)"
                                placeholder="Select score"
                                :model-value="profile[activity]"
                                :error="errors[`life_system_profile.${activity}`]"
                                :items="lifeSystemItems"
                                @update:model-value="setScore(activity, $event)"
                            />
                        </div>
                    </div>
                </div>

                <div
                    class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-100 px-6 py-4 dark:border-white/10"
                >
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-white/10"
                        @click="close"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        :disabled="saving"
                        class="flex items-center gap-2 rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <Loader2 v-if="saving" class="h-4 w-4 animate-spin" />
                        {{ saving ? "Saving..." : "Save changes" }}
                    </button>
                </div>
            </form>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { reactive, ref, watch } from "vue";
import { Loader2, X } from "lucide-vue-next";
import Combobox from "~/components/ui/Combobox.vue";
import { patientService } from "~/api/patient/PatientService";
import { useToast } from "~/composables/useToast";
import type { LifeSystemActivity, LifeSystemProfile } from "~/types/patient";
import {
    LIFE_SYSTEM_ACTIVITIES,
    LIFE_SYSTEM_SCALE,
    activityLabel,
    assessmentOptions,
    lifeSystemItems,
} from "~/utils/assessment";

type StateKey = keyof typeof assessmentOptions;

interface AssessmentEntry {
    uuid: string;
    condition: string | null;
    mental_state: string | null;
    affect: string | null;
    behavior: string | null;
    communication: string | null;
    speech: string | null;
    life_system_profile: LifeSystemProfile | null;
}

const stateFields: { key: StateKey; label: string; placeholder: string }[] = [
    { key: "condition", label: "Mobility", placeholder: "Select condition" },
    {
        key: "mental_state",
        label: "Level of Consciousness",
        placeholder: "Select state",
    },
    { key: "affect", label: "Affect", placeholder: "Select affect" },
    { key: "behavior", label: "Behavior", placeholder: "Select behavior" },
    {
        key: "communication",
        label: "Communication Ability",
        placeholder: "Select communication status",
    },
    { key: "speech", label: "Speech Pattern", placeholder: "Select speech status" },
];

const props = defineProps<{
    open: boolean;
    assessment: AssessmentEntry | null;
    patientUuid: string;
    patientName: string;
    branchUuid: string;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "saved", assessment: AssessmentEntry): void;
}>();

const { success, error } = useToast();

const saving = ref(false);
const errors = ref<Record<string, string>>({});

const form = reactive<Record<StateKey, string>>({
    condition: "",
    mental_state: "",
    affect: "",
    behavior: "",
    communication: "",
    speech: "",
});

const profile = reactive<Record<LifeSystemActivity, number>>({
    bathing: 5,
    transferring: 5,
    toileting: 5,
    grooming: 5,
    eating: 5,
    locomotion: 5,
    dressing: 5,
});

function fill() {
    const entry = props.assessment;

    stateFields.forEach(({ key }) => {
        form[key] = entry?.[key] ?? "";
    });

    LIFE_SYSTEM_ACTIVITIES.forEach((activity) => {
        profile[activity] = Number(entry?.life_system_profile?.[activity] ?? 5);
    });

    errors.value = {};
}

function set(key: StateKey, value: string) {
    form[key] = value;
    delete errors.value[key];
}

function setScore(activity: LifeSystemActivity, value: number) {
    profile[activity] = Number(value);
    delete errors.value[`life_system_profile.${activity}`];
}

async function submit() {
    if (saving.value || !props.assessment) return;

    errors.value = {};

    stateFields.forEach(({ key, label }) => {
        if (!form[key]) errors.value[key] = `${label} is required`;
    });

    if (Object.keys(errors.value).length) return;

    saving.value = true;

    try {
        const res: any = await patientService.updateAssessment(
            props.patientUuid,
            props.assessment.uuid,
            {
                branch_uuid: props.branchUuid,
                ...form,
                life_system_profile: { ...profile },
            },
        );

        success(res?.message ?? "Assessment updated.");
        emit("saved", res.assessment);
        close();
    } catch (err: any) {
        const fieldErrors = err?.errors ?? {};

        errors.value = Object.fromEntries(
            Object.entries(fieldErrors).map(([key, value]: any) => [
                key,
                Array.isArray(value) ? value[0] : value,
            ]),
        );

        error(err?.message ?? "Unable to update this assessment.");
    } finally {
        saving.value = false;
    }
}

function close() {
    emit("close");
}

watch(
    () => props.open,
    (open) => {
        if (open) fill();
    },
    { immediate: true },
);
</script>
