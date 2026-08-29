<!-- components/forms/AssessmentForm.vue -->
<template>
    <section ref="assessmentSection" class="rounded-2xl p-8 md:p-10">
        <div class="flex items-baseline gap-3 mb-8">
            <span class="text-2xl text-primary">04</span>

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
                    Recent diagnosis, vital status, and mental / cognitive state
                    — share whatever you already know
                </p>
            </div>
        </div>

        <div
            v-if="assessments.length > 1"
            class="mb-8 flex flex-wrap items-center gap-2"
        >
            <button
                v-for="(a, index) in assessments"
                :key="index"
                type="button"
                class="group flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition-colors"
                :class="
                    index === activeIndex
                        ? 'bg-primary text-white'
                        : 'bg-slate-50 text-slate-500 hover:bg-slate-100 dark:bg-secondary dark:text-gray-400 dark:border dark:border-white/10 dark:hover:bg-white/5'
                "
                @click="activeIndex = index"
            >
                <span
                    class="flex h-5 w-5 items-center justify-center rounded-full text-[11px] font-semibold"
                    :class="
                        index === activeIndex
                            ? 'bg-white/20 text-white'
                            : 'bg-white text-slate-400 dark:bg-white/10 dark:text-gray-500'
                    "
                >
                    {{ index + 1 }}
                </span>

                Assessment {{ index + 1 }}

                <XIcon
                    v-if="assessments.length > 1"
                    class="h-3.5 w-3.5 opacity-0 transition-opacity group-hover:opacity-100"
                    :class="
                        index === activeIndex
                            ? 'text-white/80 hover:text-white'
                            : 'text-slate-400 hover:text-red-500 dark:text-gray-500'
                    "
                    @click.stop="removeAssessment(index)"
                />
            </button>

            <button
                type="button"
                class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-50 text-slate-400 transition hover:bg-primary/10 hover:text-primary dark:bg-secondary dark:text-gray-500"
                @click="addAssessment"
            >
                <PlusIcon class="h-4 w-4" />
            </button>
        </div>

        <div class="space-y-10">
            <div v-if="activeAssessment" class="space-y-10">
                <div
                    v-if="assessments.length > 1"
                    class="flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-7 w-7 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary"
                        >
                            {{ activeIndex + 1 }}
                        </span>

                        <h3 class="text-sm font-semibold text-slate-700 dark:text-gray-300">
                            Assessment {{ activeIndex + 1 }}
                        </h3>
                    </div>

                    <button
                        type="button"
                        class="text-sm font-medium text-slate-400 transition hover:text-red-500 dark:text-gray-500"
                        @click="removeAssessment(activeIndex)"
                    >
                        Remove
                    </button>
                </div>

                <div class="space-y-6">
                    <h4
                        class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                    >
                        Recent Diagnosis
                    </h4>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <BaseInput
                            label="Primary Diagnosis"
                            :model-value="activeAssessment.diagnosis"
                            @update:model-value="
                                update(activeIndex, 'diagnosis', $event)
                            "
                            :error="errors?.[`diagnosis.${activeIndex}`]"
                            placeholder="e.g. Type 2 Diabetes"
                        />

                        <BaseInput
                            label="Date Diagnosed"
                            :model-value="activeAssessment.diagnosis_date"
                            @update:model-value="
                                update(activeIndex, 'diagnosis_date', $event)
                            "
                            mode="date"
                            :max="todayStr"
                            :error="errors?.[`diagnosis_date.${activeIndex}`]"
                        />
                    </div>

                    <BaseInput
                        label="Diagnosis Notes"
                        :model-value="activeAssessment.diagnosis_notes"
                        @update:model-value="
                            update(activeIndex, 'diagnosis_notes', $event)
                        "
                        :error="errors?.[`diagnosis_notes.${activeIndex}`]"
                        placeholder="Additional details about the diagnosis"
                    />

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold text-slate-700 dark:text-gray-300">
                            Supporting Document
                        </label>

                        <p class="text-[13px] text-muted dark:text-gray-400">
                            Upload a lab result, medical certificate, or report
                            (PDF, PNG, or JPG, up to 10MB)
                        </p>

                        <label
                            class="group flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl p-6 text-center transition-colors border-2 border-dashed border-slate-200 dark:border-white/10"
                            :class="
                                errors?.[`diagnosis_file.${activeIndex}`]
                                    ? 'bg-red-50/70 dark:bg-red-500/10'
                                    : activeAssessment.diagnosis_file_name
                                      ? 'bg-primary/5 dark:bg-primary-500/10'
                                      : 'bg-slate-50 hover:bg-primary/5 dark:bg-white/5 dark:hover:bg-primary-500/10'
                            "
                        >
                            <input
                                type="file"
                                class="hidden"
                                accept=".pdf,.png,.jpg,.jpeg"
                                @change="onFileChange($event, activeIndex)"
                            />

                            <template
                                v-if="activeAssessment.diagnosis_file_name"
                            >
                                <FileText class="h-6 w-6 text-primary" />

                                <span
                                    class="max-w-full truncate px-4 text-sm font-medium text-slate-800 dark:text-white"
                                >
                                    {{ activeAssessment.diagnosis_file_name }}
                                </span>

                                <button
                                    type="button"
                                    class="text-[13px] text-danger underline"
                                    @click.prevent="clearFile(activeIndex)"
                                >
                                    Remove file
                                </button>
                            </template>

                            <template v-else>
                                <UploadCloud
                                    class="h-6 w-6 text-slate-400 transition-colors group-hover:text-primary dark:text-gray-500"
                                />

                                <span class="text-sm text-slate-500 dark:text-gray-400">
                                    <span class="font-medium text-primary">
                                        Click to upload
                                    </span>
                                    or drag a file here
                                </span>
                            </template>
                        </label>

                        <p
                            v-if="errors?.[`diagnosis_file.${activeIndex}`]"
                            class="text-xs text-red-500"
                        >
                            {{ errors[`diagnosis_file.${activeIndex}`] }}
                        </p>
                    </div>
                </div>

                <div class="space-y-6">
                    <h4
                        class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                    >
                        Vital Signs (if known)
                    </h4>

                    <div
                        class="grid grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5"
                    >
                        <BaseInput
                            label="Blood Pressure (mmHg)"
                            :model-value="activeAssessment.blood_pressure"
                            @update:model-value="
                                update(activeIndex, 'blood_pressure', $event)
                            "
                            :error="errors?.[`blood_pressure.${activeIndex}`]"
                            placeholder="e.g. 120/80"
                        />

                        <BaseInput
                            label="Pulse Rate (bpm)"
                            :model-value="activeAssessment.pulse_rate"
                            @update:model-value="
                                update(activeIndex, 'pulse_rate', $event)
                            "
                            :error="errors?.[`pulse_rate.${activeIndex}`]"
                            mode="number"
                            placeholder="e.g. 72"
                        />

                        <BaseInput
                            label="Respiratory Rate (breaths/min)"
                            :model-value="activeAssessment.respiratory_rate"
                            @update:model-value="
                                update(activeIndex, 'respiratory_rate', $event)
                            "
                            :error="errors?.[`respiratory_rate.${activeIndex}`]"
                            mode="number"
                            placeholder="e.g. 16"
                        />

                        <BaseInput
                            label="Temperature (°C)"
                            :model-value="activeAssessment.temperature"
                            @update:model-value="
                                update(activeIndex, 'temperature', $event)
                            "
                            :error="errors?.[`temperature.${activeIndex}`]"
                            mode="number"
                            placeholder="e.g. 36.8"
                        />

                        <BaseInput
                            label="Oxygen Saturation, SpO2 (%)"
                            :model-value="activeAssessment.oxygen_saturation"
                            @update:model-value="
                                update(activeIndex, 'oxygen_saturation', $event)
                            "
                            :error="
                                errors?.[`oxygen_saturation.${activeIndex}`]
                            "
                            mode="number"
                            max="100"
                            placeholder="e.g. 98"
                        />
                    </div>
                </div>

                <div class="space-y-6">
                    <h4
                        class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                    >
                        Mental / Cognitive State
                    </h4>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
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
                                { label: 'Confused', value: 'confused' },
                                {
                                    label: 'Unconscious',
                                    value: 'unconscious',
                                },
                            ]"
                        />

                        <Combobox
                            :model-value="activeAssessment.memory_issues"
                            @update:model-value="
                                update(activeIndex, 'memory_issues', $event)
                            "
                            label="Memory / Cognitive Issues"
                            placeholder="Select option"
                            :error="errors?.[`memory_issues.${activeIndex}`]"
                            :items="[
                                { label: 'None', value: 'none' },
                                {
                                    label: 'Mild forgetfulness',
                                    value: 'mild',
                                },
                                { label: 'Dementia', value: 'dementia' },
                                {
                                    label: 'Alzheimer\'s',
                                    value: 'alzheimers',
                                },
                            ]"
                        />

                        <Combobox
                            :model-value="activeAssessment.mood"
                            @update:model-value="
                                update(activeIndex, 'mood', $event)
                            "
                            label="Mood / Behavior"
                            placeholder="Select mood"
                            :error="errors?.[`mood.${activeIndex}`]"
                            :items="[
                                { label: 'Calm', value: 'calm' },
                                { label: 'Anxious', value: 'anxious' },
                                {
                                    label: 'Aggressive',
                                    value: 'aggressive',
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
                                { label: 'Aphasic', value: 'alphasic' },
                            ]"
                        />
                    </div>
                </div>
            </div>

            <button
                type="button"
                class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-200 py-4 text-sm font-semibold text-primary transition hover:border-primary/40 hover:bg-primary/5 dark:border-white/10"
                @click="
                    addAssessment();
                    scrollToAssessmentTop();
                "
            >
                <span class="text-xl leading-none">+</span>
                Add Another Assessment
            </button>
        </div>
    </section>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from "vue";
