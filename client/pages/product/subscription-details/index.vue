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
        <div class="min-h-screen bg-slate-50">
            <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="grid grid-cols-1 gap-8 items-start">
                    <div class="max-w-2xl">
                        <span
                            class="inline-block text-xs font-semibold tracking-wide uppercase text-primary bg-primary-50 px-3 py-1 rounded-full mb-3"
                        >
                            Subscription setup
                        </span>

                        <h1
                            class="text-3xl md:text-4xl font-extrabold text-secondary"
                        >
                            Subscription details
                        </h1>

                        <p class="mt-3 text-muted">
                            Complete your subscription by reviewing your plan,
                            branch and agency information, billing cycle, and
                            payment details.
                        </p>
                    </div>

                    <div
                        class="w-full flex justify-center items-center flex-col"
                    >
                        <Subscription v-model:stepCompleted="stepCompleted" />
                    </div>

                    <!-- <aside class="xl:sticky xl:top-6">
                        <SubscriptionSummary :stepCompleted="stepCompleted" />
                    </aside> -->
                </div>
            </div>
        </div>
    </ClientOnly>
</template>
