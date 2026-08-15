<template>
    <div
        class="min-h-[calc(100vh-90px)] bg-primary/10 text-slate-900 flex flex-col bg-slate-100"
    >
        <div class="relative">
            <BookingHero />
            <AmumaServices />
            <Featured />
        </div>
    </div>
</template>

<script setup lang="ts">
import BookingHero from "~/components/sections/booking/search/BookingHero.vue";
import Featured from "~/components/sections/booking/search/Featured.vue";
import AmumaServices from "~/components/sections/booking/search/AmumaServices.vue";

useHead({
    title: "Bookings",
});
definePageMeta({
    navVariant: 1,
    navTheme: "dark",
});
</script>
