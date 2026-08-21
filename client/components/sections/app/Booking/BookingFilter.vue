<template>
    <div
        class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center flex-1"
    >
        <div class="relative flex-1 min-w-0">
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
                placeholder="Search by Reference ID or Patient name"
                inputClass="pl-[2.3rem] w-full"
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
                ref="filterButton"
                type="button"
                class="flex items-center justify-center gap-2 w-full sm:w-auto px-4 py-2.5 text-sm border border-[#E4EFED] rounded-lg bg-white hover:bg-[#F7FAF9] transition"
                @click="toggleFilters"
            >
                <svg
                    class="h-4 w-4 text-[#6B8A87]"
                    viewBox="0 0 20 20"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M3 5h14" />
                    <path d="M5 10h10" />
                    <path d="M8 15h4" />
                </svg>

                <span class="font-medium text-[#16302E]">Filters</span>

                <ChevronDown
                    class="h-4 w-4 text-muted transition-transform"
                    :class="{ 'rotate-180': open }"
                />
            </button>

            <Teleport to="body">
                <div
                    v-if="open"
                    class="fixed inset-0 z-[9998]"
                    @click="open = false"
                />

                <Transition name="fade-slide">
                    <div
                        v-if="open"
                        class="fixed z-[9999] w-[630px] max-w-[calc(100vw-1rem)] bg-white rounded-xl shadow-xl border border-[#E4EFED] p-4 sm:p-6"
                        :style="dropdownStyle"
                        @click.stop
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

                        <div
                            class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-6 py-2"
                        >
                            <p
                                class="sm:w-20 shrink-0 text-sm font-semibold text-[#16302E]"
                            >
                                Type
                            </p>

                            <div class="flex flex-wrap gap-x-5 gap-y-2">
                                <button
                                    v-for="item in typeFilters"
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

                        <div
                            class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-6 py-2"
                        >
                            <p
                                class="sm:w-20 shrink-0 text-sm font-semibold text-[#16302E]"
                            >
                                Period
                            </p>

                            <div class="flex flex-col gap-2.5 min-w-0">
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

                                <div
                                    class="flex flex-row flex-nowrap items-center gap-2"
                                >
                                    <BaseInput
                                        v-model="localDateFrom"
                                        mode="date"
                                        class-name="w-full min-w-0 xs:w-[140px]"
                                        @update:modelValue="activePreset = null"
                                    />

                                    <span
                                        class="text-muted text-xs text-center shrink-0"
                                    >
                                        to
                                    </span>

                                    <BaseInput
                                        v-model="localDateTo"
                                        mode="date"
                                        class-name="w-full min-w-0 xs:w-[140px]"
                                        @update:modelValue="activePreset = null"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="h-px bg-[#E4EFED] my-3" />

                        <div
                            class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-6 py-2"
                        >
                            <p
                                class="sm:w-20 shrink-0 text-sm font-semibold text-[#16302E]"
                            >
                                Status
                            </p>

                            <div class="flex flex-wrap gap-x-5 gap-y-2">
                                <button
                                    v-for="item in statusFilters"
                                    :key="item.value"
                                    type="button"
                                    class="text-sm transition"
                                    :class="
                                        localStatus === item.value
                                            ? 'text-primary font-medium'
                                            : 'text-[#16302E] hover:text-primary'
                                    "
                                    @click="localStatus = item.value"
                                >
                                    {{ item.label }}
                                </button>
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
                </Transition>
            </Teleport>
        </div>
    </div>
</template>

<script setup lang="ts">
import {
    ref,
    computed,
    watch,
    onMounted,
    onBeforeUnmount,
    nextTick,
} from "vue";
import { ChevronDown } from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import { typeFilters, statusFilters } from "~/types/booking";

const props = defineProps<{
    search: string;
    type: string;
    status: string;
    dateFrom: string;
    dateTo: string;
}>();

const emit = defineEmits<{
    (e: "update:search", value: string): void;
    (e: "update:type", value: string): void;
    (e: "update:status", value: string): void;
    (e: "update:dateFrom", value: string): void;
    (e: "update:dateTo", value: string): void;
}>();

const open = ref(false);
const filterButton = ref<HTMLElement | null>(null);

const dropdownStyle = ref<Record<string, string>>({
    top: "0px",
    left: "0px",
});

const DROPDOWN_WIDTH = 630;
const DROPDOWN_HEIGHT = 440;
const SCREEN_GAP = 16;

