<template>
    <div class="w-full max-w-5xl mx-auto space-y-5">
        <!-- Header -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <div class="flex items-center gap-3">
                <div
                    class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center"
                >
                    <img
                        src="/assets/logo/logo.png"
                        alt="Logo"
                        class="w-6 h-6 object-contain"
                    />
                </div>

                <div>
                    <h2 class="text-xl font-bold text-slate-800">
                        Review & Confirm
                    </h2>

                    <p class="text-sm text-slate-500">
                        Review your information before activating your
                        subscription.
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200">
            <div class="px-5 py-4 border-b flex items-center justify-between">
                <h3 class="font-semibold text-slate-800">
                    Subscription Details
                </h3>

                <span
                    class="text-xs font-semibold uppercase px-3 py-1 rounded-full bg-primary/10 text-primary"
                >
                    {{ checkout.selectedInterval || "—" }}
                </span>
            </div>

            <div class="p-5 space-y-3">
                <SummaryRow
                    label="Plan"
                    :value="checkout.selectedPlan?.plan_code || '—'"
                />

                <SummaryRow
                    label="Billing cycle"
                    :value="checkout.selectedInterval || '—'"
                />

                <SummaryRow
                    label="Payment method"
                    :value="checkout.payment_method || '—'"
                />
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200">
            <div class="px-5 py-4 border-b">
                <h3 class="font-semibold text-slate-800">Agency Information</h3>
            </div>

            <div class="p-5 space-y-3">
                <SummaryRow
                    label="Name"
                    :value="checkout.agency.agency_name || '—'"
                />

                <SummaryRow
                    label="Description"
                    :value="checkout.agency.agency_description || '—'"
                />

                <SummaryRow label="Address" :value="agencyAddress || '—'" />
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200">
            <div class="px-5 py-4 border-b">
                <h3 class="font-semibold text-slate-800">Branch Information</h3>
            </div>

            <div class="p-5 flex gap-5">
                <div
                    class="w-24 h-24 rounded-xl bg-slate-100 overflow-hidden shrink-0"
                >
                    <img
                        v-if="branchImagePreview"
                        :src="branchImagePreview"
                        class="w-full h-full object-cover"
                    />

                    <div
                        v-else
                        class="w-full h-full flex items-center justify-center text-xs text-slate-400"
                    >
                        No Image
                    </div>
                </div>

                <div class="flex-1 space-y-3">
                    <SummaryRow
                        label="Name"
                        :value="checkout.branch.name || '—'"
                    />

                    <SummaryRow
                        label="Contact"
                        :value="checkout.branch.contact_number || '—'"
                    />

                    <SummaryRow
                        label="Description"
                        :value="checkout.branch.description || '—'"
                    />

                    <SummaryRow label="Address" :value="branchAddress || '—'" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200">
            <div class="px-5 py-4 border-b">
                <h3 class="font-semibold text-slate-800">
                    Branch Configuration
                </h3>
            </div>

            <div class="p-5 space-y-3">
                <SummaryRow
                    label="Business hours"
                    :value="businessHours || '—'"
                />

                <SummaryRow
                    label="Currency"
                    :value="checkout.settings?.currency || '—'"
                />

                <SummaryRow
                    label="Time zone"
                    :value="checkout.settings?.time_zone || '—'"
                />
            </div>
        </div>

        <div
            class="flex gap-3 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3"
        >
            <i class="ti ti-info-circle text-blue-500 text-lg mt-0.5" />

            <p class="text-sm text-blue-700 leading-relaxed">
                Please confirm that all information is correct. You can update
                your branch details later from your settings.
            </p>
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
        : typeof checkout.branch.image === "string"
          ? checkout.branch.image
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
