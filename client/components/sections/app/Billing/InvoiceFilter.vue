<template>
    <div class="w-full bg-white p-5 rounded-t-xl dark:bg-secondary">
        <div
            class="flex items-center justify-between"
            :class="expanded ? 'mb-4' : ''"
        >
            <button
                type="button"
                class="flex items-center gap-3 text-left"
                @click="expanded = !expanded"
            >
                <div
                    class="h-10 w-10 rounded-xl bg-[#EAF4F2] flex items-center justify-center text-primary dark:bg-accent-500/15"
                >
                    <SlidersHorizontal class="h-5 w-5" />
                </div>

                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold text-[#16302E] dark:text-white">
                            Filter Invoice
                        </h3>

                        <span
                            v-if="!expanded && activeFilterCount"
                            class="h-5 min-w-5 px-1 rounded-full bg-primary text-white text-[10px] flex items-center justify-center"
                        >
                            {{ activeFilterCount }}
                        </span>
                    </div>

                    <p class="text-xs text-[#6B8A87] dark:text-gray-400">
                        Search and filter invoice date range
                    </p>
                </div>
            </button>

            <button
                type="button"
                class="h-8 w-8 rounded-lg hover:bg-[#EAF4F2] dark:hover:bg-accent-500/15"
                @click="expanded = !expanded"
            >
                <ChevronDown
                    class="h-4 w-4 mx-auto transition-transform"
                    :class="expanded ? 'rotate-180' : ''"
                />
            </button>
        </div>

        <Transition name="dropdown">
            <div v-show="expanded" class="overflow-hidden">
                <div class="mb-4 relative">
                    <Search
                        class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-[#6B8A87] dark:text-gray-400"
                    />

                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Search invoice code..."
                        class="w-full rounded-xl border border-[#EDF4F3] bg-[#FAFCFB] pl-9 py-2.5 text-sm dark:border-white/10 dark:bg-white/5"
                        @input="emitChange"
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-[#6B8A87] dark:text-gray-400"> From </label>

                        <input
                            v-model="filters.dateFrom"
                            type="datetime-local"
                            class="w-full rounded-xl border border-[#EDF4F3] px-3 py-2.5 text-sm dark:border-white/10"
                            @change="emitChange"
                        />
                    </div>

                    <div>
                        <label class="text-xs text-[#6B8A87] dark:text-gray-400"> To </label>

                        <input
                            v-model="filters.dateTo"
                            type="datetime-local"
                            class="w-full rounded-xl border border-[#EDF4F3] px-3 py-2.5 text-sm dark:border-white/10"
                            @change="emitChange"
                        />
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { reactive, computed, ref } from "vue";
import { Search, ChevronDown, SlidersHorizontal } from "lucide-vue-next";

export interface InvoiceFilters {
    search: string;
    dateFrom: string;
    dateTo: string;
}

const today = new Date();

const startToday = new Date(
    today.getFullYear(),
    today.getMonth(),
    today.getDate(),
    0,
    0,
    0,
);

const endToday = new Date(
    today.getFullYear(),
    today.getMonth(),
    today.getDate(),
    23,
    59,
    59,
);

const props = withDefaults(
    defineProps<{
        modelValue?: Partial<InvoiceFilters>;
    }>(),
    {
        modelValue: () => ({}),
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: InvoiceFilters): void;
}>();

const expanded = ref(true);

const filters = reactive<InvoiceFilters>({
    search: props.modelValue?.search ?? "",

    dateFrom:
        props.modelValue?.dateFrom ?? startToday.toISOString().slice(0, 16),

    dateTo: props.modelValue?.dateTo ?? endToday.toISOString().slice(0, 16),
});

const activeFilterCount = computed(() => {
    let count = 0;

    if (filters.search) count++;

    if (filters.dateFrom || filters.dateTo) count++;

    return count;
});

let timer: any;

function emitChange() {
    clearTimeout(timer);

    timer = setTimeout(() => {
        emit("update:modelValue", {
            ...filters,
        });
    }, 250);
}
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition:
        max-height 0.3s ease,
        opacity 0.25s ease;
}
</style>
