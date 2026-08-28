<template>
    <section class="p-8 md:p-10">
        <div class="flex items-baseline gap-3 mb-8">
            <span class="text-2xl text-primary">02</span>
            <div>
                <h2 class="text-xl text-primary">Patient Information</h2>
                <p class="text-[13px] text-muted">
                    Details about the person receiving care
                </p>
            </div>
        </div>

        <div class="space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <BaseInput
                    label="First Name"
                    :model-value="model.first_name"
                    @update:model-value="update('first_name', $event)"
                    :error="errors?.first_name"
                    required
                />
                <BaseInput
                    label="Middle Name"
                    :model-value="model.middle_name"
                    @update:model-value="update('middle_name', $event)"
                    :error="errors?.middle_name"
                />
                <BaseInput
                    label="Last Name"
                    :model-value="model.last_name"
                    @update:model-value="update('last_name', $event)"
                    :error="errors?.last_name"
                    required
                />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-700">
                        Gender <span class="text-danger">*</span>
                    </label>
                    <div
                        class="inline-flex h-11 items-center rounded-lg border border-slate-200 bg-slate-50 p-1"
                    >
                        <button
                            type="button"
                            @click="update('gender', 'Female')"
                            class="flex-1 h-full rounded-md px-4 text-sm font-medium transition-colors"
                            :class="
                                model.gender === 'Female'
                                    ? 'bg-white text-primary shadow-sm'
                                    : 'text-muted hover:text-slate-700'
                            "
                        >
                            Female
                        </button>
                        <button
                            type="button"
                            @click="update('gender', 'Male')"
                            class="flex-1 h-full rounded-md px-4 text-sm font-medium transition-colors"
                            :class="
                                model.gender === 'Male'
                                    ? 'bg-white text-primary shadow-sm'
                                    : 'text-muted hover:text-slate-700'
                            "
                        >
                            Male
                        </button>
                    </div>
                    <p v-if="errors?.gender" class="text-xs text-red-500">
                        {{ errors.gender }}
                    </p>
                </div>

                <DatePickerField
                    label="Date of Birth"
                    :model-value="model.date_of_birth"
                    :max="todayStr"
                    :default-to-today="false"
                    @update:model-value="update('date_of_birth', $event)"
                    placeholder="Select date of birth"
                    :error="errors?.date_of_birth"
                    required
                />
                <PhoneInput
                    label="Phone Number"
                    :model-value="model.phone_number"
                    @update:model-value="update('phone_number', $event)"
                    :error="errors?.phone_number"
                />
            </div>

            <!-- The patient's own home address. For homecare this is kept
                 separate from the service location, since the caregiver may
                 be sent somewhere other than where the patient lives. -->
            <div class="grid grid-cols-1 gap-6">
                <BaseInput
                    :label="
                        category === 'facility' ? 'Address' : 'Home Address'
                    "
                    :model-value="props.model.address"
                    @update:model-value="update('address', $event)"
                    :error="errors?.address"
                    required
                />

                <p
                    v-if="category !== 'facility'"
                    class="-mt-4 text-[11px] text-slate-400"
                >
                    Where the patient lives. The address the caregiver visits
                    is set separately in the service schedule below.
                </p>
            </div>

            <div class="h-px bg-[#E4E0D6]" />

            <div class="grid grid-cols-3 gap-6">
                <BaseInput
                    label="Citizenship"
                    :model-value="model.citizenship"
                    @update:model-value="update('citizenship', $event)"
                    :error="errors?.citizenship"
                    required
                />
                <BaseInput
                    label="Occupation"
                    :model-value="model.occupation"
                    @update:model-value="update('occupation', $event)"
                    :error="errors?.occupation"
                    required
                />

                <Combobox
                    :model-value="model.marital_status"
                    @update:model-value="update('marital_status', $event)"
                    label="Marital Status"
                    required
                    placeholder="Select status"
                    :error="errors?.marital_status"
                    :items="[
                        { label: 'Single', value: 'single' },
                        { label: 'Married', value: 'married' },
                        { label: 'Divorced', value: 'divorced' },
                        { label: 'Widowed', value: 'widowed' },
                    ]"
                />
            </div>

            <div class="grid grid-cols-3 max-w-[20rem] gap-6">
                <BaseInput
                    label="Height (cm)"
                    :model-value="model.height"
                    @update:model-value="update('height', $event)"
                    mode="number"
                    input-class="text-center"
                    :error="errors?.height"
                />
                <BaseInput
                    label="Weight (kg)"
                    :model-value="model.weight"
                    @update:model-value="update('weight', $event)"
                    mode="number"
                    input-class="text-center"
                    :error="errors?.weight"
                />
                <Combobox
                    :model-value="model.blood_type"
                    @update:model-value="update('blood_type', $event)"
                    label="Blood Type"
                    placeholder="Select"
                    :error="errors?.blood_type"
                    :items="[
                        { label: 'A+', value: 'A+' },
                        { label: 'A-', value: 'A-' },
                        { label: 'B+', value: 'B+' },
                        { label: 'B-', value: 'B-' },
                        { label: 'AB+', value: 'AB+' },
                        { label: 'AB-', value: 'AB-' },
                        { label: 'O+', value: 'O+' },
                        { label: 'O-', value: 'O-' },
                    ]"
                />
            </div>

            <BaseInput
                label="Allergies"
                :model-value="model.allergies"
                @update:model-value="update('allergies', $event)"
                placeholder="e.g. Penicillin, Peanuts, Latex"
                :error="errors?.allergies"
            />
        </div>
    </section>
</template>

<script setup lang="ts">
import BaseInput from "../ui/BaseInput.vue";
import PhoneInput from "../ui/PhoneInput.vue";
import Combobox from "../ui/Combobox.vue";
import DatePickerField from "../ui/DatePickerField.vue";
import type { Patient } from "~/types/patient";
import { getLocalDateStr } from "~/utils/time";
const props = defineProps<{
    model: Patient;
    errors?: Record<string, string> | null;
    category?: string;
}>();

const emit = defineEmits<{
    (e: "update:model", value: Patient): void;
    (e: "update:errors", value: Record<string, string>): void;
}>();

function update<K extends keyof Patient>(key: K, value: Patient[K]) {
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

const todayStr = new Date().toISOString().split("T")[0];
</script>
