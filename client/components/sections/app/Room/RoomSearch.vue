<script setup lang="ts">
import { Search, BedDouble, Crown, Building2 } from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";

defineProps<{
    modelValue: string;
    activeTab: string;
}>();

const emit = defineEmits<{
    "update:modelValue": [value: string];
    "update:activeTab": [value: string];
    addRoom: [];
    search: [];
}>();

const tabs = [
    {
        label: "All Rooms",
        icon: Building2,
    },
    {
        label: "VIP Rooms",
        icon: Crown,
    },
    {
        label: "Common Rooms",
        icon: BedDouble,
    },
];
import { Modules } from "~/types/module";

const { canCreate } = usePermissions();
</script>

<template>
    <div class="bg-white px-5 py-2 space-y-5">
        <div class="flex gap-2">
            <div class="relative flex-1">
                <div class="relative flex-1">
                    <Search
                        class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    />

                    <BaseInput
                        :model-value="modelValue"
                        @update:model-value="emit('update:modelValue', $event)"
                        placeholder="Search rooms, beds, or residents..."
                        input-class="pl-11"
                        @keyup.enter="emit('search')"
                    />
                </div>
            </div>

            <button
                type="button"
                v-if="canCreate(Modules.RoomsAndBeds)"
                @click="emit('addRoom')"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-primary px-5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md active:scale-[0.98]"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 4v16m8-8H4"
                    />
                </svg>

                Add Room<
            </button>
        </div>

        <div
            class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
        >
            <div class="flex items-center md:flex-row flex-col gap-5">
                <div class="inline-flex w-fit rounded-xl bg-slate-100 p-1">
                    <button
                        v-for="tab in tabs"
                        :key="tab.label"
                        type="button"
                        @click="emit('update:activeTab', tab.label)"
                        class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all duration-200"
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

                <button
                    type="button"
                    class="flex items-center justify-center md:w-fit w-full gap-2 rounded-lg bg-primary px-5 py-2 text-sm font-medium text-white transition hover:bg-primary/50"
                    @click="emit('search')"
                >
                    <Search class="h-4 w-4" />
                    Search
                </button>
            </div>

            <div
                class="hidden flex-wrap items-center gap-5 text-xs text-slate-500 lg:flex"
            >
                <span class="flex items-center gap-2">
                    <span
                        class="h-2.5 w-2.5 rounded-full bg-emerald-500"
                    ></span>
                    Available
                </span>

                <span class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                    Partial
                </span>

                <span class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>
                    Occupied
                </span>

                <span class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                    Maintenance
                </span>
            </div>
        </div>
    </div>
</template>
