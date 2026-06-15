<template>
    <div class="bg-white rounded-2xl shadow-sm border p-6">
        <h2 class="text-lg font-bold mb-4">Summary</h2>

        <div class="mb-5">
            <p
                class="text-xs text-gray-400 uppercase font-semibold mb-2 flex items-center gap-2"
            >
                <img
                    src="/assets/logo/logo.png"
                    alt="Preview"
                    class="h-[30px] object-cover rounded-sm"
                />
                Subscription
            </p>

            <div class="space-y-2 text-sm ml-3">
                <div class="flex justify-between">
                    <span class="text-gray-500">Plan</span>
                    <span class="font-semibold">
                        {{ checkout.selectedPlan?.name || "—" }}
                    </span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Billing</span>
                    <span class="font-semibold capitalize">
                        {{ checkout.selectedInterval || "—" }}
                    </span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Price</span>
                    <span class="font-bold text-blue-600">
                        {{
                            checkout.selectedPrice != null
                                ? `₱${checkout.selectedPrice}`
                                : "—"
                        }}
                    </span>
                </div>
            </div>
        </div>

        <div class="mb-5 border-t pt-4">
            <p
                class="text-xs text-gray-400 uppercase font-semibold mb-2 flex gap-2 items-center"
            >
                <img
                    v-if="branchImagePreview"
                    :src="branchImagePreview"
                    alt="Preview"
                    class="h-[30px] object-cover rounded-sm"
                />
                Branch
            </p>

            <div class="space-y-2 text-sm ml-3">
                <div class="space-y-2 text-sm">
                    <div
                        v-for="field in branchFields"
                        :key="field.key"
                        class="flex justify-between gap-3"
                    >
                        <span class="text-gray-500">{{ field.label }}</span>

                        <span
                            class="font-semibold text-right max-w-[200px]"
                            :class="
                                field.key === 'address'
                                    ? 'whitespace-normal break-words'
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

                            <template v-else-if="field.key === 'hours'">
                                {{
                                    checkout.settings?.opening &&
                                    checkout.settings?.closing
                                        ? `${checkout.settings.opening} - ${checkout.settings.closing}`
                                        : "—"
                                }}
                            </template>

                            <template v-else-if="field.key === 'currency'">
                                {{ checkout.settings?.[field.key] || "—" }}
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
            </div>
        </div>

        <!-- AGENCY -->
        <div class="mb-5 border-t pt-4">
            <p class="text-xs text-gray-400 uppercase font-semibold mb-2">
                Agency
            </p>

            <div class="space-y-2 text-sm ml-3">
                <div
                    v-for="field in agencyFields"
                    :key="field.key"
                    class="flex justify-between gap-3"
                >
                    <span class="text-gray-500">
                        {{ field.label }}
                    </span>

                    <span
                        class="font-semibold text-right max-w-[200px]"
                        :class="
                            field.type === 'computed'
                                ? 'whitespace-normal break-words'
                                : 'truncate'
                        "
                    >
                        <template
                            v-if="
                                field.type === 'computed' &&
                                field.key === 'address'
                            "
                        >
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
        </div>

        <!-- BRANCH -->
        <div class="border-t pt-4 flex justify-between items-center">
            <span class="font-bold">Total</span>

            <span class="text-xl font-bold text-blue-600">
                {{
                    checkout.selectedPrice != null
                        ? `₱${checkout.selectedPrice}`
                        : "—"
                }}
            </span>
        </div>

        <button
            @click="send"
            class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-semibold transition"
        >
            Confirm & Pay
        </button>
    </div>
</template>

<script setup lang="ts">
import { useSubscriptionCheckout } from "~/stores/subscription";
import { branchFields, agencyFields } from "~/utils/fields";
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { type SubscriptionRequest } from "~/types/subscription";

const checkout = useSubscriptionCheckout();
const branchImagePreview = computed(() =>
    checkout.branch.image instanceof File
        ? URL.createObjectURL(checkout.branch.image)
        : null,
);

const send = async () => {
    try {
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
            branch_latitude: checkout.branch.location.lat,
            branch_longitude: checkout.branch.location.lng,

            // AGENCY DATA
            agency_id: checkout.agency.id,
            agency_name: checkout.agency.name,
            agency_description: checkout.agency.description,
            agency_street: checkout.agency.location.street ?? "",
            agency_city: checkout.agency.location.city ?? "",
            agency_province: checkout.agency.location.province ?? "",
            agency_country: checkout.agency.location.country ?? "",
            agency_latitude: checkout.agency.location.lat ?? undefined,
            agency_longitude: checkout.agency.location.lng ?? undefined,
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
    }
};
</script>
