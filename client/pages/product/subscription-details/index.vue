<script setup lang="ts">
import Subscription from "~/components/sections/subscription/Subscription.vue";
import SubscriptionSummary from "~/components/sections/subscription/SubscriptionSummary.vue";

definePageMeta({
    middleware: "auth-client",
    navVariant: 1,
});

useHead({ title: "Subscription Details" });
</script>

<template>
    <ClientOnly>
        <div class="min-h-screen mt-5">
            <div class="max-w-7xl mx-auto">
                <h1
                    class="mx-5 text-center text-[1.4rem] font-extrabold text-gray-900"
                >
                    Subscription Details
                </h1>

                <div
                    class="grid grid-cols-1 lg:grid-cols-[2fr_400px] gap-8 mb-3"
                >
                    <div>
                        <Subscription />
                    </div>

                    <div class="sticky top-6 md:mt-6 lg:mx-0 mx-7">
                        <SubscriptionSummary class="sticky top-6 self-start" />
                    </div>
                </div>
            </div>
        </div>
    </ClientOnly>
</template>
