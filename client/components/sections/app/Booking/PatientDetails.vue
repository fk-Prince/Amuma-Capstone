<template>
    <section>
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
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
            Patient Information
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <Field
                label="Name"
                :value="
                    fullName(
                        patient?.first_name,
                        patient?.middle_name,
                        patient?.last_name,
                    )
                "
            />
            <Field label="Gender" :value="patient?.gender" />
            <Field
                label="Birth Date"
                :value="formatDate(patient?.date_of_birth)"
            />
            <Field label="Blood Type" :value="patient?.blood_type" />
            <Field label="Phone" :value="patient?.phone_number" />
            <Field label="Occupation" :value="patient?.occupation" />
            <Field label="Allergies" :value="patient?.allergies" />
            <!-- The patient's own home address. The homecare visit address is
                 a separate thing and lives in the service section. -->
            <Field label="Home Address" :value="patient?.address" />
            <Field
                class="normal-case"
                label="Height / Weight"
                :value="
                    [
                        patient?.height ? `${patient.height} cm` : '',
                        patient?.weight ? `${patient.weight} kg` : '',
                    ]
                        .filter(Boolean)
                        .join(' / ')
                "
            />
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

const patient = computed(() => props.booking?.patient ?? null);
</script>
