<template>
    <div class="w-[65%]">
        <div class="flex flex-col gap-3 sticky top-6 w-[65%]">
            <div class="flex items-center justify-between">
                <p class="text-sm font-bold text-slate-700">
                    Subscription summary
                </p>
                <span
                    class="text-[11px] font-medium px-2.5 py-0.5 rounded-full bg-green-50 text-green-700"
                >
                    <i class="ti ti-shield-check text-[11px] mr-1" />Secure
                    checkout
                </span>
            </div>

            <div class="bg-white overflow-hidden text-sm">
                <div
                    class="flex items-center justify-between px-4 py-3 border-b border-gray-100"
                >
                    <div
                        class="flex items-center gap-2 font-medium text-slate-700"
                    >
                        <span
                            class="w-7 h-7 rounded-lg bg-gray-100 flex items-center justify-center"
                        >
                            <img
                                src="/assets/logo/logo.png"
                                alt="Preview"
                                class="h-[30px] object-cover rounded-sm"
                            />
                        </span>
                        Subscription plan
                    </div>
                    <span
                        class="text-[11px] font-medium px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 uppercase"
                    >
                        {{ checkout.selectedInterval }}
                    </span>
                </div>
                <div class="px-4 py-3 flex flex-col gap-1.5">
                    <div class="pl-5">
                        <SummaryRow
                            label="Plan"
                            :value="checkout.selectedPlan?.plan_code"
                        />
                        <SummaryRow
                            label="Billing cycle"
                            :value="checkout.selectedInterval"
                        />
                        <SummaryRow
                            label="Payment method"
                            :value="checkout.payment_method"
                        />
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden text-sm">
                <div
                    class="flex items-center gap-2 px-4 py-3 border-b border-gray-100 font-medium text-slate-700"
                >
                    <span
                        class="w-7 h-7 rounded-lg bg-gray-100 flex items-center justify-center"
                    >
                        <img
                            v-if="branchImagePreview"
                            :src="branchImagePreview"
                            alt="Preview"
                            class="h-[30px] object-cover rounded-sm"
                        />
                    </span>
                    Branch details
                </div>
                <div
                    class="px-4 py-3 flex flex-col gap-1.5 border-b border-gray-100"
                >
                    <p
                        class="text-[10px] font-medium text-gray-400 uppercase tracking-widest mb-1"
                    >
                        Info
                    </p>
                    <div class="pl-5">
                        <SummaryRow
                            label="Name"
                            :value="checkout.branch.name"
                        />
                        <SummaryRow
                            label="Contact"
                            :value="checkout.branch.contact_number"
                        />
                        <SummaryRow
                            label="Description"
                            :value="checkout.branch.description"
                        />
                    </div>
                </div>
                <div class="px-4 py-3 flex flex-col gap-1.5">
                    <p
                        class="text-[10px] font-medium text-gray-400 uppercase tracking-widest mb-1"
                    >
                        Address
                    </p>
                    <div class="pl-5">
                        <SummaryRow
                            label="Street"
                            :value="checkout.branch.location.street"
                        />
                        <SummaryRow
                            label="City"
                            :value="checkout.branch.location.city"
                        />
                        <SummaryRow
                            label="Province"
                            :value="checkout.branch.location.province"
                        />
                        <SummaryRow
                            label="Country"
                            :value="checkout.branch.location.country"
                        />
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden text-sm">
                <div
                    class="flex items-center gap-2 px-4 py-3 border-b border-gray-100 font-medium text-slate-700"
                >
                    <span
                        class="w-7 h-7 rounded-lg flex items-center justify-center"
                    >
                    </span>
                    Agency details
                </div>
                <div
                    class="px-4 py-3 flex flex-col gap-1.5 border-b border-gray-100"
                >
                    <p
                        class="text-[10px] font-medium text-gray-400 uppercase tracking-widest mb-1"
                    >
                        Info
                    </p>
                    <div class="pl-5">
                        <SummaryRow
                            label="Name"
                            :value="checkout.agency.name"
                        />
                        <SummaryRow
                            label="Description"
                            :value="checkout.agency.description"
                        />
                    </div>
                </div>
                <div class="px-4 py-3 flex flex-col gap-1.5">
                    <p
                        class="text-[10px] font-medium text-gray-400 uppercase tracking-widest mb-1"
                    >
                        Address
                    </p>
                    <div class="pl-5">
                        <SummaryRow
                            label="Street"
                            :value="checkout.agency.location.street"
                        />
                        <SummaryRow
                            label="City"
                            :value="checkout.agency.location.city"
                        />
                        <SummaryRow
                            label="Province"
                            :value="checkout.agency.location.province"
                        />
                        <SummaryRow
                            label="Country"
                            :value="checkout.agency.location.country"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useSubscriptionCheckout } from "~/stores/subscription";
import SummaryRow from "~/components/ui/SummaryRow.vue";

const checkout = useSubscriptionCheckout();
const branchImagePreview = computed(() =>
    checkout.branch.image instanceof File
        ? URL.createObjectURL(checkout.branch.image)
        : null,
);
</script>
