<script setup lang="ts">
import { ref } from "vue";
import type { Service } from "~/types/service";
import { Modules } from "~/types/module";
import { usePermissions } from "~/composables/usePermission";

const { canUpdate } = usePermissions();

const props = defineProps<{
    loading: boolean;
    services: Service[];
}>();

const emit = defineEmits<{
    edit: [service: Service];
}>();

const expandedServices = ref<number[]>([]);

const isExpanded = (service_id: number | undefined) => {
    if (service_id === undefined) return false;
    return expandedServices.value.includes(service_id);
};

const toggleService = (service_id: number | undefined) => {
    if (service_id === undefined) return false;
    expandedServices.value.includes(service_id)
        ? (expandedServices.value = expandedServices.value.filter(
              (x) => x !== service_id,
          ))
        : expandedServices.value.push(service_id);
};

const openEditService = (service: Service) => {
    emit("edit", service);
};

const formatDuration = (duration: string) => {
    const [h, m] = duration.split(":").map(Number);
    const parts = [];
    if (h != null && h > 0) parts.push(`${h}h`);
    if (m != null && m > 0) parts.push(`${m}m`);
    return parts.length ? parts.join(" ") : "—";
};

const formatPrice = (price: number | string) => {
    const num = Number(price);
    return isNaN(num) ? price : `₱${num.toFixed(2)}`;
};
</script>

<template>
    <div class="space-y-3 max-h-[560px]">
        <div class="overflow-hidden pr-1 space-y-3 scroll-thin p-3">
            <template v-if="loading">
                <div
                    v-for="n in 5"
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
                    v-if="services.length === 0"
                    class="flex flex-col items-center justify-center py-12 text-center"
                >
                    <svg
                        viewBox="0 0 24 24"
                        class="w-10 h-10 text-gray-300 mb-3"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M3 21h18M5 21V7l7-4 7 4v14" />
                        <path d="M9 21v-6h6v6" />
                    </svg>
                    <p class="text-sm font-medium text-gray-500">
                        No Service found
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        Try adjusting your search or filters, or add a new
                        service.
                    </p>
                </div>

                <template v-else>
                    <div
                        v-for="service in services"
                        :key="service.service_id"
                        class="rounded-xl border border-gray-100 border-l-4 bg-gray-50/60 overflow-hidden bg-white"
                        :class="
                            service.is_available
                                ? 'border-l-emerald-400'
                                : 'border-l-gray-300'
                        "
                    >
                        <button
                            type="button"
                            @click="toggleService(service.service_id)"
                            class="w-full text-left hover:bg-gray-100/60 transition-colors p-4 flex items-center justify-between gap-3"
                        >
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-gray-800">
                                        {{ service.service_name }}
                                    </p>
                                    <span
                                        v-if="service.category_name"
                                        class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-violet-100 text-violet-600"
                                    >
                                        {{ service.category_name }}
                                    </span>
                                </div>
                                <div
                                    class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1.5 text-xs text-gray-400"
                                >
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
                                    <span class="flex items-center gap-1">
                                        <svg
                                            viewBox="0 0 24 24"
                                            class="w-3.5 h-3.5"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <circle cx="12" cy="12" r="9" />
                                            <path d="M12 7v5l3 3" />
                                        </svg>
                                        {{
                                            formatDuration(
                                                service.maximum_duration,
                                            )
                                        }}
                                    </span>
                                    <span class="text-gray-200">|</span>
                                    <span
                                        class="flex items-center gap-1 font-medium text-gray-600"
                                    >
                                        {{ formatPrice(service.price) }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 shrink-0">
                                <span
                                    class="text-[10px] font-medium px-2 py-0.5 rounded-full"
                                    :class="
                                        service.is_available
                                            ? 'bg-emerald-100 text-emerald-600'
                                            : 'bg-gray-100 text-gray-500'
                                    "
                                >
                                    {{
                                        service.is_available
                                            ? "Available"
                                            : "Unavailable"
                                    }}
                                </span>

                                <svg
                                    viewBox="0 0 24 24"
                                    class="w-4 h-4 text-gray-400 shrink-0 transition-transform duration-200"
                                    :class="{
                                        'rotate-180': isExpanded(
                                            service.service_id,
                                        ),
                                    }"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        d="M6 9l6 6 6-6"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </div>
                        </button>

                        <div
                            v-if="isExpanded(service.service_id)"
                            class="px-4 pb-4 border-t border-gray-100 pt-4"
                        >
                            <div class="flex items-center gap-2 mb-3">
                                <button
                                    v-if="canUpdate(Modules.Services)"
                                    type="button"
                                    @click.stop="openEditService(service)"
                                    class="flex items-center gap-1.5 text-xs font-medium text-gray-600 border border-gray-200 rounded-lg px-3 py-1.5 bg-white hover:border-blue-300 hover:text-blue-600 transition-colors"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        class="w-3.5 h-3.5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                    Edit Service
                                </button>

                                <button
                                    type="button"
                                    class="flex items-center gap-1.5 text-xs font-medium text-rose-500 border border-rose-200 rounded-lg px-3 py-1.5 bg-white hover:bg-rose-50 transition-colors"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        class="w-3.5 h-3.5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                    Delete
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div
                                    class="rounded-lg bg-white border border-gray-100 p-3"
                                >
                                    <p class="text-gray-400 mb-0.5">Price</p>
                                    <p class="font-semibold text-gray-700">
                                        {{ formatPrice(service.price) }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-lg bg-white border border-gray-100 p-3"
                                >
                                    <p class="text-gray-400 mb-0.5">Duration</p>
                                    <p class="font-semibold text-gray-700">
                                        {{
                                            formatDuration(
                                                service.maximum_duration,
                                            )
                                        }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-lg bg-white border border-gray-100 p-3"
                                >
                                    <p class="text-gray-400 mb-0.5">Type</p>
                                    <p
                                        class="font-semibold text-gray-700 capitalize"
                                    >
                                        {{ service.type }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-lg bg-white border border-gray-100 p-3"
                                >
                                    <p class="text-gray-400 mb-0.5">Category</p>
                                    <p class="font-semibold text-gray-700">
                                        {{ service.category_name || "—" }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </template>
        </div>
    </div>
</template>
