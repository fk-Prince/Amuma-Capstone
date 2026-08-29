<template>
    <div class="w-full max-w-5xl mx-auto space-y-6">
        <div class="bg-white rounded-2xl shadow-sm p-6 dark:bg-secondary">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center"
                    >
                        <img
                            src="/assets/logo/logo.png"
                            alt="Logo"
                            class="w-7 h-7 object-contain"
                        />
                    </div>

                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">
                            Review & Confirm
                        </h2>

                        <p class="text-sm text-slate-500 mt-1 dark:text-gray-400">
                            Review your information before activating your
                            subscription.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="router.back()"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors shrink-0 dark:bg-secondary dark:border-white/10 dark:text-gray-300"
                >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"
                        />
                    </svg>
                    Back
                </button>
            </div>
        </div>

        <section class="bg-white rounded-2xl shadow-sm p-6 space-y-5 dark:bg-secondary">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                        Subscription Details
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        Your selected plan and payment information.
                    </p>
                </div>

                <span
                    class="px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold capitalize"
                >
                    {{ checkout.selectedInterval || "—" }}
                </span>
            </div>

            <div class="space-y-3">
                <SummaryRow
                    label="Plan"
                    :value="
                        checkout.selectedPlan?.plan_code === 'C'
                            ? 'Hybrid Plan'
                            : checkout.selectedPlan?.plan_code === 'A'
                              ? 'Homecare Services'
                              : checkout.selectedPlan?.plan_code === 'B'
                                ? 'Inhouse Facility'
                                : '—'
                    "
                />

                <SummaryRow
                    class="capitalize"
                    label="Billing Cycle"
                    :value="checkout.selectedInterval || '—'"
                />

                <SummaryRow
                    label="Payment Method"
                    :value="checkout.payment_method || '—'"
                />
            </div>
        </section>

        <section class="bg-white rounded-2xl shadow-sm p-6 space-y-5 dark:bg-secondary">
            <div>
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                    Agency Information
                </h3>

                <p class="text-sm text-slate-500 dark:text-gray-400">Registered agency details.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-5">
                <div
                    class="w-28 h-28 rounded-2xl bg-slate-100 overflow-hidden shrink-0 dark:bg-white/10 dark:bg-secondary"
                >
                    <img
                        v-if="agencyImagePrevieiw"
                        :src="agencyImagePrevieiw"
                        class="w-full h-full object-cover"
                    />

                    <div
                        v-else
                        class="h-full flex items-center justify-center text-xs text-slate-400 dark:text-gray-500"
                    >
                        No Image
                    </div>
                </div>
                <div class="space-y-3 flex-1">
                    <SummaryRow
                        label="Agency Name"
                        :value="checkout.agency.name || '—'"
                    />

                    <SummaryRow
                        label="Email Address"
                        :value="checkout.agency.email || '—'"
                    />

                    <SummaryRow
                        label="Description"
                        :value="checkout.agency.description || '—'"
                    />

                    <SummaryRow label="Address" :value="agencyAddress || '—'" />
                </div>
            </div>
        </section>

        <section class="bg-white rounded-2xl shadow-sm p-6 space-y-5 dark:bg-secondary">
            <div>
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                    Branch Information
                </h3>

                <p class="text-sm text-slate-500 dark:text-gray-400">
                    Branch details and location.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-5">
                <div
                    class="w-28 h-28 rounded-2xl bg-slate-100 overflow-hidden shrink-0 dark:bg-white/10 dark:bg-secondary"
                >
                    <img
                        v-if="branchImagePreview"
                        :src="branchImagePreview"
                        class="w-full h-full object-cover"
                    />

                    <div
                        v-else
                        class="h-full flex items-center justify-center text-xs text-slate-400 dark:text-gray-500"
                    >
                        No Image
                    </div>
                </div>

                <div class="flex-1 space-y-3">
                    <SummaryRow
                        label="Branch Name"
                        :value="checkout.branch.name || '—'"
                    />

                    <SummaryRow
                        label="Email Address"
                        :value="checkout.branch.email || '—'"
                    />

                    <SummaryRow
                        label="Contact Number"
                        :value="checkout.branch.contact_number || '—'"
                    />

                    <SummaryRow
                        label="Description"
                        :value="checkout.branch.description || '—'"
                    />

                    <SummaryRow label="Address" :value="branchAddress || '—'" />
                </div>
            </div>
        </section>

        <section class="bg-white rounded-2xl shadow-sm p-6 space-y-5 dark:bg-secondary">
            <div>
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                    Operation Settings
                </h3>

                <p class="text-sm text-slate-500 dark:text-gray-400">
                    Branch configuration and working preferences.
                </p>
            </div>

            <div class="space-y-3">
                <SummaryRow
                    label="Business Hours"
                    :value="businessHours || '—'"
                />

                <SummaryRow
                    label="Currency"
                    :value="checkout.settings?.currency || '—'"
                />

                <SummaryRow
                    label="Time Zone"
                    :value="checkout.settings?.time_zone || '—'"
                />

                <!-- <SummaryRow
                    label="Minimum Homecare Hours"
                    :value="
                        String(checkout.settings?.minimum_homecare_hours || '—')
                    "
                />

                <SummaryRow
                    label="Billing Due Date"
                    :value="String(checkout.settings?.billing_due_date || '—')"
                />

                <SummaryRow
                    label="Branch Status"
                    :value="checkout.settings?.is_open ? 'Open' : 'Closed'"
                /> -->
            </div>
        </section>

        <div class="flex gap-3 rounded-2xl bg-blue-50 px-5 py-4 dark:bg-primary-500/10">
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
import { useRouter } from "vue-router";

const router = useRouter();
const checkout = useSubscriptionCheckout();

const branchImagePreview = computed(() =>
    checkout.branch.image instanceof File
        ? URL.createObjectURL(checkout.branch.image)
        : typeof checkout.branch.image === "string"
          ? checkout.branch.image
          : null,
);

const agencyImagePrevieiw = computed(() =>
    checkout.agency.image instanceof File
        ? URL.createObjectURL(checkout.agency.image)
        : typeof checkout.agency.image === "string"
          ? checkout.agency.image
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
