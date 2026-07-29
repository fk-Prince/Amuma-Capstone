<template>
    <section class="rounded-2xl p-8 md:p-10">
        <div class="flex items-baseline gap-3 mb-8">
            <span class="text-2xl text-primary">04</span>
            <div>
                <h2 class="text-xl text-primary">
                    Patient Assessment
                    <span
                        class="ml-1 align-middle text-[11px] font-medium uppercase tracking-wide text-slate-400"
                        >(Optional)</span
                    >
                </h2>
                <p class="text-[13px] text-muted">
                    Recent diagnosis, vital status, and mental / cognitive state
                    — share whatever you already know
                </p>
            </div>
        </div>

        <div class="space-y-10">
            <div class="space-y-6">
                <h3 class="text-sm font-semibold text-slate-700">
                    Recent Diagnosis
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <BaseInput
                        label="Primary Diagnosis"
                        :model-value="model.diagnosis"
                        @update:model-value="update('diagnosis', $event)"
                        :error="errors?.diagnosis"
                        placeholder="e.g. Type 2 Diabetes"
                    />
                    <BaseInput
                        label="Date Diagnosed"
                        :model-value="model.diagnosis_date"
                        @update:model-value="update('diagnosis_date', $event)"
                        mode="date"
                        :max="todayStr"
                        :error="errors?.diagnosis_date"
                    />
                </div>

                <BaseInput
                    label="Diagnosis Notes"
                    :model-value="model.diagnosis_notes"
                    @update:model-value="update('diagnosis_notes', $event)"
                    :error="errors?.diagnosis_notes"
                    placeholder="Additional details about the diagnosis"
                />

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-700">
                        Supporting Document
                    </label>
                    <p class="text-[13px] text-muted">
                        Upload a lab result, medical certificate, or report
                        (PDF, PNG, or JPG, up to 10MB)
                    </p>

                    <label
                        class="group flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed p-6 text-center transition-colors cursor-pointer"
                        :class="
                            errors?.diagnosis_file
                                ? 'border-red-300 bg-red-50'
                                : model.diagnosis_file_name
                                  ? 'border-primary/40 bg-primary/5'
                                  : 'border-slate-200 hover:border-primary/40 hover:bg-primary/5'
                        "
                    >
                        <input
                            type="file"
                            class="hidden"
                            accept=".pdf,.png,.jpg,.jpeg"
                            @change="onFileChange"
                        />

                        <template v-if="model.diagnosis_file_name">
                            <FileText class="h-6 w-6 text-primary" />
                            <span
                                class="text-sm font-medium text-slate-800 max-w-full truncate px-4"
                            >
                                {{ model.diagnosis_file_name }}
                            </span>
                            <button
                                type="button"
                                class="text-[13px] text-danger underline"
                                @click.prevent="clearFile"
                            >
                                Remove file
                            </button>
                        </template>

                        <template v-else>
                            <UploadCloud
                                class="h-6 w-6 text-slate-400 group-hover:text-primary transition-colors"
                            />
                            <span class="text-sm text-slate-500">
                                <span class="font-medium text-primary"
                                    >Click to upload</span
                                >
                                or drag a file here
                            </span>
                        </template>
                    </label>

                    <p
                        v-if="errors?.diagnosis_file"
                        class="text-xs text-red-500"
                    >
                        {{ errors.diagnosis_file }}
                    </p>
                </div>
            </div>

            <div class="h-px bg-[#E4E0D6]" />

            <div class="space-y-6">
                <h3 class="text-sm font-semibold text-slate-700">
                    Vital Signs (if known)
                </h3>

                <div
                    class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6"
                >
                    <BaseInput
                        label="Blood Pressure (mmHg)"
                        :model-value="model.blood_pressure"
                        @update:model-value="update('blood_pressure', $event)"
                        :error="errors?.blood_pressure"
                        placeholder="e.g. 120/80"
                    />

                    <BaseInput
                        label="Pulse Rate (bpm)"
                        :model-value="model.pulse_rate"
                        @update:model-value="update('pulse_rate', $event)"
                        :error="errors?.pulse_rate"
                        mode="number"
                        placeholder="e.g. 72"
                    />

                    <BaseInput
                        label="Respiratory Rate (breaths/min)"
                        :model-value="model.respiratory_rate"
                        @update:model-value="update('respiratory_rate', $event)"
                        :error="errors?.respiratory_rate"
                        mode="number"
                        placeholder="e.g. 16"
                    />

                    <BaseInput
                        label="Temperature (°C)"
                        :model-value="model.temperature"
                        @update:model-value="update('temperature', $event)"
                        :error="errors?.temperature"
                        mode="number"
                        placeholder="e.g. 36.8"
                    />

                    <BaseInput
                        label="Oxygen Saturation, SpO2 (%)"
                        :model-value="model.oxygen_saturation"
                        @update:model-value="
                            update('oxygen_saturation', $event)
                        "
                        :error="errors?.oxygen_saturation"
                        mode="number"
                        max="100"
                        placeholder="e.g. 98"
                    />
                </div>
            </div>

            <div class="h-px bg-[#E4E0D6]" />

            <div class="space-y-6">
                <h3 class="text-sm font-semibold text-slate-700">
                    Mental / Cognitive State
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Combobox
                        :model-value="model.mental_state"
                        @update:model-value="update('mental_state', $event)"
                        label="Level of Consciousness"
                        placeholder="Select state"
                        :error="errors?.mental_state"
                        :items="[
                            { label: 'Alert', value: 'alert' },
                            { label: 'Confused', value: 'confused' },
                            { label: 'Unconscious', value: 'unconscious' },
                        ]"
                    />
                    <Combobox
                        :model-value="model.memory_issues"
                        @update:model-value="update('memory_issues', $event)"
                        label="Memory / Cognitive Issues"
                        placeholder="Select option"
                        :error="errors?.memory_issues"
                        :items="[
                            { label: 'None', value: 'none' },
                            { label: 'Mild forgetfulness', value: 'mild' },
                            { label: 'Dementia', value: 'dementia' },
                            { label: 'Alzheimer\'s', value: 'alzheimers' },
                        ]"
                    />
                    <Combobox
                        :model-value="model.mood"
                        @update:model-value="update('mood', $event)"
                        label="Mood / Behavior"
                        placeholder="Select mood"
                        :error="errors?.mood"
                        :items="[
                            { label: 'Calm', value: 'calm' },
                            { label: 'Anxious', value: 'anxious' },
                            { label: 'Aggressive', value: 'aggressive' },
                        ]"
                    />

                    <Combobox
                        :model-value="model.communication"
                        @update:model-value="update('communication', $event)"
                        label="Communication Ability"
                        placeholder="Select communication status"
                        :error="errors?.communication"
                        :items="[
                            {
                                label: 'Coherent & Logical',
                                value: 'Coherent & Logical',
                            },
                            { label: 'Impaired', value: 'Impaired' },
                        ]"
                    />

                    <Combobox
                        :model-value="model.speech"
                        @update:model-value="update('speech', $event)"
                        label="Speech Pattern"
                        placeholder="Select speech status"
                        :error="errors?.speech"
                        :items="[
                            { label: 'Clear', value: 'clear' },
                            { label: 'Slurred', value: 'slurred' },
                            { label: 'Aphasic', value: 'alphasic' },
                        ]"
                    />
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { FileText, UploadCloud } from "lucide-vue-next";
import BaseInput from "../ui/BaseInput.vue";
import Combobox from "../ui/Combobox.vue";
import type { Assessment } from "~/types/patient";

const props = defineProps<{
    model: Assessment;
    errors?: Record<string, string> | null;
}>();

const emit = defineEmits<{
    (e: "update:model", value: Assessment): void;
    (e: "update:errors", value: Record<string, string>): void;
}>();

const todayStr = new Date().toISOString().split("T")[0];

function update<K extends keyof Assessment>(key: K, value: Assessment[K]) {
    emit("update:model", {
        ...props.model,
        [key]: value,
    });

    clearError(key as string);
}

function clearError(field: string) {
    if (!props.errors) return;

    const updated = { ...props.errors };
    delete updated[field];

    emit("update:errors", updated);
}

function onFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    emit("update:model", {
        ...props.model,
        diagnosis_file: file,
        diagnosis_file_name: file.name,
    });

    clearError("diagnosis_file");
}

function clearFile() {
    emit("update:model", {
        ...props.model,
        diagnosis_file: undefined,
        diagnosis_file_name: undefined,
    });
}
</script>
