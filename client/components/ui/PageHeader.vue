<template>
    <div class="flex items-end justify-between border-b border-[#DCEBE9] pb-4">
        <div>
            <h1 class="text-4xl font-bold text-[#16302E]">
                {{ title }}
            </h1>

            <h2 class="mt-1 text-sm font-medium text-[#0E7C7B]">
                {{ subtitle }}
            </h2>

            <p class="mt-1 text-sm text-[#6B8A87]">
                {{ description }}
            </p>
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: "",
    },
    description: {
        type: String,
        default: "",
    },
});
</script>