import {
    FileText,
    UploadCloud,
    Plus as PlusIcon,
    X as XIcon,
} from "lucide-vue-next";
import BaseInput from "../ui/BaseInput.vue";
import Combobox from "../ui/Combobox.vue";
import type { Assessment } from "~/types/patient";

const props = defineProps<{
    model: Assessment[];
    errors?: Record<string, string> | null;
}>();

const emit = defineEmits<{
    (e: "update:model", value: Assessment[]): void;
    (e: "update:errors", value: Record<string, string>): void;
}>();

const todayStr = new Date().toISOString().split("T")[0];

function createAssessment(): Assessment {
    return {
        diagnosis: "",
        diagnosis_date: "",
        diagnosis_notes: "",
        diagnosis_file: undefined,
        diagnosis_file_name: undefined,
        blood_pressure: "",
        pulse_rate: "",
        respiratory_rate: "",
        temperature: "",
        oxygen_saturation: "",
        mental_state: "alert",
        memory_issues: "none",
        mood: "calm",
        communication: "Coherent & Logical",
        speech: "clear",
    };
}

const assessments = ref<Assessment[]>(
    props.model && props.model.length > 0
        ? props.model.map((a) => ({ ...createAssessment(), ...a }))
        : [createAssessment()],
);

const activeIndex = ref(0);