const updateDropdownPosition = () => {
    if (!filterButton.value || !open.value) return;

    const rect = filterButton.value.getBoundingClientRect();

    const width = Math.min(DROPDOWN_WIDTH, window.innerWidth - SCREEN_GAP * 2);

    const height = Math.min(
        DROPDOWN_HEIGHT,
        window.innerHeight - SCREEN_GAP * 2,
    );

    let left = rect.right - width;

    left = Math.max(
        SCREEN_GAP,
        Math.min(left, window.innerWidth - width - SCREEN_GAP),
    );

    let top = rect.bottom + 8;

    if (top + height > window.innerHeight - SCREEN_GAP) {
        top = rect.top - height - 8;
    }

    if (top < SCREEN_GAP) {
        top = SCREEN_GAP;
    }

    dropdownStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
        maxHeight: `${height}px`,
        overflowY: "auto",
    };
};

const toggleFilters = async () => {
    open.value = !open.value;

    if (open.value) {
        await nextTick();
        updateDropdownPosition();
    }
};

const handleViewportChange = () => {
    if (open.value) {
        updateDropdownPosition();
    }
};

const getLocalDateStr = (date: Date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
};

const getDefaultDateRange = () => {
    const today = new Date();

    const from = new Date(today);
    from.setDate(from.getDate() - 1);

    const to = new Date(today);
    to.setDate(to.getDate() + 7);

    return {
        from: getLocalDateStr(from),
        to: getLocalDateStr(to),
    };
};

const defaultDates = getDefaultDateRange();

const localType = ref(props.type);
const localStatus = ref(props.status);
const localDateFrom = ref(props.dateFrom || defaultDates.from);
const localDateTo = ref(props.dateTo || defaultDates.to);

const activePreset = ref<string | null>(
    props.dateFrom || props.dateTo ? null : "1w",
);

const periodPresets = [
    { label: "All", value: "all" },
    { label: "1 Day", value: "1d" },
    { label: "1 Week", value: "1w" },
    { label: "1 Month", value: "1m" },
    { label: "1 Year", value: "1y" },
];

const activeFilterCount = computed(() => {
    let count = 0;

    if (props.type && props.type !== "all") count++;
    if (props.status && props.status !== "all") count++;

    if (props.dateFrom || props.dateTo) count++;

    return count;
});

watch(
    () => props.type,
    (v) => {
        localType.value = v;
    },
);

watch(
    () => props.status,
    (v) => {
        localStatus.value = v;
    },
);

watch(
    () => props.dateFrom,
    (v) => {
        localDateFrom.value = v;
    },
);

watch(
    () => props.dateTo,
    (v) => {
        localDateTo.value = v;
    },
);

function applyPreset(value: string) {
    activePreset.value = value;

    if (value === "all") {
        localDateFrom.value = "";
        localDateTo.value = "";
        return;
    }

    const today = new Date();
    const to = new Date(today);
    const from = new Date(today);

    if (value === "1d") {
        from.setDate(from.getDate() - 1);
        to.setDate(to.getDate() + 1);
    }

    if (value === "1w") {
        from.setDate(from.getDate() - 1);
        to.setDate(to.getDate() + 7);
    }

    if (value === "1m") {
        from.setMonth(from.getMonth() - 1);
        to.setDate(to.getDate() + 7);
    }

    if (value === "1y") {
        from.setFullYear(from.getFullYear() - 1);
        to.setDate(to.getDate() + 7);
    }

    localDateFrom.value = getLocalDateStr(from);
    localDateTo.value = getLocalDateStr(to);
}

function resetAll() {
    localType.value = "all";
    localStatus.value = "all";

    const defaults = getDefaultDateRange();

    localDateFrom.value = defaults.from;
    localDateTo.value = defaults.to;
    activePreset.value = "1w";
}

function applyAndClose() {
    emit("update:type", localType.value);
    emit("update:status", localStatus.value);
    emit("update:dateFrom", localDateFrom.value);
    emit("update:dateTo", localDateTo.value);

    open.value = false;
}

const typeSummary = computed(() => {
    const found = typeFilters.find((t: any) => t.value === props.type);
    return found?.label ?? "All";
});

const statusSummary = computed(() => {
    const found = statusFilters.find((s: any) => s.value === props.status);
    return found?.label ?? "All";
});

const periodSummary = computed(() => {
    if (!props.dateFrom && !props.dateTo) return "All";

    if (props.dateFrom && props.dateTo) {
        return `${props.dateFrom} → ${props.dateTo}`;
    }

    return props.dateFrom || props.dateTo;
});

onMounted(() => {
    if (!props.dateFrom) {
        emit("update:dateFrom", defaultDates.from);
    }

    if (!props.dateTo) {
        emit("update:dateTo", defaultDates.to);
    }

    window.addEventListener("resize", handleViewportChange);
    window.addEventListener("scroll", handleViewportChange, true);
});

onBeforeUnmount(() => {
    window.removeEventListener("resize", handleViewportChange);
    window.removeEventListener("scroll", handleViewportChange, true);
});
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition:
        opacity 0.15s ease,
        transform 0.15s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
