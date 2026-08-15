<template>
    <div class="flex gap-3 items-center flex-1">
        <div class="relative flex-1">
            <svg
                class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted"
                viewBox="0 0 20 20"
                fill="none"
                stroke="currentColor"
                stroke-width="1.75"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <circle cx="9" cy="9" r="6" />
                <path d="m17 17-4-4" />
            </svg>

            <BaseInput
                mode="text"
                :modelValue="search"
                @update:modelValue="$emit('update:search', $event)"
                placeholder="Search by patient name"
                inputClass="pl-[2.3rem]"
            />

            <button
                v-if="search"
                type="button"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[#9AB3AF] hover:text-[#16302E] transition"
                @click="$emit('update:search', '')"
            >
                <svg
                    class="h-4 w-4"
                    viewBox="0 0 20 20"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M5 5l10 10M15 5 5 15" />
                </svg>
            </button>
        </div>

        <div class="relative shrink-0">
            <button
                type="button"
                class="flex items-center gap-4 px-4 py-2.5 text-sm border border-[#E4EFED] rounded-lg bg-white hover:bg-[#F7FAF9] transition"
                @click="open = !open"
            >
                <span class="text-muted">
                    Care Type
                    <span class="text-[#16302E] font-medium ml-1">{{
                        typeSummary
                    }}</span>
                </span>
                <span class="text-[#E4EFED]">|</span>
                <span class="text-muted">
                    Period
                    <span class="text-[#16302E] font-medium ml-1">{{
                        periodSummary
                    }}</span>
                </span>
                <ChevronDown
                    class="h-4 w-4 text-muted transition-transform"
                    :class="{ 'rotate-180': open }"
                />
            </button>

            <div v-if="open" class="fixed inset-0 z-20" @click="open = false" />

            <transition name="fade-slide">
                <div
                    v-if="open"
                    class="absolute right-0 z-30 mt-2 w-[630px] max-w-[90vw] bg-white rounded-xl shadow-lg border border-[#E4EFED] p-6"
                >
                    <button
                        type="button"
                        class="absolute right-4 top-4 text-muted hover:text-[#16302E]"
                        @click="open = false"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 20 20"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.75"
                            stroke-linecap="round"
                        >
                            <path d="M5 5l10 10M15 5 5 15" />
                        </svg>
                    </button>

                    <div class="flex items-start gap-6 py-2">
                        <p
                            class="w-20 shrink-0 text-sm font-semibold text-[#16302E] pt-1.5"
                        >
                            Care Type
                        </p>
                        <div class="flex flex-wrap gap-x-5 gap-y-2">
                            <button
                                v-for="item in careTypeFilters"
                                :key="item.value"
                                type="button"
                                class="text-sm transition"
                                :class="
                                    localType === item.value
                                        ? 'text-primary font-medium'
                                        : 'text-[#16302E] hover:text-primary'
                                "
                                @click="localType = item.value"
                            >
                                {{ item.label }}
                            </button>
                        </div>
                    </div>

                    <div class="h-px bg-[#E4EFED] my-3" />

                    <div class="flex items-start gap-6 py-2">
                        <p
                            class="w-20 shrink-0 text-sm font-semibold text-[#16302E] pt-1.5"
                        >
                            Period
                        </p>
                        <div class="flex flex-col gap-2.5">
                            <div class="flex flex-wrap gap-x-5 gap-y-2">
                                <button
                                    v-for="p in periodPresets"
                                    :key="p.value"
                                    type="button"
                                    class="text-sm transition"
                                    :class="
                                        activePreset === p.value
                                            ? 'text-primary font-medium'
                                            : 'text-[#16302E] hover:text-primary'
                                    "
                                    @click="applyPreset(p.value)"
                                >
                                    {{ p.label }}
                                </button>
                            </div>

                            <div class="flex items-center gap-2 w-full">
                                <BaseInput
                                    v-model="localDateFrom"
                                    mode="date"
                                    class-name="w-[140px]"
                                    @update:modelValue="activePreset = null"
                                />
                                <span class="text-muted text-xs">to</span>
                                <BaseInput
                                    v-model="localDateTo"
                                    mode="date"
                                    class-name="w-[140px]"
                                    @update:modelValue="activePreset = null"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="h-px bg-[#E4EFED] my-4" />

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            class="px-4 py-2 text-sm font-medium text-muted hover:text-[#16302E] transition"
                            @click="resetAll"
                        >
                            Reset
                        </button>
                        <button
                            type="button"
                            class="px-5 py-2 text-sm font-medium rounded-lg bg-primary text-white hover:opacity-90 transition"
                            @click="applyAndClose"
                        >
                            Apply
                        </button>
                    </div>
                </div>
            </transition>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { ChevronDown } from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";

const props = defineProps<{
    search: string;
    type: string;
    dateFrom: string;
    dateTo: string;
}>();

const emit = defineEmits<{
    (e: "update:search", value: string): void;
    (e: "update:type", value: string): void;
    (e: "update:dateFrom", value: string): void;
    (e: "update:dateTo", value: string): void;
}>();

const careTypeFilters = [
    { label: "All Category", value: "all" },
    { label: "In-house Facility", value: "facility" },
    { label: "Homecare", value: "homecare" },
];

const open = ref(false);

const localType = ref(props.type);
const localDateFrom = ref(props.dateFrom);
const localDateTo = ref(props.dateTo);
const activePreset = ref<string | null>(null);

const periodPresets = [
    { label: "All", value: "all" },
    { label: "1 Day", value: "1d" },
    { label: "1 Week", value: "1w" },
    { label: "1 Month", value: "1m" },
    { label: "1 Year", value: "1y" },
];

watch(
    () => props.type,
    (v) => (localType.value = v),
);
watch(
    () => props.dateFrom,
    (v) => (localDateFrom.value = v),
);
watch(
    () => props.dateTo,
    (v) => (localDateTo.value = v),
);

function applyPreset(value: string) {
    activePreset.value = value;
    const today = new Date();
    const to = today.toISOString().slice(0, 10);

    if (value === "all") {
        localDateFrom.value = "";
        localDateTo.value = "";
        return;
    }

    const start = new Date(today);
    if (value === "1d") start.setDate(start.getDate() - 1);
    if (value === "1w") start.setDate(start.getDate() - 7);
    if (value === "1m") start.setMonth(start.getMonth() - 1);
    if (value === "1y") start.setFullYear(start.getFullYear() - 1);

    localDateFrom.value = start.toISOString().slice(0, 10);
    localDateTo.value = to;
}

function resetAll() {
    localType.value = "all";
    localDateFrom.value = "";
    localDateTo.value = "";
    activePreset.value = "all";
}

function applyAndClose() {
    emit("update:type", localType.value);
    emit("update:dateFrom", localDateFrom.value);
    emit("update:dateTo", localDateTo.value);
    open.value = false;
}

const typeSummary = computed(() => {
    const found = careTypeFilters.find((t) => t.value === props.type);
    return found?.label ?? "All Category";
});

const periodSummary = computed(() => {
    if (!props.dateFrom && !props.dateTo) return "All";
    if (props.dateFrom && props.dateTo)
        return `${props.dateFrom} → ${props.dateTo}`;
    return props.dateFrom || props.dateTo;
});
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.15s ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