const activeAssessment = computed(() => assessments.value[activeIndex.value]);

watch(
    () => props.model,
    (val) => {
        if (!val || val.length === 0) return;
        if (val === assessments.value) return;
        assessments.value = val.map((a) => ({ ...createAssessment(), ...a }));
        if (activeIndex.value > assessments.value.length - 1) {
            activeIndex.value = assessments.value.length - 1;
        }
    },
);

function update<K extends keyof Assessment>(
    index: number,
    key: K,
    value: Assessment[K],
) {
    assessments.value[index] = {
        ...assessments.value[index],
        [key]: value,
    };

    emit("update:model", [...assessments.value]);

    clearError(`${String(key)}.${index}`);
}

watch(
    () => props.errors,
    (val) => {
        if (!val || Object.keys(val).length === 0) return;

        const errorIndices = new Set<number>();

        Object.keys(val).forEach((key) => {
            const index = parseInt(key.split(".").pop() || "");
            if (!isNaN(index)) {
                errorIndices.add(index);
            }
        });

        if (errorIndices.size > 0) {
            const firstErrorIndex = Math.min(...Array.from(errorIndices));
            activeIndex.value = firstErrorIndex;
        }
    },
    { deep: true },
);

function addAssessment() {
    assessments.value.push(createAssessment());
    activeIndex.value = assessments.value.length - 1;

    emit("update:model", [...assessments.value]);
}

function removeAssessment(index: number) {
    if (assessments.value.length <= 1) return;

    assessments.value.splice(index, 1);

    if (activeIndex.value >= assessments.value.length) {
        activeIndex.value = assessments.value.length - 1;
    } else if (activeIndex.value > index) {
        activeIndex.value -= 1;
    }

    emit("update:model", [...assessments.value]);
}

function clearError(field: string) {
    if (!props.errors) return;

    const updated = { ...props.errors };

    delete updated[field];

    emit("update:errors", updated);
}

function onFileChange(event: Event, index: number) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) return;

    if (file.size > 10 * 1024 * 1024) {
        const updated = {
            ...(props.errors ?? {}),
            [`diagnosis_file.${index}`]: "File size must not exceed 10MB.",
        };

        emit("update:errors", updated);

        input.value = "";
        return;
    }

    const allowedTypes = ["application/pdf", "image/png", "image/jpeg"];

    if (!allowedTypes.includes(file.type)) {
        const updated = {
            ...(props.errors ?? {}),
            [`diagnosis_file.${index}`]:
                "Only PDF, PNG, and JPG files are allowed.",
        };

        emit("update:errors", updated);

        input.value = "";
        return;
    }

    assessments.value[index] = {
        ...assessments.value[index],
        diagnosis_file: file,
        diagnosis_file_name: file.name,
    };

    emit("update:model", [...assessments.value]);

    clearError(`diagnosis_file.${index}`);
}

function clearFile(index: number) {
    assessments.value[index] = {
        ...assessments.value[index],
        diagnosis_file: undefined,
        diagnosis_file_name: undefined,
    };

    emit("update:model", [...assessments.value]);
}
const assessmentSection = ref<HTMLElement | null>(null);
async function scrollToAssessmentTop() {
    await nextTick();

    assessmentSection.value?.scrollIntoView({
        behavior: "smooth",
        block: "start",
    });
}
</script>
