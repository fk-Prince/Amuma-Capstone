<template>
    <section class="rounded-2xl p-8 md:p-10">
        <div class="flex items-baseline gap-3 mb-8">
            <span class="text-2xl text-primary">04</span>
            <div>
                <h2 class="text-xl text-primary">Patient Assessment</h2>
                <p class="text-[13px] text-muted">
                    Current diagnosis, vital status, and mental / cognitive
                    state
                </p>
            </div>
        </div>

        <div class="space-y-10">
            <div class="space-y-6">
                <h3 class="text-sm font-semibold text-slate-700">
                    Current Diagnosis
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <BaseInput
                        label="Primary Diagnosis"
                        :model-value="model.diagnosis"
                        @update:model-value="update('diagnosis', $event)"
                        required
                    />
                    <BaseInput
                        label="Date Diagnosed"
                        :model-value="model.diagnosis_date"
                        @update:model-value="update('diagnosis_date', $event)"
                        placeholder="YYYY-MM-DD"
                    />
                </div>

                <BaseInput
                    label="Notes"
                    :model-value="model.diagnosis_notes"
                    @update:model-value="update('diagnosis_notes', $event)"
                    placeholder="Additional details about the diagnosis"
                    mode="textarea"
                />

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-700">
                        Supporting File
                    </label>
                    <p class="text-[13px] text-muted">
                        Upload a lab result, medical certificate, or report
                        (PDF/image)
                    </p>

                    <div class="flex items-center gap-3">
                        <label
                            class="flex items-center gap-2 h-11 px-4 rounded-lg border border-[#E4E0D6] text-[15px] text-muted cursor-pointer hover:bg-accent transition-colors"
                        >
                            <span>{{
                                model.diagnosis_file_name || "Choose file"
                            }}</span>
                            <input
                                type="file"
                                class="hidden"
                                accept=".pdf,.png,.jpg,.jpeg"
                                @change="onFileChange"
                            />
                        </label>
                        <button
                            v-if="model.diagnosis_file_name"
                            type="button"
                            class="text-[13px] text-danger underline"
                            @click="clearFile"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            </div>

            <div class="h-px bg-[#E4E0D6]" />

            <div class="space-y-6">
                <h3 class="text-sm font-semibold text-slate-700">
                    Vital Status (if known)
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <BaseInput
                        label="Blood Pressure (mmHg)"
                        :model-value="model.blood_pressure"
                        @update:model-value="update('blood_pressure', $event)"
                        placeholder="e.g. 120/80"
                    />

                    <BaseInput
                        label="Pulse Rate (bpm)"
                        :model-value="model.pulse_rate"
                        @update:model-value="update('pulse_rate', $event)"
                        placeholder="e.g. 72"
                    />

                    <BaseInput
                        label="Respiratory Rate (breaths/min)"
                        :model-value="model.respiratory_rate"
                        @update:model-value="update('respiratory_rate', $event)"
                        placeholder="e.g. 16"
                    />

                    <BaseInput
                        label="Temperature (°C)"
                        :model-value="model.temperature"
                        @update:model-value="update('temperature', $event)"
                        mode="number"
                        placeholder="e.g. 36.8"
                    />

                    <BaseInput
                        label="Oxygen Saturation (%)"
                        :model-value="model.oxygen_saturation"
                        @update:model-value="
                            update('oxygen_saturation', $event)
                        "
                        mode="number"
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
                        label="Mental State"
                        placeholder="Select state"
                        :items="[
                            { label: 'Alert', value: 'alert' },
                            { label: 'Confused', value: 'confused' },
                            { label: 'Unconscious', value: 'unconscious' },
                        ]"
                    />
                    <Combobox
                        :model-value="model.memory_issues"
                        @update:model-value="update('memory_issues', $event)"
                        label="Memory Issues"
                        placeholder="Select option"
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
                        label="Mood"
                        placeholder="Select mood"
                        :items="[
                            { label: 'Calm', value: 'calm' },
                            { label: 'Anxious', value: 'anxious' },
                            { label: 'Aggressive', value: 'aggressive' },
                        ]"
                    />

                    <Combobox
                        :model-value="model.communication"
                        @update:model-value="update('communication', $event)"
                        label="Communication"
                        placeholder="Select communication status"
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
                        label="Speech"
                        placeholder="Select speech status"
                        :items="[
                            { label: 'Clear', value: 'clear' },
                            { label: 'Slurred', value: 'slurred' },
                            { label: 'Alphasic', value: 'alphasic' },
                        ]"
                    />
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import BaseInput from "../ui/BaseInput.vue";
import Combobox from "../ui/Combobox.vue";
import type { Assessment } from "~/types/patient";

const props = defineProps<{
    model: Assessment;
}>();

const emit = defineEmits<{
    (e: "update:model", value: Assessment): void;
}>();

function update<K extends keyof Assessment>(key: K, value: Assessment[K]) {
    emit("update:model", {
        ...props.model,
        [key]: value,
    });
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
}

function clearFile() {
    emit("update:model", {
        ...props.model,
        diagnosis_file: undefined,
        diagnosis_file_name: undefined,
    });
}
</script>
