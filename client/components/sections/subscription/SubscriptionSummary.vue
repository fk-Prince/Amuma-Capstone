<template>
    <div class="bg-white rounded-3xl shadow-sm p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Summary</h2>

                <p class="text-sm text-slate-500 mt-1">
                    Review your subscription details before payment.
                </p>
            </div>

            <img
                src="/assets/logo/logo.png"
                alt="Logo"
                class="h-6 object-contain"
            />
        </div>

        <section>
            <h3 class="text-xs uppercase font-semibold text-slate-400 mb-3">
                Subscription Details
            </h3>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500"> Plan </span>

                    <span class="font-semibold text-slate-800">
                        {{ checkout.selectedPlan?.name || "—" }}
                    </span>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-500"> Billing </span>

                    <span class="font-semibold capitalize">
                        {{ checkout.selectedInterval || "—" }}
                    </span>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-500"> Price </span>

                    <span class="font-bold text-primary">
                        {{
                            checkout.selectedPrice != null
                                ? `₱${checkout.selectedPrice}`
                                : "—"
                        }}
                    </span>
                </div>
            </div>
        </section>

        <div class="h-px bg-slate-100" />

        <section>
            <h3 class="text-xs uppercase font-semibold text-slate-400 mb-3">
                Agency Information
            </h3>

            <div class="space-y-3 text-sm">
                <div
                    v-for="field in agencyFields"
                    :key="field.key"
                    class="flex justify-between gap-4"
                >
                    <span class="text-slate-500">
                        {{ field.label }}
                    </span>

                    <span
                        class="font-semibold text-right text-slate-800 max-w-[220px]"
                        :class="
                            field.key === 'address'
                                ? 'break-words whitespace-normal'
                                : 'truncate'
                        "
                    >
                        <template v-if="field.key === 'address'">
                            {{
                                [
                                    checkout.agency.location.street,
                                    checkout.agency.location.city,
                                    checkout.agency.location.province,
                                    checkout.agency.location.country,
                                ]
                                    .filter(Boolean)
                                    .join(", ") || "—"
                            }}
                        </template>

                        <template v-else>
                            {{
                                checkout.agency[
                                    field.key as keyof typeof checkout.agency
                                ] || "—"
                            }}
                        </template>
                    </span>
                </div>
            </div>
        </section>

        <div class="h-px bg-slate-100" />

        <section>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs uppercase font-semibold text-slate-400">
                    Branch Information
                </h3>

                <img
                    v-if="branchImagePreview"
                    :src="branchImagePreview"
                    class="h-7 w-7 rounded-lg object-cover"
                />
            </div>

            <div class="space-y-3 text-sm">
                <div
                    v-for="field in branchFields"
                    :key="field.key"
                    class="flex justify-between gap-4"
                >
                    <span class="text-slate-500">
                        {{ field.label }}
                    </span>

                    <span
                        class="font-semibold text-right text-slate-800 max-w-[220px]"
                        :class="
                            field.key === 'address'
                                ? 'break-words whitespace-normal'
                                : 'truncate'
                        "
                    >
                        <template v-if="field.key === 'address'">
                            {{
                                [
                                    checkout.branch.location.street,
                                    checkout.branch.location.city,
                                    checkout.branch.location.province,
                                    checkout.branch.location.country,
                                ]
                                    .filter(Boolean)
                                    .join(", ") || "—"
                            }}
                        </template>

                        <template v-else>
                            {{
                                checkout.branch[
                                    field.key as keyof typeof checkout.branch
                                ] || "—"
                            }}
                        </template>
                    </span>
                </div>
            </div>

            <div class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500"> Business Hours </span>

                    <span class="font-semibold">
                        {{
                            checkout.settings?.opening &&
                            checkout.settings?.closing
                                ? `${checkout.settings.opening} - ${checkout.settings.closing}`
                                : "—"
                        }}
                    </span>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-500"> Currency </span>

                    <span class="font-semibold">
                        {{ checkout.settings?.currency || "—" }}
                    </span>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-500"> Time Zone </span>

                    <span class="font-semibold">
                        {{ checkout.settings?.time_zone || "—" }}
                    </span>
                </div>
            </div>
        </section>

        <div class="h-px bg-slate-100" />

        <div class="flex items-center justify-between">
            <span class="text-base font-bold text-slate-800"> Total </span>

            <span class="text-2xl font-bold text-primary">
                {{
                    checkout.selectedPrice != null
                        ? `₱${checkout.selectedPrice}`
                        : "—"
                }}
            </span>
        </div>

        <button
            v-if="stepCompleted"
            @click="send"
            :disabled="isLoading"
            class="w-full rounded-xl bg-primary hover:bg-primary/90 disabled:opacity-50 text-white py-3 font-semibold transition flex items-center justify-center gap-2"
        >
            <svg
                v-if="isLoading"
                class="w-5 h-5 animate-spin"
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

            {{ isLoading ? "Validating..." : "Confirm & Pay" }}
        </button>
    </div>
