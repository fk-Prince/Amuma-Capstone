<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
        >
            <div
                class="w-full max-w-xl bg-white rounded-2xl shadow-xl border border-[#E4EFED]"
            >
                <div class="flex justify-between items-center p-5 border-b">
                    <div>
                        <h2 class="text-lg font-semibold text-[#16302E]">
                            Create Homecare Package
                        </h2>

                        <p class="text-sm text-[#9AB3AF]">
                            Configure a new caregiving service package.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="close"
                        class="text-gray-400 hover:text-gray-700"
                    >
                        ✕
                    </button>
                </div>

                <form class="p-5 space-y-4" @submit.prevent="submit">
                    <Combobox
                        :model-value="form.type"
                        @update:model-value="update('type', $event)"
                        label="Service Type"
                        placeholder="Select service type"
                        :items="serviceTypes"
                        :error="errors.type"
                        required
                    />

                    <Combobox
                        :model-value="form.billing_interval"
                        @update:model-value="update('billing_interval', $event)"
                        label="Billing Interval"
                        placeholder="Select billing interval"
                        :items="billingIntervals"
                        :error="errors.billing_interval"
                        required
                    />

                    <BaseInput
                        :model-value="form.price"
                        @update:model-value="update('price', $event)"
                        label="Hourly Rate"
                        mode="number"
                        placeholder="0.00"
                        :error="errors.price"
                        required
                    />

                    <BaseInput
                        :model-value="form.description"
                        @update:model-value="update('description', $event)"
                        label="Description"
                        placeholder="Enter service package description"
                        :error="errors.description"
                        mode="textarea"
                    />

                    <div class="grid grid-cols-2 gap-3 pt-3">
                        <button
                            type="button"
                            class="rounded-xl border px-4 py-3 text-sm"
                            @click="close"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="rounded-xl bg-primary text-white px-4 py-3 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <svg
                                v-if="isSubmitting"
                                class="w-4 h-4 animate-spin"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                />

                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                />
                            </svg>

                            {{
                                isSubmitting ? "Creating..." : "Create Package"
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { reactive, computed, ref } from "vue";
import { useRoute } from "vue-router";

import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";

import { branchContractService } from "~/api/branch-contract/BranchContractService";
import { homecarePlanForm, homecarePlanSchema } from "~/types/contract";
import { useToast } from "~/composables/useToast";

const { success, error } = useToast();

const route = useRoute();

const isSubmitting = ref(false);

defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    close: [];
}>();

const serviceTypes = [
    {
        label: "Activities of Daily Living (ADL) Services",
        value: "ADL",
    },
];

const billingIntervals = [
    {
        label: "Hourly",
        value: "HOURLY",
    },
];

const uuid = computed(() => route.params.uuid as string);

const form = reactive({
    ...homecarePlanForm(),
    description: "",
    branch_uuid: uuid.value,
});

const errors = reactive<Record<string, string>>({
    type: "",
    price: "",
    billing_interval: "",
    description: "",
    general: "",
});

function update<K extends keyof typeof form>(key: K, value: (typeof form)[K]) {
    form[key] = value;
    clearError(String(key));
}

function clearError(field: string) {
    errors[field] = "";
}

function clearErrors() {
    Object.keys(errors).forEach((key) => {
        errors[key] = "";
    });
}

function close() {
    if (isSubmitting.value) return;

    clearErrors();
    emit("close");
}

async function submit() {
    clearErrors();

    const payload = {
        ...form,
        price: Number(form.price),
        description: form.description || null,
    };

    const result = homecarePlanSchema.safeParse(payload);

    if (!result.success) {
        const formatted = result.error.flatten().fieldErrors;

        Object.entries(formatted).forEach(([key, value]) => {
            errors[key] = value?.[0] ?? "";
        });

        return;
    }

    try {
        isSubmitting.value = true;

        const res = await branchContractService.create(payload);

        success(res.message ?? "Homecare package created successfully");

        close();
    } catch (err: any) {
        const apiErrors = err?.errors || err?.response?.data?.errors;

        if (apiErrors) {
            Object.assign(
                errors,
                Object.fromEntries(
                    Object.entries(apiErrors).map(([key, value]: any) => [
                        key,
                        Array.isArray(value) ? value[0] : value,
                    ]),
                ),
            );
        } else {
            error(
                err?.response?.data?.message ??
                    err?.message ??
                    "Failed to create homecare package",
            );
        }
    } finally {
        isSubmitting.value = false;
    }
}
</script>
