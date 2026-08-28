<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { Stethoscope } from "lucide-vue-next";
import type { PatientRetrieve } from "~/types/patient";
import { formatDate } from "~/utils/time";

const props = defineProps<{
    patient: PatientRetrieve;
}>();

const assessments = computed(() => {
    const value = props.patient.assessment;

    if (!value) return [];

    if (Array.isArray(value)) return value;

    if (typeof value === "object") return [value];

    return [];
});

const activeAssessmentIndex = ref(0);

watch(assessments, (list) => {
    if (!list.length) {
        activeAssessmentIndex.value = 0;
        return;
    }

    if (activeAssessmentIndex.value > list.length - 1) {
        activeAssessmentIndex.value = list.length - 1;
    }
});

const activeAssessment = computed(
    () => assessments.value[activeAssessmentIndex.value] ?? null,
);
</script>

<template>
    <section class="rounded-2xl bg-white p-6 shadow-sm">
        <div class="flex items-center gap-2">
            <Stethoscope class="h-4 w-4 text-primary" />
            <h3 class="font-semibold text-secondary">Assessment</h3>
        </div>

        <p
            v-if="!assessments.length"
            class="mt-4 text-sm text-muted"
        >
            No assessment recorded.
        </p>

        <template v-else>
            <div
                v-if="assessments.length > 1"
                class="mt-4 flex flex-wrap items-center gap-2"
            >
                <button
                    v-for="(assessment, index) in assessments"
                    :key="index"
                    type="button"
                    class="rounded-full px-3 py-1.5 text-xs font-medium transition-colors"
                    :class="
                        activeAssessmentIndex === index
                            ? 'bg-primary text-white'
                            : 'bg-muted-light/60 text-secondary hover:bg-muted-light'
                    "
                    @click="activeAssessmentIndex = index"
                >
                    Assessment {{ index + 1 }}
                </button>
            </div>

            <div v-if="activeAssessment" class="mt-5 space-y-6">
                <div>
                    <h5
                        class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted"
                    >
                        Diagnosis
                    </h5>

                    <div class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                        <div>
                            <p class="text-xs text-muted">Diagnosis</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.diagnosis || "—" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted">Diagnosis Date</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{
                                    formatDate(activeAssessment.diagnosis_date)
                                }}
                            </p>
                        </div>

                        <div class="sm:col-span-2">
                            <p class="text-xs text-muted">Diagnosis Notes</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.diagnosis_notes || "—" }}
                            </p>
                        </div>
                    </div>
                </div>

                <div>
                    <h5
                        class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted"
                    >
                        Vital Signs
                    </h5>

                    <div
                        class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm sm:grid-cols-3"
                    >
                        <div>
                            <p class="text-xs text-muted">Blood Pressure</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.blood_pressure || "—" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted">Pulse Rate</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.pulse_rate || "—" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted">Respiratory Rate</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.respiratory_rate || "—" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted">Temperature</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.temperature || "—" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted">Oxygen Saturation</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.oxygen_saturation || "—" }}
                            </p>
                        </div>
                    </div>
                </div>

                <div>
                    <h5
                        class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted"
                    >
                        Mental / Cognitive State
                    </h5>

                    <div class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                        <div>
                            <p class="text-xs text-muted">Mental State</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.mental_state || "—" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted">Mood</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.mood || "—" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted">Memory Issues</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.memory_issues || "—" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted">Communication</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.communication || "—" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted">Speech</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.speech || "—" }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </section>
</template>
