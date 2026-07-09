<script setup lang="ts">
import type { Service } from "~/types/service";

const props = defineProps<{
    loading: boolean;
    services: Service[];
}>();

const emit = defineEmits<{
    toggle: [room_id: number];
}>();

// const toggleRoom = (service_id: number | undefined) => {
//     emit("toggle", service_id);
// };
</script>

<template>
    <div class="space-y-3 max-h-[560px]">
        <div class="overflow-hidden pr-1 space-y-3 scroll-thin p-3">
            <template v-if="loading">
                <div
                    v-for="n in 2"
                    :key="n"
                    class="rounded-xl border border-gray-100 border-l-4 border-l-gray-200 bg-white p-4 animate-pulse"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="h-5 w-24 bg-gray-200 rounded"></div>

                            <div class="flex gap-2 mt-3">
                                <div class="h-3 w-20 bg-gray-200 rounded"></div>
                                <div class="h-3 w-16 bg-gray-200 rounded"></div>
                                <div class="h-3 w-24 bg-gray-200 rounded"></div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="w-3 h-3 rounded-full bg-gray-200"></div>
                            <div class="w-4 h-4 rounded bg-gray-200"></div>
                        </div>
                    </div>
                </div>
            </template>
            <template v-else>
                <div
                    v-for="service in services"
                    :key="service.service_id"
                    class="rounded-xl border border-gray-100 border-l-4 bg-gray-50/60 overflow-hidden bg-white"
                >
                    <!-- @click="toggleRoom(service.service_id)" -->
                    <button
                        type="button"
                        class="w-full text-left hover:bg-gray-100/60 transition-colors p-4 flex items-center justify-between gap-3"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-gray-800">
                                    {{ service.service_name }}
                                </p>
                            </div>
                            <div
                                class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1.5 text-xs text-gray-400"
                            >
                                <span class="text-gray-200">|</span>
                                <span class="flex items-center gap-1">
                                    <svg
                                        viewBox="0 0 24 24"
                                        class="w-3.5 h-3.5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            d="M2.5 19h19l-1.7-9.3-4.8 4-3-6.7-3 6.7-4.8-4Z"
                                        />
                                    </svg>

                                    {{ service.type }}
                                </span>
                                <span class="text-gray-200">|</span>
                            </div>
                        </div>
                    </button>
                </div>
            </template>
        </div>
    </div>
</template>
