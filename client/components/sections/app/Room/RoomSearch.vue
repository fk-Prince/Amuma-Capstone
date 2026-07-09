<script setup lang="ts">
import BaseInput from "~/components/ui/BaseInput.vue";

defineProps<{
    modelValue: string;
    activeTab: string;
}>();

const emit = defineEmits<{
    "update:modelValue": [value: string];
    "update:activeTab": [value: string];
}>();

const tabs = ["All Rooms", "VIP Rooms", "Common Rooms"];
</script>

<template>
    <div class="space-y-4">
        <div class="relative">
            <svg
                viewBox="0 0 24 24"
                class="w-4 h-4 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <circle cx="11" cy="11" r="7" />
                <path d="M21 21l-4.35-4.35" stroke-linecap="round" />
            </svg>

            <BaseInput
                :model-value="modelValue"
                @update:model-value="emit('update:modelValue', $event)"
                placeholder="Search rooms, residents..."
                input-class="pl-[3rem]"
            />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 py-4">
            <div class="flex items-center gap-1 bg-gray-50 rounded-full p-1">
                <button
                    v-for="tab in tabs"
                    :key="tab"
                    @click="emit('update:activeTab', tab)"
                    class="px-3.5 py-1.5 rounded-full text-xs"
                    :class="
                        activeTab === tab
                            ? 'bg-white shadow text-gray-800'
                            : 'text-gray-400'
                    "
                >
                    {{ tab }}
                </button>
            </div>
            <div
                class="hidden lg:flex items-center gap-3 text-xs text-gray-500"
            >
                <div
                    class="hidden lg:flex items-center gap-3 text-xs text-gray-500"
                >
                    <span class="flex items-center gap-1.5"
                        ><span
                            class="w-2 h-2 rounded-full bg-emerald-500"
                        ></span
                        >Available</span
                    >
                    <span class="flex items-center gap-1.5"
                        ><span class="w-2 h-2 rounded-full bg-orange-500"></span
                        >Partial</span
                    >
                    <span class="flex items-center gap-1.5"
                        ><span class="w-2 h-2 rounded-full bg-sky-500"></span
                        >Occupied</span
                    >
                    <span class="flex items-center gap-1.5"
                        ><span class="w-2 h-2 rounded-full bg-rose-500"></span
                        >Maintenance</span
                    >
                </div>
            </div>
        </div>
    </div>
</template>
