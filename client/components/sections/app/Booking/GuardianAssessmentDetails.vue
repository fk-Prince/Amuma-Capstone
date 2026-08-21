<template>
    <section v-if="guardian">
        <h3
            class="mb-4 flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-[#0E7C7B]"
        >
            <svg
                class="h-3.5 w-3.5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
                <circle cx="10" cy="7" r="4" />
                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            </svg>
            Guardian
        </h3>

        <div class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
            <Field
                label="Full Name"
                :value="
                    fullName(
                        guardian.first_name,
                        guardian.middle_name,
                        guardian.last_name,
                    )
                "
            />

            <Field label="Phone Number" :value="guardian.phone_number" />
            <Field label="Email" :value="guardian.email" />
            <Field label="Address" :value="guardian.address" />
            <Field label="Occupation" :value="guardian.occupation" />
            <Field label="Relationship" :value="guardian.relationship" />
        </div>
    </section>

    <section v-if="hasAssessment">
        <h3
            class="mb-4 flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-[#0E7C7B]"
        >
            <svg
                class="h-3.5 w-3.5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M9 11l3 3L22 4" />
                <path
                    d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"
                />
            </svg>
            Assessment
        </h3>

        <div
            v-if="assessments.length > 1"
            class="mb-6 flex flex-wrap items-center gap-2"
        >
            <button
                v-for="(assessmentItem, index) in assessments"
                :key="index"
                type="button"
                class="group flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition-colors"
                :class="
                    index === activeIndex
                        ? 'bg-primary text-white'
                        : 'bg-slate-50 text-slate-500 hover:bg-slate-100'
                "
                @click="activeIndex = index"
            >
                <span
                    class="flex h-5 w-5 items-center justify-center rounded-full text-[11px] font-semibold"
                    :class="
                        index === activeIndex
                            ? 'bg-white/20 text-white'
                            : 'bg-white text-slate-400'
                    "
                >
                    {{ index + 1 }}
                </span>

                Assessment {{ index + 1 }}
            </button>
        </div>

        <div v-if="activeAssessment" class="space-y-8">
            <div class="space-y-4">
                <h5
                    class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                >
                    Recent Diagnosis / Supporting Document
                </h5>

                <div
                    class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2"
                >
                    <Field
                        label="Diagnosis"
                        :value="activeAssessment.diagnosis"
                    />

                    <Field
                        label="Diagnosis Date"
                        :value="activeAssessment.diagnosis_date"
                    />

                    <Field
                        label="Diagnosis Notes"
                        :value="activeAssessment.diagnosis_notes"
                    />

                    <Field
                        label="Diagnosis File"
                        :value="activeAssessment.diagnosis_file_name"
                    >
                        <template #value>
                            <a
                                v-if="activeAssessment.diagnosis_file"
                                :href="diagnosisFileUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-primary underline hover:text-primary/70"
                            >
                                {{
                                    activeAssessment.diagnosis_file_name ||
                                    "View file"
                                }}
                            </a>

                            <span v-else>
                                {{
                                    activeAssessment.diagnosis_file_name || "—"
                                }}
                            </span>
                        </template>
                    </Field>
                </div>
            </div>

            <div class="space-y-4">
                <h5
                    class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                >
                    Vital Signs
                </h5>

                <div
                    class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2"
                >
                    <Field
                        label="Blood Pressure"
                        :value="activeAssessment.blood_pressure"
                    />

                    <Field
                        label="Pulse Rate"
                        :value="activeAssessment.pulse_rate"
                    />

                    <Field
                        label="Respiratory Rate"
                        :value="activeAssessment.respiratory_rate"
                    />

                    <Field
                        label="Temperature"
                        :value="activeAssessment.temperature"
                    />

                    <Field
                        label="Oxygen Saturation"
                        :value="activeAssessment.oxygen_saturation"
                    />
                </div>
            </div>

            <div class="space-y-4">
                <h5
                    class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                >
                    Mental / Cognitive State
                </h5>

                <div
                    class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2"
                >
                    <Field
                        label="Mental State"
                        :value="activeAssessment.mental_state"
                    />

                    <Field
                        label="Memory Issues"
                        :value="activeAssessment.memory_issues"
                    />

                    <Field label="Mood" :value="activeAssessment.mood" />

                    <Field
                        label="Communication"
                        :value="activeAssessment.communication"
                    />

                    <Field label="Speech" :value="activeAssessment.speech" />
                </div>
            </div>
        </div>
    </section>
</template>

<script lang="ts" setup>
import { computed, onBeforeUnmount, ref, watch } from "vue";
import type { BookingRetrieve } from "~/types/booking";
import { fullName } from "~/utils/user";
import { Field } from "~/utils/fields";

const props = defineProps<{
    booking: BookingRetrieve;
}>();

const guardian = computed(() => props.booking?.guardian ?? null);

const assessments = computed<any[]>(() => {
    const value = props.booking?.assessment;

    if (!value) {
        return [];
    }

    if (Array.isArray(value)) {
        return value;
    }

    if (typeof value === "object") {
        return [value];
    }

    return [];
});

const activeIndex = ref(0);

const activeAssessment = computed(() => {
    return assessments.value[activeIndex.value] ?? null;
});

const hasAssessment = computed(() => assessments.value.length > 0);

watch(
    assessments,
    (list) => {
        if (!list.length) {
            activeIndex.value = 0;
            return;
        }

        if (activeIndex.value > list.length - 1) {
            activeIndex.value = list.length - 1;
        }
    },
    {
        immediate: true,
    },
);

const diagnosisFileUrl = computed(() => {
    const file = activeAssessment.value?.diagnosis_file;

    if (!file) {
        return "";
    }

    if (typeof file === "string") {
        return file;
    }

    if (file instanceof File) {
        return URL.createObjectURL(file);
    }

    return "";
});

onBeforeUnmount(() => {
    const url = diagnosisFileUrl.value;

    if (url && url.startsWith("blob:")) {
        URL.revokeObjectURL(url);
    }
});
</script>
