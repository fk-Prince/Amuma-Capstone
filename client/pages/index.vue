<script setup lang="ts">
import HeroSection from "~/components/sections/landing/HeroSection.vue";
import SolutionsSection from "~/components/sections/landing/SolutionsSection.vue";
import Landing from "~/components/sections/landing/Landing.vue";
import FeaturesSection from "~/components/sections/landing/FeaturesSection.vue";
import HowItWorksSection from "~/components/sections/landing/HowItWorksSection.vue";
import TestimonialsSection from "~/components/sections/landing/TestimonialsSection.vue";
import PricingSection from "~/components/sections/landing/PricingSection.vue";
import CtaSection from "~/components/sections/landing/CtaSection.vue";
useHead({
    title: "AMUMA",
});
definePageMeta({
    layout: "default",
    navVariant: 2,
});
</script>

<template>
    <div class="min-h-screen w-full overflow-x-hidden">
        <HeroSection />
        <SolutionsSection />
        <FeaturesSection />
        <HowItWorksSection />
        <TestimonialsSection />
        <PricingSection />
        <CtaSection />
    </div>
</template>
