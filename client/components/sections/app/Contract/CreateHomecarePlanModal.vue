<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm p-4 font-sans animate-fade-in"
        >
            <div
                class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden animate-slide-up"
            >
                <div
                    class="sticky top-0 bg-gradient-to-r from-slate-50 to-blue-50 px-6 py-5 border-b border-gray-200 flex items-start justify-between gap-4"
                >
                    <div class="flex-1">
                        <h2 class="text-xl font-bold text-gray-900">
                            {{
                                isEditMode
                                    ? "Update Homecare Package"
                                    : "Create Homecare Package"
                            }}
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            {{
                                isEditMode
                                    ? "Update this caregiving service package"
                                    : "Configure a new caregiving service package"
                            }}
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="close"
                        :disabled="isSubmitting"
                        class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors disabled:opacity-50"
                        aria-label="Close dialog"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form
                    class="p-6 space-y-6 max-h-[calc(100vh-200px)] overflow-y-auto"
                    @submit.prevent="submit"
                >
                    <div>
                        <Combobox
                            :model-value="form.accommodation_type"
                            @update:model-value="
                                update('accommodation_type', $event)
                            "
                            label="Service Type"
                            placeholder="Select service type"
                            :items="serviceTypes"
                            :error="errors.accommodation_type"
                            required
                        />
                        <p class="text-xs text-gray-500 mt-2 ml-1">
                            Choose the type of activities-of-daily-living
                            service provided
                        </p>
                    </div>

                    <div>
                        <Combobox
                            :model-value="form.billing_cycle"
                            @update:model-value="
                                update('billing_cycle', $event)
                            "
                            label="Billing Cycle"
                            placeholder="Select billing cycle"
                            :items="billingIntervals"
                            :error="errors.billing_cycle"
                            required
                        />
                        <p class="text-xs text-gray-500 mt-2 ml-1">
                            Homecare packages are billed hourly based on visits
                            logged by the assigned caregiver
                        </p>
                    </div>

                    <!-- Price -->
                    <div>
                        <BaseInput
                            :model-value="form.price"
                            @update:model-value="update('price', $event)"
                            label="Hourly Rate"
                            mode="number"
                            placeholder="0.00"
                            :error="errors.price"
                            required
                        />
                        <p class="text-xs text-gray-500 mt-2 ml-1">
                            Set the hourly rate charged for this service
                        </p>
                    </div>

                    <div
                        class="bg-gray-50 rounded-xl p-4 border border-gray-200"
                    >
                        <div class="space-y-3">
                            <BaseInput
                                :model-value="form.description"
                                @update:model-value="
                                    update('description', $event)
                                "
                                label="Description"
                                placeholder="Describe the service, visit frequency, and caregiver support..."
                                :error="errors.description"
                                mode="textarea"
                            />

                            <!-- Suggestions -->
                            <div
                                v-if="
                                    existingPackageDescription ||
                                    suggestedDescription
                                "
                                class="space-y-2"
                            >
                                <div
                                    v-if="existingPackageDescription"
                                    class="flex items-start gap-3 p-3 bg-white rounded-lg border-2 border-blue-200 cursor-pointer hover:border-blue-300 hover:bg-blue-50 transition-all group"
                                    @click="useExistingDescription"
                                    role="button"
                                    tabindex="0"
                                    @keydown.enter="useExistingDescription"
                                    @keydown.space="useExistingDescription"
                                >
                                    <div
                                        class="flex-shrink-0 w-5 h-5 mt-1 text-blue-600"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 111.414 1.414L7.414 8l3.293 3.293a1 1 0 01-1.414 1.414l-4-4z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p
                                            class="text-sm font-semibold text-gray-900 mb-1"
                                        >
                                            Current Description
                                        </p>
                                        <p
                                            class="text-sm text-gray-700 line-clamp-2"
                                        >
                                            {{ existingPackageDescription }}
                                        </p>
                                        <p
                                            class="text-xs text-blue-600 font-medium mt-1"
                                        >
                                            Click to reuse
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-else-if="suggestedDescription"
                                    class="flex items-start gap-3 p-3 bg-white rounded-lg border-2 border-green-200 cursor-pointer hover:border-green-300 hover:bg-green-50 transition-all group"
                                    @click="useSuggestedDescription"
                                    role="button"
                                    tabindex="0"
                                    @keydown.enter="useSuggestedDescription"
                                    @keydown.space="useSuggestedDescription"
                                >
                                    <div
                                        class="flex-shrink-0 w-5 h-5 mt-1 text-green-600"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                            />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p
                                            class="text-sm font-semibold text-gray-900 mb-1"
                                        >
                                            Suggested for
                                            {{ serviceTypeLabel }}
                                        </p>
                                        <p
                                            class="text-sm text-gray-700 line-clamp-2"
                                        >
                                            {{ suggestedDescription }}
                                        </p>
                                        <p
                                            class="text-xs text-green-600 font-medium mt-1"
                                        >
                                            Click to use this suggestion
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="generalError"
                        class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-lg"
                    >
                        <div class="flex-shrink-0 w-5 h-5 text-red-600 mt-0.5">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-red-700">
                            {{ generalError }}
                        </p>
                    </div>
                </form>

                <div
                    class="sticky bottom-0 bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-end gap-3"
                >
                    <button
                        type="button"
                        @click="close"
                        :disabled="isSubmitting"
                        class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        @click="submit"
                        :disabled="isSubmitting"
                        class="px-6 py-2.5 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 disabled:bg-blue-400 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
                    >
                        <svg
                            v-if="isSubmitting"
                            class="w-4 h-4 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
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
                            isSubmitting
                                ? isEditMode
                                    ? "Updating..."
                                    : "Creating..."
                                : isEditMode
                                  ? "Update Package"
                                  : "Create Package"
                        }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { reactive, computed, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { X } from "lucide-vue-next";

import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";

import { branchContractService } from "~/api/branch-contract/BranchContractService";
import { homecarePlanForm, homecarePlanSchema } from "~/types/contract";
import { useToast } from "~/composables/useToast";

const { success, error } = useToast();
const route = useRoute();

const isSubmitting = ref(false);

const props = defineProps<{
    open: boolean;
    data?: any;
    allPlans?: any[];
}>();

const emit = defineEmits<{
    close: [];
    saved: [plan: any];
}>();

// Constants
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
const isEditMode = computed(() => !!props.data?.branch_contract_id);

const serviceTypeLabel = computed(() => {
    const match = serviceTypes.find(
        (type) => type.value === form.accommodation_type,
    );
    return match?.label || form.accommodation_type;
});

const generalError = computed(() => errors.general);

const existingPackageDescription = computed(() => {
    return isEditMode.value ? props.data?.description : null;
});

const suggestedDescription = computed(() => {
    if (
        isEditMode.value ||
        !form.accommodation_type ||
        !props.allPlans ||
        props.allPlans.length === 0
    ) {
        return null;
    }

    const matchingPlan = props.allPlans.find(
        (plan: any) =>
            plan.accommodation_type === form.accommodation_type &&
            plan.category &&
            plan.category.toLowerCase() === "homecare",
    );

    return matchingPlan?.description || null;
});

const form = reactive({
    ...homecarePlanForm(),
    description: "",
    branch_uuid: uuid.value,
});

const errors = reactive<Record<string, string>>({
    accommodation_type: "",
    price: "",
    billing_cycle: "",
    description: "",
    general: "",
});

watch(
    () => props.data,
    (newData) => {
        Object.assign(form, homecarePlanForm(), newData ?? {}, {
            branch_uuid: uuid.value,
        });

        clearErrors();
    },
    {
        immediate: true,
    },
);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            document.body.style.overflow = "hidden";
        } else {
            document.body.style.overflow = "auto";
        }
    },
);

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
    resetForm();
    emit("close");
}

