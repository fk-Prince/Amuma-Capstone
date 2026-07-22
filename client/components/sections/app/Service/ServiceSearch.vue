<script setup lang="ts">
import BaseInput from "~/components/ui/BaseInput.vue";
import { Modules } from "~/types/module";
import { usePermissions } from "~/composables/usePermission";

import { Search, Plus } from "lucide-vue-next";

const { canCreate } = usePermissions();

defineProps<{
    modelValue: string;
    activeTab: string;
}>();

const emit = defineEmits<{
    "update:modelValue": [value: string];
    "update:activeTab": [value: string];
    addService: [];
}>();

const tabs = [
    "All Services",
    "Online Medical Services",
    "Facility Medical Services",
];
</script>

<template>
    <div class="space-y-4">
        <div class="relative">
            <Search
                class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-[#9AB3AF]"
            />

            <BaseInput
                :model-value="modelValue"
                @update:model-value="emit('update:modelValue', $event)"
                placeholder="Search services..."
                input-class="pl-[3rem] rounded-xl border-[#E4EFED] focus:border-[#0E7C7B] focus:ring-[#0E7C7B]/20"
            />
        </div>

        <div
            class="flex flex-col lg:flex-row lg:items-center justify-between gap-4"
        >
            <div
                class="inline-flex w-full lg:w-fit overflow-x-auto items-center gap-1 rounded-2xl bg-[#F7FAF9] border border-[#E4EFED] p-1"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab"
                    type="button"
                    @click="emit('update:activeTab', tab)"
                    class="relative whitespace-nowrap px-4 py-2 rounded-xl text-xs font-medium transition-all duration-200"
                    :class="
                        activeTab === tab
                            ? `
                                bg-white
                                text-[#16302E]
                                shadow-sm
                                ring-1
                                ring-[#E4EFED]
                            `
                            : `
                                text-[#6B8A87]
                                hover:text-[#16302E]
                                hover:bg-white/60
                            `
                    "
                >
                    {{ tab }}
                </button>
            </div>

            <button
                v-if="canCreate(Modules.Services)"
                type="button"
                @click="emit('addService')"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 h-11 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:opacity-90 hover:shadow-md active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-primary/30"
            >
                <Plus class="w-4 h-4" />

                <span> Add Service </span>
            </button>
        </div>
    </div>
</template>
