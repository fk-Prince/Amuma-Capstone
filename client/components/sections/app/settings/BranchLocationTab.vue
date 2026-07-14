<template>
    <div class="space-y-6">
        <div class="grid md:grid-cols-2 gap-4">
            <BaseInput v-model="location.street" label="Street" required />
            <BaseInput v-model="location.city" label="City" required />
            <BaseInput v-model="location.province" label="Province" required />
            <BaseInput v-model="location.country" label="Country" required />
        </div>

        <div class="flex items-center gap-3">
            <button
                type="button"
                class="btn-primary"
                :disabled="saving"
                @click="$emit('save')"
            >
                {{ saving ? "Saving..." : "Update Location" }}
            </button>

            <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
            <p v-if="success" class="text-sm text-green-600">Saved.</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import BaseInput from "~/components/ui/BaseInput.vue";

defineProps<{
    location: {
        street: string;
        city: string;
        province: string;
        country: string;
    };
    saving: boolean;
    error: string;
    success: boolean;
}>();

defineEmits<{
    (e: "save"): void;
}>();
</script>
