<template>
    <div class="w-full flex flex-col gap-4">
        <Calendar />

        <div class="rounded-2xl bg-white border border-[#E4EFED] p-5">
            <h3 class="font-semibold text-[#16302E]">Notifications</h3>

            <ul class="mt-3 space-y-2 text-sm text-[#6B8A87]">
                <li>• 2 bookings need confirmation</li>
                <li>• 1 booking starts in 30 minutes</li>
            </ul>
        </div>
    </div>
</template>

<script lang="ts" setup>
import Calendar from "~/components/ui/Calendar.vue";
</script>
