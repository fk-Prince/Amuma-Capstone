<template>
    <section v-if="guardian">
        <h3
            class="mb-4 flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-[#0E7C7B] dark:text-accent-300"
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

    <section v-if="hasDiagnoses">
        <h3
            class="mb-4 flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-[#0E7C7B] dark:text-accent-300"
        >
            <FileText class="h-3.5 w-3.5" />
            Diagnosis
        </h3>

        <div class="space-y-6">
            <div
                v-for="(entry, index) in diagnoses"
                :key="index"
                class="border-b border-slate-100 pb-5 last:border-b-0 last:pb-0 dark:border-white/10"
            >
                <p
                    v-if="diagnoses.length > 1"
                    class="mb-3 text-xs font-semibold text-slate-600 dark:text-gray-400"
                >
                    Diagnosis {{ index + 1 }}
                </p>

                <div
                    class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2"
                >
                    <Field label="Diagnosis" :value="entry.diagnosis" />

                    <Field
                        label="Diagnosis Date"
                        :value="formatDate(entry.diagnosis_date)"
                    />

                    <Field
                        label="Diagnosis Notes"
                        :value="entry.diagnosis_notes"
                    />

                    <Field
                        label="Diagnosis File"
                        :value="entry.diagnosis_file_name"
                    >
                        <template #value>
                            <a
                                v-if="entry.diagnosis_file"
                                :href="entry.diagnosis_file"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-primary underline hover:text-primary/70"
                            >
                                {{ entry.diagnosis_file_name || "View file" }}
                            </a>

                            <span v-else>
                                {{ entry.diagnosis_file_name || "—" }}
                            </span>
                        </template>
                    </Field>
                </div>
            </div>
        </div>
    </section>

    <section v-if="hasAssessment">
        <h3
            class="mb-4 flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-[#0E7C7B] dark:text-accent-300"
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
                        : 'bg-slate-50 text-slate-500 hover:bg-slate-100 dark:bg-white/5 dark:text-gray-400 dark:hover:bg-white/10'
                "
                @click="activeIndex = index"
            >
                <span
                    class="flex h-5 w-5 items-center justify-center rounded-full text-[11px] font-semibold"
                    :class="
                        index === activeIndex
                            ? 'bg-white/20 text-white'
                            : 'bg-white text-slate-400 dark:bg-secondary dark:text-gray-500'
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
                    class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                >
                    Condition &amp; Mental / Cognitive State
                </h5>

                <div
                    class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2"
                >
                    <Field
                        label="Mobility"
                        :value="activeAssessment.condition"
                    />

                    <Field
                        label="Level of Consciousness"
                        :value="activeAssessment.mental_state"
                    />

                    <Field label="Affect" :value="activeAssessment.affect" />

                    <Field
                        label="Behavior"
                        :value="activeAssessment.behavior"
                    />

                    <Field
                        label="Communication"
                        :value="activeAssessment.communication"
                    />

                    <Field label="Speech" :value="activeAssessment.speech" />
                </div>
            </div>

            <div v-if="activeAssessment.life_system_profile" class="space-y-4">
                <h5
                    class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                >
                    Life System Profile
                </h5>

                <div
                    class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2"
                >
                    <Field
                        v-for="activity in LIFE_SYSTEM_ACTIVITIES"
                        :key="activity"
                        :label="activityLabel(activity)"
                        :value="
                            lifeSystemLabel(
                                activeAssessment.life_system_profile?.[
                                    activity
                                ],
                            )
                        "
                    />
                </div>
            </div>
        </div>
    </section>
</template>

<script lang="ts" setup>
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { FileText } from "lucide-vue-next";
import type { BookingRetrieve } from "~/types/booking";
import { fullName } from "~/utils/user";
import { formatDate } from "~/utils/time";
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

// Diagnoses moved out of the assessment; older bookings still carry them
// inside it, so those are read back as a single-entry list.
const diagnoses = computed<any[]>(() => {
    const provided = (props.booking as any)?.diagnoses;

    if (Array.isArray(provided) && provided.length) return provided;

    return assessments.value.filter(
        (entry) => entry?.diagnosis || entry?.diagnosis_notes,
    );
});

const hasDiagnoses = computed(() => diagnoses.value.length > 0);

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
