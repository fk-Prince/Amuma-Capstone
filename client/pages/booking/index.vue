<template>
    <div class="min-h-screen bg-primary/10 text-slate-900 flex flex-col">
        <BookingHero />
        <AmumaServices />
        <Featured />
    </div>
</template>

<script setup lang="ts">
import BookingHero from "~/components/sections/booking/BookingHero.vue";
import Featured from "~/components/sections/booking/Featured.vue";
import AmumaServices from "~/components/sections/booking/AmumaServices.vue";

useHead({
    title: "Bookings",
});
definePageMeta({
    navVariant: 1,
});
</script>
