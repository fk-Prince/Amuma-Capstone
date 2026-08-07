<template>
    <div class="w-full bg-white p-5 rounded-t-xl">
        <div
            class="flex items-center justify-between"
            :class="expanded ? 'mb-4' : ''"
        >
            <button
                type="button"
                class="flex items-center gap-3 text-left flex-1 min-w-0"
                @click="expanded = !expanded"
            >
                <div
                    class="h-10 w-10 shrink-0 rounded-xl bg-[#EAF4F2] flex items-center justify-center text-primary"
                >
                    <SlidersHorizontal class="h-5 w-5" />
                </div>

                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold text-[#16302E]">
                            Filter Schedule
                        </h3>

                        <span
                            v-if="!expanded && activeFilterCount > 0"
                            class="inline-flex items-center justify-center h-5 min-w-[1.25rem] px-1 rounded-full bg-primary text-white text-[10px] font-semibold"
                        >
                            {{ activeFilterCount }}
                        </span>
                    </div>

                    <p class="text-xs text-[#6B8A87] mt-1 truncate">
                        Refine appointments by date, status or provider
                    </p>
                </div>
            </button>

            <div class="flex items-center gap-3 shrink-0 pl-3">
                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="option in viewOptions"
                        :key="option.value"
                        type="button"
                        class="flex items-center justify-center gap-2 py-2.5 text-sm font-medium transition-colors"
                        @click="setView(option.value)"
                    >
                        <component :is="option.icon" class="h-6 w-6" />
                    </button>
                </div>
                <button
                    type="button"
                    class="h-8 w-8 rounded-lg flex items-center justify-center text-[#6B8A87] hover:bg-[#EAF4F2] hover:text-primary transition-colors"
                    :aria-expanded="expanded"
                    aria-label="Toggle filters"
                    @click="expanded = !expanded"
                >
                    <ChevronDown
                        class="h-4 w-4 transition-transform duration-300 ease-out"
                        :class="expanded ? 'rotate-180' : ''"
                    />
                </button>
            </div>
        </div>

        <Transition
            name="dropdown"
            @enter="onEnter"
            @after-enter="onAfterEnter"
            @leave="onLeave"
        >
            <div v-show="expanded" ref="panelRef" class="overflow-hidden">
                <div class="relative mb-4">
                    <Search
                        class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-[#6B8A87]"
                    />

                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Search patient, reference ID..."
                        class="w-full rounded-xl border border-[#EDF4F3] bg-[#FAFCFB] pl-9 pr-3 py-2.5 text-sm text-[#16302E] placeholder:text-[#9BB3B0] focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                        @input="emitChange()"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label
                            class="text-xs uppercase tracking-wide text-[#6B8A87] mb-1.5 block"
                        >
                            From
                        </label>

                        <div class="relative">
                            <CalendarDays
                                class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-[#6B8A87]"
                            />

                            <input
                                v-model="filters.date_from"
                                type="date"
                                class="w-full rounded-xl border border-[#EDF4F3] bg-[#FAFCFB] pl-9 pr-2 py-2.5 text-sm text-[#16302E] focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                                @change="emitChange(true)"
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            class="text-xs uppercase tracking-wide text-[#6B8A87] mb-1.5 block"
                        >
                            To
                        </label>

                        <div class="relative">
                            <CalendarDays
                                class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-[#6B8A87]"
                            />

                            <input
                                v-model="filters.date_to"
                                type="date"
                                class="w-full rounded-xl border border-[#EDF4F3] bg-[#FAFCFB] pl-9 pr-2 py-2.5 text-sm text-[#16302E] focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                                @change="emitChange(true)"
                            />
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <p
                        class="text-xs uppercase tracking-wide text-[#6B8A87] mb-2"
                    >
                        Status
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="status in statusOptions"
                            :key="status.value"
                            type="button"
                            class="px-3 py-1.5 rounded-full text-xs font-medium border transition-colors"
                            :class="
                                filters.statuses.includes(status.value)
                                    ? 'bg-primary border-primary text-white'
                                    : 'bg-[#FAFCFB] border-[#EDF4F3] text-[#6B8A87] hover:border-primary/40'
                            "
                            @click="toggleStatus(status.value)"
                        >
                            {{ status.label }}
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <p
                        class="text-xs uppercase tracking-wide text-[#6B8A87] mb-2"
                    >
                        Type
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="type in typeOptions"
                            :key="type.value"
                            type="button"
                            class="px-3 py-1.5 rounded-full text-xs font-medium border transition-colors"
                            :class="
                                filters.type.includes(type.value)
                                    ? 'bg-[#0E7C7B] border-[#0E7C7B] text-white'
                                    : 'bg-[#FAFCFB] border-[#EDF4F3] text-[#6B8A87] hover:border-[#0E7C7B]/40'
                            "
                            @click="toggleType(type.value)"
                        >
                            {{ type.label }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
<script setup lang="ts">
import { reactive, computed, watch, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
    Search,
    CalendarDays,
    ChevronDown,
    X,
    LayoutGrid,
    Table,
    SlidersHorizontal,
} from "lucide-vue-next";

export type ScheduleView = "card" | "table";
export type ScheduleTypeFilter = "medical" | "homecare";

export interface ScheduleFilters {
    search: string;
    date_from: string;
    date_to: string;
    statuses: string[];
    type: string[];
    view: ScheduleView;
}

const props = withDefaults(
    defineProps<{
        defaultExpanded?: boolean;
    }>(),
    {
        defaultExpanded: true,
    },
);

const emit = defineEmits<{
    (e: "change", filters: ScheduleFilters): void;
}>();

const route = useRoute();
const router = useRouter();

const expanded = ref(props.defaultExpanded);

const today = new Date().toISOString().slice(0, 10);

function toArray(value: unknown): string[] {
    if (Array.isArray(value)) {
        return value.map(String).filter(Boolean);
    }

    if (typeof value === "string" && value.length > 0) {
        return value.split(",").filter(Boolean);
    }

    return [];
}

function toStr(value: unknown): string {
    if (Array.isArray(value)) {
        return String(value[0] ?? "");
    }

    return typeof value === "string" ? value : "";
}

function filtersFromQuery(): ScheduleFilters {
    return {
        search: toStr(route.query.search),

        date_from: toStr(route.query.date_from) || today,

        date_to: toStr(route.query.date_to) || today,

        statuses: toArray(route.query.statuses),

        type: toArray(route.query.type).length
            ? toArray(route.query.type)
            : ["medical"],

        view: (toStr(route.query.view) as ScheduleView) || "card",
    };
}

const filters = reactive<ScheduleFilters>(filtersFromQuery());
onMounted(() => {
    const needsDefaultQuery =
        !route.query.date_from || !route.query.date_to || !route.query.type;

    if (!route.query.type) {
        filters.type = ["medical"];
    }
    if (needsDefaultQuery) {
        syncQuery();
    }
});
watch(
    () => route.query,
    () => {
        Object.assign(filters, filtersFromQuery());
    },
);

const viewOptions: {
    label: string;
    value: ScheduleView;
    icon: unknown;
}[] = [
    {
        label: "Card",
        value: "card",
        icon: LayoutGrid,
    },
    {
        label: "Table",
        value: "table",
        icon: Table,
    },
];

const statusOptions = [
    {
        label: "Upcoming",
        value: "upcoming",
    },
    {
        label: "In Progress",
        value: "in_progress",
    },
    {
        label: "Waiting",
        value: "waiting",
    },
    {
        label: "Completed",
        value: "completed",
    },
    {
        label: "Cancelled",
        value: "cancelled",
    },
];

const typeOptions = [
    {
        label: "Medical Services",
        value: "medical",
    },
    {
        label: "Activities of Daily Living",
        value: "adl",
    },
];

const activeFilterCount = computed(() => {
    let count = 0;

    if (filters.search) count++;

    if (filters.date_from || filters.date_to) count++;

    count += filters.statuses.length;

    count += filters.type.length;

    return count;
});

const setView = (value: ScheduleView) => {
    filters.view = value;
    emitChange(true);
};

const toggleStatus = (value: string) => {
    const index = filters.statuses.indexOf(value);

    if (index === -1) {
        filters.statuses.push(value);
    } else {
        filters.statuses.splice(index, 1);
    }

    emitChange(true);
};

const toggleType = (value: string) => {
    filters.type = [value];
    emitChange(true);
};

const resetFilters = () => {
    filters.search = "";
    filters.date_from = today;
    filters.date_to = today;
    filters.statuses = [];
    filters.type = [];
    emitChange(true);
};

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

function syncQuery() {
    const query: Record<string, string> = {
        ...(route.query as Record<string, string>),
    };
    const setOrDelete = (key: string, value: string) => {
        if (value) {
            query[key] = value;
        } else {
            delete query[key];
        }
    };
    setOrDelete("search", filters.search);
    setOrDelete("date_from", filters.date_from);
    setOrDelete("date_to", filters.date_to);
    setOrDelete("statuses", filters.statuses.join(","));
    setOrDelete("type", filters.type.join(","));
    setOrDelete("view", filters.view === "card" ? "" : filters.view);

    router.replace({
        query,
    });

    emit("change", {
        ...filters,
    });
}

const emitChange = (immediate = false) => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    if (immediate) {
        syncQuery();
        return;
    }

    debounceTimer = setTimeout(syncQuery, 250);
};

const onEnter = (el: Element) => {
    const panel = el as HTMLElement;

    panel.style.maxHeight = "0px";
    panel.style.opacity = "0";

    void panel.offsetHeight;

    panel.style.maxHeight = `${panel.scrollHeight}px`;
    panel.style.opacity = "1";
};

const onAfterEnter = (el: Element) => {
    (el as HTMLElement).style.maxHeight = "none";
};

const onLeave = (el: Element) => {
    const panel = el as HTMLElement;

    panel.style.maxHeight = `${panel.scrollHeight}px`;

    void panel.offsetHeight;

    panel.style.maxHeight = "0px";
    panel.style.opacity = "0";
};
</script>
<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition:
        max-height 0.3s ease,
        opacity 0.25s ease;
}
</style>
