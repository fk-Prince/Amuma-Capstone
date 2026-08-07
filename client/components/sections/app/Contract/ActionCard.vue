<template>
    <button
        type="button"
        class="flex items-center justify-between rounded-xl border border-[#E4EFED] bg-white px-5 py-4 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5"
        @click="onClick?.()"
    >
        <div class="flex items-center gap-3">
            <div
                class="w-9 h-9 rounded-lg flex items-center justify-center"
                :class="bgColor"
            >
                <component :is="icon" class="w-5 h-5" :class="iconColor" />
            </div>

            <span class="text-sm font-semibold" :class="textColor">
                {{ label }}
            </span>
        </div>

        <ChevronRight class="w-4 h-4" :class="textColor" />
    </button>
</template>

<script setup lang="ts">
import { ChevronRight } from "lucide-vue-next";

defineProps<{
    label: string;

    icon: any;

    iconColor: string;

    textColor: string;

    bgColor?: string;

    onClick?: () => void;
}>();
</script>