</template>
<script setup lang="ts">
import { useSubscriptionCheckout } from "~/stores/subscription";
import { branchFields, agencyFields } from "~/utils/fields";
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { type SubscriptionRequest } from "~/types/subscription";
const isLoading = ref(false);
const props = defineProps<{
    stepCompleted: boolean;
}>();
const checkout = useSubscriptionCheckout();
const branchImagePreview = computed(() =>
    checkout.branch.image instanceof File
        ? URL.createObjectURL(checkout.branch.image)
        : null,
);

const send = async () => {
    try {
        isLoading.value = true;
        const payload: SubscriptionRequest = {
            plan_code: checkout.selectedPlan.plan_code,
            payment_method: checkout.payment_method,
            billing_interval: checkout.selectedInterval,

            //BRANCH DATA
            branch_name: checkout.branch.name,
            branch_contact_number: checkout.branch.contact_number,
            branch_image: checkout.branch.image,
            branch_description: checkout.branch.description,
            branch_settings: checkout.settings,
            branch_street: checkout.branch.location.street,
            branch_city: checkout.branch.location.city,
            branch_province: checkout.branch.location.province,
            branch_country: checkout.branch.location.country,
            branch_latitude: checkout.branch.location.latitude,
            branch_longitude: checkout.branch.location.longitude,
            branch_email: checkout.branch.email ?? "",
            branch_document: checkout.agency.document ?? "",

            // AGENCY DATA
            agency_id: checkout.agency.agency_id,
            agency_name: checkout.agency.name,
            agency_description: checkout.agency.description,
            agency_street: checkout.agency.location.street ?? "",
            agency_city: checkout.agency.location.city ?? "",
            agency_province: checkout.agency.location.province ?? "",
            agency_country: checkout.agency.location.country ?? "",
            agency_latitude: checkout.agency.location.latitude ?? undefined,
            agency_longitude: checkout.agency.location.longitude ?? undefined,
            agency_email: checkout.agency.email ?? "",
            agency_image: checkout.agency.image,
            agency_id_front: checkout.agency.id_front ?? "",
            agency_id_back: checkout.agency.id_back ?? "",
            agency_document: checkout.agency.document ?? "",
        };
        await subscriptionService.validateSubscription(payload);
        checkout.subscriptionPayload = payload;
        await navigateTo({
            path: "/product/subscription-details/checkout",
            query: {
                code: checkout.selectedPlan?.plan_id,
                interval: checkout.selectedInterval,
            },
        });
    } catch (err: any) {
        const errors = err?.errors || err?.response?.data?.errors;
        console.log(err);
        if (errors) {
            checkout.errors = Object.fromEntries(
                Object.entries(errors).map(([key, value]: any) => [
                    key,
                    Array.isArray(value) ? value[0] : value,
                ]),
            );
        }
    } finally {
        isLoading.value = false;
    }
};
</script>
