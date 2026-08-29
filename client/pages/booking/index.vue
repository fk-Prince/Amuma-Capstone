<template>
    <div class="min-h-screen-header bg-slate-100 text-slate-900 flex flex-col dark:bg-secondary dark:text-white">
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
    middleware: ["prevent-staff-booking"],
});
</script>
