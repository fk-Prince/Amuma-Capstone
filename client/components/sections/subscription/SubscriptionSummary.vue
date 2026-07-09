<template>
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <h2 class="text-lg font-bold mb-4">Summary</h2>

        <div class="mb-5">
            <div class="flex justify-between items-center">
                <p
                    class="text-xs text-gray-400 uppercase font-semibold mb-2 flex items-center gap-2"
                >
                    Subscription Details
                </p>

                <img
                    src="/assets/logo/logo.png"
                    alt="Preview"
                    class="h-[20px] object-cover rounded-sm"
                />
            </div>
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
            <p class="text-xs text-gray-400 uppercase font-semibold mb-2">
                Agency Information
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

        <div class="mb-5 border-t pt-4">
            <div class="flex justify-between items-center">
                <p
                    class="text-xs text-gray-400 uppercase font-semibold mb-2 flex gap-2 items-center"
                >
                    Branch Information
                </p>
                <img
                    v-if="branchImagePreview"
                    :src="branchImagePreview"
                    alt="Preview"
                    class="h-[20px] object-cover rounded-sm"
                />
            </div>

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

            <div class="space-y-2 text-sm ml-3 mt-2">
                <div class="flex justify-between">
                    <span class="text-gray-500">Business Hours</span>
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
                    <span class="text-gray-500">Currency</span>
                    <span class="font-semibold">
                        {{ checkout.settings?.currency || "—" }}
                    </span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Time Zone</span>
                    <span class="font-semibold">
                        {{ checkout.settings?.time_zone || "—" }}
                    </span>
                </div>

                <!-- <div class="flex justify-between">
                    <span class="text-gray-500">Online Fee</span>
                    <span class="font-semibold">
                        ₱{{ checkout.settings?.online_additional_fee ?? 0 }}
                    </span>
                </div> -->
            </div>
        </div>

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
            v-if="stepCompleted"
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
import { getBranchImage } from "~/types/branch";
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
        const hasImageFile = checkout.branch.image instanceof File;
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

            // AGENCY DATA
            agency_id: checkout.agency.id,
            agency_name: checkout.agency.agency_name,
            agency_description: checkout.agency.agency_description,
            agency_street: checkout.agency.location.street ?? "",
            agency_city: checkout.agency.location.city ?? "",
            agency_province: checkout.agency.location.province ?? "",
            agency_country: checkout.agency.location.country ?? "",
            agency_latitude: checkout.agency.location.latitude ?? undefined,
            agency_longitude: checkout.agency.location.longitude ?? undefined,
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
