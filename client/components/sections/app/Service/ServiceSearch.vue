<script setup lang="ts">
import { Search, Stethoscope, Globe2, Building2, Plus } from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import { Modules } from "~/types/module";
import { usePermissions } from "~/composables/usePermission";

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
</script>

<template>
    <div class="bg-white px-5 py-2 space-y-5">
        <div class="relative">
            <Search
                class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />

            <BaseInput
                :model-value="modelValue"
                @update:model-value="emit('update:modelValue', $event)"
                placeholder="Search services..."
                input-class="pl-11 rounded-xl border-[#E4EFED] focus:border-primary focus:ring-primary/20"
            />
        </div>

        <div
            class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
        >
            <div
                class="inline-flex w-full lg:w-fit overflow-x-auto rounded-xl bg-slate-100 p-1"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab.label"
                    type="button"
                    @click="emit('update:activeTab', tab.label)"
                    class="flex items-center gap-2 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-medium transition-all duration-200"
                    :class="
                        activeTab === tab.label
                            ? 'bg-white text-slate-900 shadow-sm'
                            : 'text-slate-500 hover:text-slate-700'
                    "
                >
                    <component :is="tab.icon" class="h-4 w-4" />

                    {{ tab.label }}
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <div
                    class="hidden items-center gap-5 text-xs text-slate-500 lg:flex"
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
        </div>
    </div>
</template>
