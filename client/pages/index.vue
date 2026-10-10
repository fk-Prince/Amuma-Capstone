<script setup lang="ts">
import HeroSection from "~/components/sections/landing/HeroSection.vue";
import AudienceSection from "~/components/sections/landing/AudienceSection.vue";
import SolutionsSection from "~/components/sections/landing/SolutionsSection.vue";
import FeaturesSection from "~/components/sections/landing/FeaturesSection.vue";
import HowItWorksSection from "~/components/sections/landing/HowItWorksSection.vue";
import TestimonialsSection from "~/components/sections/landing/TestimonialsSection.vue";
import PricingSection from "~/components/sections/landing/PricingSection.vue";
import FaqSection from "~/components/sections/landing/FaqSection.vue";
import CtaSection from "~/components/sections/landing/CtaSection.vue";
import HelpWidget from "~/components/sections/landing/HelpWidget.vue";

useHead({
    title: "AMUMA",
});
definePageMeta({
    layout: "default",
    navVariant: 3,
});
</script>

<template>
    <div class="min-h-screen w-full overflow-x-hidden">
        <HeroSection />
        <AudienceSection />
        <SolutionsSection />
        <FeaturesSection />
        <HowItWorksSection />

        <div id="testimonials" class="scroll-mt-32">
            <TestimonialsSection />
        </div>

        <div id="pricing" class="scroll-mt-32">
            <PricingSection />
        </div>

        <div id="faq" class="scroll-mt-32">
            <FaqSection :limit="5" />
        </div>

        <CtaSection />
        <HelpWidget />
    </div>
</template>