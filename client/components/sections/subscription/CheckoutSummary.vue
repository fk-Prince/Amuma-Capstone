<template>
    <div class="w-full">
        <div class="flex flex-col gap-4 sticky top-6 w-full">
            <div
                class="bg-white overflow-hidden text-sm rounded-xl shadow-sm relative"
            >
                <div class="flex items-center justify-between px-4 py-3.5 pl-5">
                    <div
                        class="flex items-center gap-2.5 font-semibold text-slate-800"
                    >
                        <span
                            class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center shrink-0"
                        >
                            <img
                                src="/assets/logo/logo.png"
                                alt=""
                                class="h-[18px] w-[18px] object-contain"
                            />
                        </span>
                        Subscription plan
                    </div>

                    <span
                        class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-teal-50 text-teal-700 uppercase tracking-wide shrink-0"
                    >
                        {{ checkout.selectedInterval }}
                    </span>
                </div>

                <div class="px-4 py-1 pl-5">
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

                <div class="px-4 py-3 flex flex-col mt-2">
                    <p
                        class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1"
                    >
                        Branch Information
                    </p>
                    <SummaryRow label="Name" :value="checkout.branch.name" />
                    <SummaryRow
                        label="Contact"
                        :value="checkout.branch.contact_number"
                    />
                    <SummaryRow
                        label="Description"
                        :value="checkout.branch.description"
                    />
                    <SummaryRow label="Address" :value="branchAddress" />
                </div>

                <div class="px-4 py-3 flex flex-col">
                    <p
                        class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1"
                    >
                        Branch settings
                    </p>
                    <SummaryRow label="Business hours" :value="businessHours" />
                    <SummaryRow
                        label="Currency"
                        :value="checkout.settings?.currency"
                    />
                    <SummaryRow
                        label="Time zone"
                        :value="checkout.settings?.time_zone"
                    />
                    <!-- <SummaryRow
                        label="Online fee"
                        :value="`₱${checkout.settings?.online_additional_fee ?? 0}`"
                    /> -->
                </div>

                <div class="px-4 py-3 flex flex-col">
                    <p
                        class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1"
                    >
                        Agency Information
                    </p>
                    <SummaryRow
                        label="Name"
                        :value="checkout.agency.agency_name"
                    />
                    <SummaryRow
                        label="Description"
                        :value="checkout.agency.agency_description"
                    />
                    <SummaryRow label="Address" :value="agencyAddress" />
                </div>
            </div>

            <div
                class="flex items-start gap-2.5 px-4 py-3 rounded-xl bg-slate-50"
            >
                <i
                    class="ti ti-info-circle text-[16px] text-slate-400 mt-0.5 shrink-0"
                />
                <p class="text-xs text-slate-500 leading-relaxed">
                    You can review or update this anytime in your branch
                    settings after subscription. Charges begin once checkout is
                    confirmed.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useSubscriptionCheckout } from "~/stores/subscription";
import SummaryRow from "~/components/ui/SummaryRow.vue";

const checkout = useSubscriptionCheckout();

const branchImagePreview = computed(() =>
    checkout.branch.image instanceof File
        ? URL.createObjectURL(checkout.branch.image)
        : null,
);

const branchAddress = computed(() =>
    [
        checkout.branch.location.street,
        checkout.branch.location.city,
        checkout.branch.location.province,
        checkout.branch.location.country,
    ]
        .filter(Boolean)
        .join(", "),
);

const agencyAddress = computed(() =>
    [
        checkout.agency.location.street,
        checkout.agency.location.city,
        checkout.agency.location.province,
        checkout.agency.location.country,
    ]
        .filter(Boolean)
        .join(", "),
);

const businessHours = computed(() =>
    checkout.settings?.opening && checkout.settings?.closing
        ? `${checkout.settings.opening} - ${checkout.settings.closing}`
        : "",
);
</script>
