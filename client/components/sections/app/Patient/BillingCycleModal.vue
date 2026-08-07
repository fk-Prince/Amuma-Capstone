<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[70] flex items-center justify-center bg-black/40 p-5"
            >
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                    <h3 class="text-lg font-semibold">Select Billing Cycle</h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Choose the billing cycle for this admission.
                    </p>

                    <div
                        v-if="admission?.end_date"
                        class="mt-4 rounded-xl bg-slate-50 p-3"
                    >
                        <p class="text-xs text-slate-500">
                            Current discharge date
                        </p>

                        <p class="font-semibold">
                            {{ formatDate(admission.end_date) }}
                        </p>
                    </div>

                    <div class="mt-6">
                        <div v-if="loading" class="space-y-3">
                            <div
                                v-for="i in 3"
                                :key="i"
                                class="animate-pulse rounded-xl border p-4"
                            >
                                <div
                                    class="h-4 w-28 rounded bg-slate-200"
                                ></div>
                            </div>
                        </div>

                        <div v-else-if="contracts.length" class="space-y-3">
                            <button
                                v-for="contract in contracts"
                                :key="contract.branch_contract_id"
                                class="w-full rounded-xl border p-4 text-left transition"
                                :class="
                                    selectedContract?.branch_contract_id ===
                                    contract.branch_contract_id
                                        ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                        : 'hover:border-primary'
                                "
                                @click="selectContract(contract)"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-semibold capitalize">
                                            {{
                                                contract.billing_cycle.toLowerCase()
                                            }}
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            {{ contract.category }}
                                            -
                                            {{ contract.accommodation_type }}
                                        </p>
                                    </div>

                                    <span class="font-semibold text-primary">
                                        ₱{{
                                            Number(
                                                contract.price,
                                            ).toLocaleString()
                                        }}
                                    </span>
                                </div>

                                <div
                                    v-if="
                                        selectedContract?.branch_contract_id ===
                                        contract.branch_contract_id
                                    "
                                    class="mt-3 rounded-lg bg-white p-3 text-sm"
                                >
                                    <p class="text-slate-500">
                                        New discharge date
                                    </p>

                                    <p class="font-semibold">
                                        {{
                                            formatDate(calculatedDischargeDate)
                                        }}
                                    </p>
                                </div>
                            </button>
                        </div>

                        <div
                            v-else
                            class="rounded-xl border border-dashed py-10 text-center text-sm text-slate-500"
                        >
                            No billing contracts available.
                        </div>
                    </div>
                    <button
                        class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-2.5 text-sm font-medium text-white disabled:opacity-50"
                        :disabled="
                            submitting ||
                            !selectedContract ||
                            !calculatedDischargeDate
                        "
                        @click="confirmSelection"
                    >
                        <svg
                            v-if="submitting"
                            class="h-4 w-4 animate-spin"
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

                        {{ submitting ? "Extending..." : "Confirm" }}
                    </button>
                    <button
                        class="mt-5 w-full rounded-xl border py-2.5 text-sm"
                        @click="$emit('close')"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, computed } from "vue";
import { useRoute } from "vue-router";

import { admissionService } from "~/api/admission/AdmissionService";
import type { Contract } from "~/types/contract";
import type { Admission } from "~/types/patient";

const route = useRoute();

const props = defineProps<{
    open: boolean;
    admission: Admission | null;
}>();

const emit = defineEmits<{
    (e: "select", contract: Contract): void;
    (e: "close"): void;
}>();

const contracts = ref<Contract[]>([]);
const loading = ref(false);

const selectedContract = ref<Contract | null>(null);

const calculatedDischargeDate = computed<string | null>(() => {
    if (!selectedContract.value || !props.admission?.end_date) {
        return null;
    }

    const date = new Date(props.admission.end_date);

    switch (selectedContract.value.billing_cycle.toLowerCase()) {
        case "monthly":
            date.setMonth(date.getMonth() + 1);
            break;

        case "quarterly":
            date.setMonth(date.getMonth() + 3);
            break;

        case "semi annual":
        case "semi-annually":
        case "semiannual":
            date.setMonth(date.getMonth() + 6);
            break;

        case "annual":
        case "yearly":
            date.setFullYear(date.getFullYear() + 1);
            break;
    }

    return date.toISOString().split("T")[0] ?? null;
});

watch(
    [() => props.open, () => props.admission],
    async ([open, admission]) => {
        if (!open || !admission) {
            return;
        }

        selectedContract.value = null;

        loading.value = true;

        try {
            const res = await admissionService.action({
                branch_uuid: route.params.uuid,
                p_uuid: route.params.p_uuid,
                admission_id: admission.patient_admission_id,
                action: "contract",
            });

            contracts.value = res.data?.data ?? res.data ?? res ?? [];
        } catch (err) {
            console.error("Failed loading contracts", err);

            contracts.value = [];
        } finally {
            loading.value = false;
        }
    },
    {
        immediate: true,
    },
);
const submitting = ref(false);
function selectContract(contract: Contract) {
    if (
        selectedContract.value?.branch_contract_id ===
        contract.branch_contract_id
    ) {
        selectedContract.value = null;
        return;
    }

    selectedContract.value = contract;
}

function confirmSelection() {
    if (!selectedContract.value || !calculatedDischargeDate.value) {
        return;
    }
    submitting.value = true;

    emit("select", selectedContract.value);
}

function formatDate(date: string | Date | null) {
    if (!date) {
        return "-";
    }

    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}
watch(
    () => props.open,
    (value) => {
        if (!value) {
            submitting.value = false;
        }
    },
);
</script>
