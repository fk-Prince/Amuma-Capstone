<template>
    <div class="flex flex-col h-screen overflow-hidden">
        <Filter class="flex-shrink-0" />

        <div
            class="flex-1 overflow-hidden mx-auto w-full max-w-[95%] px-4 py-6 flex flex-col md:flex-row gap-6"
        >
            <div class="w-full md:w-3/5 overflow-y-auto">
                <CardBooking />
            </div>
            <div class="w-full md:w-2/5 bg-secondary rounded-xl"></div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Filter from "~/components/sections/booking/Filter.vue";
import CardBooking from "~/components/sections/booking/CardBooking.vue";
</script>
