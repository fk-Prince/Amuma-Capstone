<template>
    <section class="rounded-2xl p-8 md:p-10">
        <div class="flex gap-3 mb-8 justify-between items-center">
            <div class="flex gap-3 items-baseline">
                <span class="text-2xl text-primary">03</span>
                <div>
                    <h2 class="text-xl text-primary">Guardian Information</h2>
                    <p class="text-[13px] text-muted">
                        The person responsible for this patient
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 px-4 py-3 w-fit">
                <input
                    id="fillWithData"
                    type="checkbox"
                    v-model="useMyCredentials"
                    class="w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary/30"
                />
                <label
                    for="fillWithData"
                    class="text-sm cursor-pointer select-none"
                >
                    Use my credentials
                </label>
            </div>
        </div>

        <div class="space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <BaseInput
                    label="First Name"
                    :model-value="props.model.first_name"
                    @update:model-value="update('first_name', $event)"
                    required
                />
                <BaseInput
                    label="Middle Name"
                    :model-value="props.model.middle_name"
                    @update:model-value="update('middle_name', $event)"
                />
                <BaseInput
                    label="Last Name"
                    :model-value="props.model.last_name"
                    @update:model-value="update('last_name', $event)"
                    required
                />
            </div>

            <div class="h-px bg-[#E4E0D6]" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <BaseInput
                    label="Phone Number"
                    :model-value="props.model.phone_number"
                    @update:model-value="update('phone_number', $event)"
                    required
                />
                <BaseInput
                    label="Email"
                    :model-value="props.model.email"
                    @update:model-value="update('email', $event)"
                />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <BaseInput
                    label="Relationship to Patient"
                    :model-value="props.model.relationship"
                    @update:model-value="update('relationship', $event)"
                    placeholder="e.g. Father, Mother, Guardian"
                    required
                />
                <BaseInput
                    label="Occupation"
                    :model-value="props.model.occupation"
                    @update:model-value="update('occupation', $event)"
                />
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import BaseInput from "@/components/ui/BaseInput.vue";
import type { Guardian, User } from "~/types/auth";

const props = defineProps<{
    model: Guardian;
    currentUser?: User | null;
}>();

const emit = defineEmits<{
    (e: "update:model", value: Guardian): void;
}>();

function update<K extends keyof Guardian>(key: K, value: Guardian[K]) {
    emit("update:model", {
        ...props.model,
        [key]: value,
    });
}

const useMyCredentials = ref(false);

watch(useMyCredentials, (val) => {
    if (val && props.currentUser) {
        emit("update:model", {
            ...props.model,
            first_name: props.currentUser.first_name,
            middle_name: "",
            last_name: props.currentUser.last_name,
            phone_number: props.currentUser.phone_number ?? "",
            email: props.currentUser.email,
        });
    } else {
        emit("update:model", {
            ...props.model,
            first_name: "",
            middle_name: "",
            last_name: "",
            phone_number: "",
            email: "",
        });
    }
});
</script>
