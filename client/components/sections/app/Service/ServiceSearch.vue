<script setup lang="ts">
import { computed } from "vue";
import { Search, Stethoscope, Globe2, Building2, Plus } from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import { Modules } from "~/types/module";
import { usePermissions } from "~/composables/usePermission";

const { canCreate } = usePermissions();

const props = defineProps<{
    modelValue: string;
    activeTab: string;
}>();

const emit = defineEmits<{
    "update:modelValue": [value: string];
    "update:activeTab": [value: string];
    addService: [];
}>();

const tabs = [
    {
        label: "All Services",
        icon: Stethoscope,
    },
    {
        label: "Homecare Services",
        icon: Globe2,
    },
    {
        label: "Facility Services",
        icon: Building2,
    },
];

const activeIndex = computed(() =>
    Math.max(
        0,
        tabs.findIndex((t) => t.label === props.activeTab),
    ),
);

const sliderOffset = computed(() => `${activeIndex.value * 100}%`);
</script>

<template>
    <div class="bg-white px-5 py-2 space-y-5 dark:bg-secondary">
        <div class="flex gap-2">
            <div class="relative flex-1">
                <Search
                    class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500"
                />

                <BaseInput
                    :model-value="modelValue"
                    @update:model-value="emit('update:modelValue', $event)"
                    placeholder="Search service or category..."
                    input-class="pl-11 rounded-xl border-[#E4EFED] focus:border-primary focus:ring-primary/20 dark:border-white/10"
                />
            </div>

            <button
                v-if="canCreate(Modules.Services)"
                type="button"
                @click="emit('addService')"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-primary px-5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md active:scale-[0.98]"
            >
                <Plus class="h-4 w-4" />

                Add Service
            </button>
        </div>

        <div
            class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
        >
            <div
                class="relative inline-grid w-full grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 rounded-xl border border-slate-200 bg-white p-1 shadow-sm lg:w-auto dark:border-white/10 dark:bg-secondary"
            >
                <div
                    class="absolute inset-y-1 left-1 rounded-lg bg-primary transition-transform duration-300 ease-out"
                    :style="{
                        width: 'calc((100% - 0.45rem) / 3)',
                        transform: `translateX(${sliderOffset})`,
                    }"
                />

                <button
                    v-for="tab in tabs"
                    :key="tab.label"
                    type="button"
                    class="relative z-10 flex items-center justify-center gap-2 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-medium transition-colors duration-200"
                    :class="
                        activeTab === tab.label
                            ? 'text-white'
                            : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-400'
                    "
                    @click="emit('update:activeTab', tab.label)"
                >
                    <component :is="tab.icon" class="h-4 w-4" />

                    {{ tab.label }}
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <div
                    class="hidden items-center gap-5 text-xs text-slate-500 lg:flex dark:text-gray-400"
                >
                    <span class="flex items-center gap-2">
                        <span
                            class="h-2.5 w-2.5 rounded-full bg-emerald-500"
                        ></span>
                        Available
                    </span>

                    <span class="flex items-center gap-2">
                        <span
                            class="h-2.5 w-2.5 rounded-full bg-slate-400"
                        ></span>
                        Unavailable
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
