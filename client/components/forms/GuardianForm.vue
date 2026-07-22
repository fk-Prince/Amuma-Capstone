<template>
    <section class="rounded-2xl p-8 md:p-10">
        <div class="flex gap-3 mb-8 items-baseline">
            <span class="text-2xl text-primary">03</span>
            <div>
                <h2 class="text-xl text-primary">Guardian Information</h2>
                <p class="text-[13px] text-muted">
                    The person responsible for this patient
                </p>
            </div>
        </div>

        <div class="space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <BaseInput
                    label="First Name"
                    :model-value="props.model.first_name"
                    @update:model-value="update('first_name', $event)"
                    :error="errors?.first_name"
                    :disabled="lockedFields.first_name"
                    required
                />
                <BaseInput
                    label="Middle Name"
                    :model-value="props.model.middle_name"
                    @update:model-value="update('middle_name', $event)"
                    :error="errors?.middle_name"
                    :disabled="lockedFields.middle_name"
                />
                <BaseInput
                    label="Last Name"
                    :model-value="props.model.last_name"
                    @update:model-value="update('last_name', $event)"
                    :error="errors?.last_name"
                    :disabled="lockedFields.last_name"
                    required
                />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <BaseInput
                    label="Phone Number"
                    :model-value="props.model.phone_number"
                    @update:model-value="update('phone_number', $event)"
                    :error="errors?.phone_number"
                    :disabled="lockedFields.phone_number"
                    required
                />
                <BaseInput
                    label="Email"
                    :model-value="props.model.email"
                    @update:model-value="update('email', $event)"
                    :error="errors?.email"
                    :disabled="lockedFields.email"
                    required
                />
            </div>

            <div class="grid grid-cols-1 gap-6">
                <BaseInput
                    label="Address"
                    :model-value="props.model.address"
                    @update:model-value="update('address', $event)"
                    :error="errors?.address"
                    :disabled="lockedFields.address"
                    required
                />
            </div>
            <div class="h-px bg-[#E4E0D6]" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <BaseInput
                    label="Relationship to Patient"
                    :model-value="props.model.relationship"
                    @update:model-value="update('relationship', $event)"
                    placeholder="e.g. Father, Mother, Guardian"
                    :error="errors?.relationship"
                    :disabled="lockedFields.relationship"
                    required
                />
                <BaseInput
                    label="Occupation"
                    :model-value="props.model.occupation"
                    @update:model-value="update('occupation', $event)"
                    :error="errors?.occupation"
                    :disabled="lockedFields.occupation"
                    required
                />
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { reactive, onMounted } from "vue";
import BaseInput from "@/components/ui/BaseInput.vue";
import type { Guardian } from "~/types/patient";
import type { User } from "~/types/auth";

const props = defineProps<{
    model: Guardian;
    currentUser?: User | null;
    errors?: Record<string, string> | null;
}>();

const emit = defineEmits<{
    (e: "update:model", value: Guardian): void;
    (e: "update:errors", value: Record<string, string>): void;
}>();

function update<K extends keyof Guardian>(key: K, value: Guardian[K]) {
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

const lockedFields = reactive({
    first_name: false,
    middle_name: false,
    last_name: false,
    phone_number: false,
    email: false,
    address: false,
    relationship: false,
    occupation: false,
});

onMounted(() => {
    const updates: Partial<Guardian> = {};

    if (props.currentUser?.first_name) {
        updates.first_name = props.currentUser.first_name;
    }
    if (props.currentUser?.last_name) {
        updates.last_name = props.currentUser.last_name;
    }
    if (props.currentUser?.email) {
        updates.email = props.currentUser.email;
    }
    if (props.currentUser?.phone_number) {
        updates.phone_number = props.currentUser.phone_number;
    }

    const merged = { ...props.model, ...updates };

    (Object.keys(lockedFields) as (keyof typeof lockedFields)[]).forEach(
        (key) => {
            lockedFields[key] = !!merged[key as keyof Guardian];
        },
    );

    if (Object.keys(updates).length) {
        emit("update:model", merged);
    }
});
</script>
