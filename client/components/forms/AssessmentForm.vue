<!-- components/forms/AssessmentForm.vue -->
<template>
    <section ref="assessmentSection" class="rounded-2xl p-8 md:p-10">
        <div class="flex items-baseline gap-3 mb-8">
            <span class="text-2xl text-primary">05</span>

            <div>
                <h2 class="text-xl text-primary">
                    Patient Assessment
                    <span
                        class="ml-1 align-middle text-[11px] font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500"
                    >
                        (Optional)
                    </span>
                </h2>

                <p class="text-[13px] text-muted dark:text-gray-400">
                    Condition, mental state, and how much help daily activities
                    need — share whatever you already know
                </p>
            </div>
        </div>

        <div class="space-y-10">
            <div class="space-y-6">
                <h4
                    class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                >
                    Condition &amp; Mental / Cognitive State
                </h4>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <Combobox
                        :model-value="activeAssessment.condition"
                        @update:model-value="
                            update(activeIndex, 'condition', $event)
                        "
                        label="Mobility"
                        placeholder="Select condition"
                        :error="errors?.[`condition.${activeIndex}`]"
                        :items="[
                            { label: 'Ambulatory', value: 'ambulatory' },
                            { label: 'Wheelchair', value: 'wheelchair' },
                            { label: 'Stretcher', value: 'stretcher' },
                        ]"
                    />
                    <Combobox
                        :model-value="activeAssessment.mental_state"
                        @update:model-value="
                            update(activeIndex, 'mental_state', $event)
                        "
                        label="Level of Consciousness"
                        placeholder="Select state"
                        :error="errors?.[`mental_state.${activeIndex}`]"
                        :items="[
                            { label: 'Alert', value: 'alert' },
                            { label: 'Drowsy', value: 'drowsy' },
                            { label: 'Lethargic', value: 'lethargic' },
                            {
                                label: 'Forgetfulness',
                                value: 'forgetfulness',
                            },
                        ]"
                    />

                    <Combobox
                        :model-value="activeAssessment.affect"
                        @update:model-value="
                            update(activeIndex, 'affect', $event)
                        "
                        label="Affect"
                        placeholder="Select affect"
                        :error="errors?.[`affect.${activeIndex}`]"
                        :items="[
                            { label: 'Cheerful', value: 'cheerful' },
                            { label: 'Flat', value: 'flat' },
                            { label: 'Tearful', value: 'tearful' },
                            { label: 'Depressed', value: 'depressed' },
                            { label: 'Angry', value: 'angry' },
                        ]"
                    />

                    <Combobox
                        :model-value="activeAssessment.behavior"
                        @update:model-value="
                            update(activeIndex, 'behavior', $event)
                        "
                        label="Behavior"
                        placeholder="Select behavior"
                        :error="errors?.[`behavior.${activeIndex}`]"
                        :items="[
                            { label: 'Cooperative', value: 'cooperative' },
                            {
                                label: 'Uncooperative',
                                value: 'uncooperative',
                            },
                            {
                                label: 'Lack of interaction',
                                value: 'lack_of_interaction',
                            },
                            {
                                label: 'Communication barrier',
                                value: 'communication_barrier',
                            },
                        ]"
                    />

                    <Combobox
                        :model-value="activeAssessment.communication"
                        @update:model-value="
                            update(activeIndex, 'communication', $event)
                        "
                        label="Communication Ability"
                        placeholder="Select communication status"
                        :error="errors?.[`communication.${activeIndex}`]"
                        :items="[
                            {
                                label: 'Coherent & Logical',
                                value: 'Coherent & Logical',
                            },
                            { label: 'Impaired', value: 'Impaired' },
                        ]"
                    />

                    <Combobox
                        :model-value="activeAssessment.speech"
                        @update:model-value="
                            update(activeIndex, 'speech', $event)
                        "
                        label="Speech Pattern"
                        placeholder="Select speech status"
                        :error="errors?.[`speech.${activeIndex}`]"
                        :items="[
                            { label: 'Clear', value: 'clear' },
                            { label: 'Slurred', value: 'slurred' },
                            { label: 'Aphasic', value: 'aphasic' },
                        ]"
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
                        Rate how much help the patient needs with each daily
                        activity.
                    </p>
                </div>

                <ul
                    class="flex flex-wrap gap-x-4 gap-y-1 rounded-xl bg-slate-50 px-4 py-3 text-[11px] text-slate-500 dark:bg-white/5 dark:text-gray-400"
                >
                    <li v-for="step in LIFE_SYSTEM_SCALE" :key="step.value">
                        <span
                            class="font-bold text-slate-700 dark:text-gray-300"
                        >
                            {{ step.value }}
                        </span>
                        — {{ step.label }}
                    </li>
                </ul>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <Combobox
                        v-for="activity in LIFE_SYSTEM_ACTIVITIES"
                        :key="activity"
                        :model-value="lifeSystemValue(activity)"
                        @update:model-value="
                            updateLifeSystem(activeIndex, activity, $event)
                        "
                        :label="activityLabel(activity)"
                        placeholder="Select score"
                        :items="lifeSystemItems"
                    />
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import Combobox from "../ui/Combobox.vue";
import type {
    Assessment,
    LifeSystemActivity,
    LifeSystemProfile,
    LifeSystemScore,
} from "~/types/patient";
import {
    LIFE_SYSTEM_ACTIVITIES,
    LIFE_SYSTEM_SCALE,
    activityLabel,
    lifeSystemItems,
} from "~/utils/assessment";

const props = defineProps<{
    model: Assessment[];
    errors?: Record<string, string> | null;
}>();

const emit = defineEmits<{
    (e: "update:model", value: Assessment[]): void;
    (e: "update:errors", value: Record<string, string>): void;
}>();

function createAssessment(): Assessment {
    return {
        condition: "ambulatory",
        mental_state: "alert",
        affect: "cheerful",
        behavior: "cooperative",
        communication: "Coherent & Logical",
        speech: "clear",
        life_system_profile: Object.fromEntries(
            LIFE_SYSTEM_ACTIVITIES.map((activity) => [activity, 5]),
        ) as LifeSystemProfile,
    };
}

// The model stays an array of one so callers keep the same payload shape.
const assessment = ref<Assessment>({
    ...createAssessment(),
    ...(props.model?.[0] ?? {}),
});

const activeIndex = ref(0);

const activeAssessment = computed(() => assessment.value);

watch(
    () => props.model,
    (val) => {
        if (!val || val.length === 0) return;
        if (val[0] === assessment.value) return;

        assessment.value = { ...createAssessment(), ...val[0] };
    },
);

function update<K extends keyof Assessment>(
    _index: number,
    key: K,
    value: Assessment[K],
) {
    assessment.value = { ...assessment.value, [key]: value };

    emit("update:model", [{ ...assessment.value }]);
    clearError(`${String(key)}.0`);
}

function lifeSystemValue(activity: LifeSystemActivity) {
    return assessment.value.life_system_profile?.[activity] ?? 5;
}

function updateLifeSystem(
    _index: number,
    activity: LifeSystemActivity,
    value: LifeSystemScore,
) {
    update(0, "life_system_profile", {
        ...(assessment.value.life_system_profile ?? {}),
        [activity]: value,
    } as LifeSystemProfile);
}

function clearError(field: string) {
    if (!props.errors) return;

    const updated = { ...props.errors };

    delete updated[field];

    emit("update:errors", updated);
}
</script>
