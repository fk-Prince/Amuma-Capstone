<template>
    <div
        class="relative min-h-screen flex items-center justify-center overflow-hidden bg-secondary-950 px-6"
    >
        <div
            class="pointer-events-none absolute inset-0 overflow-hidden"
            aria-hidden="true"
        >
            <div
                class="absolute -top-40 left-1/2 h-[520px] w-[520px] -translate-x-1/2 rounded-full bg-primary-500/15 blur-[140px]"
            />
        </div>

        <NuxtLink
            to="/"
            class="absolute left-6 top-6 z-10 inline-flex"
            aria-label="AMUMA home"
        >
            <BrandLogo />
        </NuxtLink>

        <div class="relative z-10 max-w-lg text-center">
            <div
                class="mx-auto mb-8 flex h-24 w-24 items-center justify-center rounded-full border border-white/10 bg-amber-500/10 text-amber-300"
            >
                <Lock class="h-11 w-11" />
            </div>

            <h1 class="text-3xl font-bold text-white">
                {{ branchName || "This branch" }} is locked
            </h1>

            <p class="mt-3 leading-relaxed text-gray-400">
                The free trial has ended and the plan hasn't been paid for yet.
                Ask your agency owner to pay for the plan from Manage
                Subscription. Everything you recorded is kept and will be back
                as soon as it's paid.
            </p>

            <div class="mt-8 flex justify-center">
                <NuxtLink
                    to="/"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-3 font-medium text-white shadow-lg shadow-primary/20 transition hover:-translate-y-0.5 hover:shadow-xl"
                >
                    Back to home
                </NuxtLink>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Lock } from "lucide-vue-next";
import BrandLogo from "~/components/ui/BrandLogo.vue";
import { useBranchStore } from "~/stores/branch";

definePageMeta({
    layout: false,
    middleware: "auth-client",
});

useHead({ title: "Branch locked" });

const route = useRoute();
const branchStore = useBranchStore();

const branchName = computed(
    () =>
        branchStore.branches.find((b) => b?.uuid === route.params.uuid)
            ?.name ?? "",
);
</script>