function resetForm() {
    Object.assign(form, homecarePlanForm(), {
        description: "",
        branch_uuid: uuid.value,
    });

    clearErrors();
}

function useExistingDescription() {
    if (existingPackageDescription.value) {
        form.description = existingPackageDescription.value;
        clearError("description");
    }
}

function useSuggestedDescription() {
    if (suggestedDescription.value) {
        form.description = suggestedDescription.value;
        clearError("description");
    }
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

        const res = isEditMode.value
            ? await branchContractService.update(
                  props.data.branch_contract_id,
                  payload,
              )
            : await branchContractService.create(payload);

        success(
            res.message ??
                (isEditMode.value
                    ? "Homecare package updated successfully"
                    : "Homecare package created successfully"),
        );

        const savedPlan = res.data ?? res;

        if (props.allPlans && savedPlan) {
            const index = props.allPlans.findIndex(
                (plan: any) =>
                    plan.branch_contract_id === savedPlan.branch_contract_id,
            );

            if (index !== -1) {
                props.allPlans.splice(index, 1, savedPlan);
            } else {
                props.allPlans.push(savedPlan);
            }
        }

        emit("saved", savedPlan);
        close();
    } catch (err: any) {
        const message =
            err?.data?.message ||
            err?.response?.data?.message ||
            err?.message ||
            (isEditMode.value
                ? "Failed to update homecare package"
                : "Failed to create homecare package");

        const apiErrors = err?.data?.errors || err?.response?.data?.errors;

        console.error(err);

        if (apiErrors && Object.keys(apiErrors).length > 0) {
            Object.entries(apiErrors).forEach(([key, value]: any) => {
                errors[key] = Array.isArray(value) ? value[0] : value;
            });
        } else {
            errors.general = message;
        }
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<style scoped>
@keyframes fade-in {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slide-up {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.animate-fade-in {
    animation: fade-in 0.2s ease-out;
}

.animate-slide-up {
    animation: slide-up 0.3s ease-out;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-clamp: 2;
}
</style>
