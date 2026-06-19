<script setup lang="ts">
import Subscription from "~/components/sections/subscription/Subscription.vue";
import SubscriptionSummary from "~/components/sections/subscription/SubscriptionSummary.vue";

definePageMeta({
    middleware: "auth-client",
    navVariant: 1,
});

useHead({ title: "Subscription Details" });

const stepCompleted = ref(false);
</script>
<template>
    <ClientOnly>
        <div class="min-h-screen">
            <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div
                    class="grid grid-cols-1 xl:grid-cols-[2fr_480px] gap-8 items-start"
                >
                    <div class="space-y-10">
                        <div>
                            <h1
                                class="text-3xl md:text-4xl font-extrabold text-gray-900"
                            >
                                Subscription Details
                            </h1>

                            <p class="mt-3 text-gray-500 max-w-2xl">
                                Complete your subscription by reviewing your
                                plan, branch and agency information, billing
                                cycle, and payment details.
                            </p>
                        </div>

                        <Subscription v-model:stepCompleted="stepCompleted" />
                    </div>

                    <aside class="xl:sticky xl:top-6">
                        <SubscriptionSummary :stepCompleted="stepCompleted" />
                    </aside>
                </div>
            </div>
        </div>
    </ClientOnly>
</template>
