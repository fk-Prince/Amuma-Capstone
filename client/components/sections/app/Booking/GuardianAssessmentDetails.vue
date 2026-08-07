<template>
    <section v-if="guardian">
        <h3
            class="flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-[#0E7C7B] mb-4"
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

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <Field
                label="Name"
                :value="
                    fullName(
                        guardian.first_name,
                        guardian.middle_name,
                        guardian.last_name,
                    )
                "
            />
            <Field label="Relationship" :value="guardian.relationship" />
            <Field label="Phone Number" :value="guardian.phone_number" />
            <Field label="Email" :value="guardian.email" />
            <Field label="Occupation" :value="guardian.occupation" />
            <Field label="Address" :value="guardian.address" />
        </div>
    </section>

    <section v-if="hasAssessment">
        <h3
            class="flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-[#0E7C7B] mb-4"
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

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <template v-for="(value, key) in assessment" :key="key">
                <Field
                    v-if="String(key) !== 'diagnosis_file'"
                    :label="formatLabel(String(key))"
                    :value="
                        String(key) === 'diagnosis_file_name'
                            ? undefined
                            : value
                    "
                >
                    <template
                        v-if="String(key) === 'diagnosis_file_name'"
                        #value
                    >
                        <a
                            v-if="assessment?.diagnosis_file"
                            :href="diagnosisFileUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-primary underline hover:text-primary/70"
                        >
                            {{ value || "View file" }}
                        </a>
                        <span v-else>{{ value || "—" }}</span>
                    </template>
                </Field>
            </template>
        </div>
    </section>
</template>
<script lang="ts" setup>
import type { BookingRetrieve } from "~/types/booking";
import { fullName } from "~/utils/user";
import { Field } from "~/utils/fields";

const props = defineProps<{
    booking: BookingRetrieve;
}>();

const guardian = computed(() => props.booking?.guardian ?? null);
const assessment = computed(() => props.booking?.assessment ?? null);

const diagnosisFileUrl = computed(() => {
    const file = assessment.value?.diagnosis_file;

    if (!file) return "";

    if (typeof file === "string") {
        return file;
    }

    return URL.createObjectURL(file);
});

function formatLabel(key: string) {
    return String(key)
        .replace(/_/g, " ")
        .replace(/([a-z])([A-Z])/g, "$1 $2")
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

const hasAssessment = computed(
    () => !!assessment.value && Object.keys(assessment.value).length > 0,
);
</script>
