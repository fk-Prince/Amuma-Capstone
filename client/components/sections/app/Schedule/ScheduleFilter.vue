<template>
    <div
        class="w-full bg-white p-5 rounded-t-2xl border border-muted-light/70 shadow-sm shadow-secondary/[0.03] font-sans"
    >
        <div
            class="flex items-center justify-between gap-3"
            :class="expanded ? 'mb-4' : ''"
        >
            <button
                type="button"
                class="flex items-center gap-3 text-left flex-1 min-w-0 group"
                @click="expanded = !expanded"
            >
                <div
                    class="h-10 w-10 shrink-0 rounded-xl bg-primary-50 flex items-center justify-center text-primary-600 transition-colors group-hover:bg-primary-100"
                >
                    <SlidersHorizontal class="h-5 w-5" />
                </div>

                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold text-secondary tracking-tight">
                            Filter Schedule
                        </h3>

                        <span
                            v-if="!expanded && activeFilterCount > 0"
                            class="inline-flex items-center justify-center h-5 min-w-[1.25rem] px-1.5 rounded-full bg-primary text-white text-[10px] font-semibold"
                        >
                            {{ activeFilterCount }}
                        </span>
                    </div>

                    <p class="text-xs text-muted mt-0.5 truncate">
                        Refine appointments by date, status or provider
                    </p>
                </div>
            </button>

            <div class="flex items-center gap-2 shrink-0 pl-2">
                <div
                    class="flex items-center gap-1 rounded-xl bg-muted-light p-1"
                >
                    <button
                        v-for="option in viewOptions"
                        :key="option.value"
                        type="button"
                        :aria-label="option.label"
                        :aria-pressed="filters.view === option.value"
                        class="flex items-center justify-center h-8 w-8 rounded-lg transition-colors"
                        :class="
                            filters.view === option.value
                                ? 'bg-white text-primary-600 shadow-sm'
                                : 'text-muted hover:text-secondary'
                        "
                        @click="setView(option.value)"
                    >
                        <component :is="option.icon" class="h-4 w-4" />
                    </button>
                </div>

                <button
                    v-if="activeFilterCount > 0"
                    type="button"
                    class="hidden sm:flex items-center gap-1 h-8 px-2.5 rounded-lg text-xs font-medium text-muted hover:text-danger hover:bg-danger/10 transition-colors"
                    @click="resetFilters"
                >
                    <X class="h-3.5 w-3.5" />
                    Clear
                </button>

                <button
                    type="button"
                    class="h-9 w-9 rounded-lg flex items-center justify-center text-muted hover:bg-primary-50 hover:text-primary-600 transition-colors"
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
                <div class="relative flex-1 mb-4">
                    <Search
                        class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    />
                    <BaseInput
                        v-model="filters.search"
                        :allowResize="true"
                        :textMax="1000"
                        input-class="pl-11"
                        placeholder="Search patient, assigned nurse, schedule..."
                        @update:modelValue="emitChange()"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label
                            class="text-[11px] font-medium uppercase tracking-wide text-muted mb-1.5 block"
                        >
                            From
                        </label>

                        <div class="relative">
                            <CalendarDays
                                class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted pointer-events-none"
                            />

                            <input
                                v-model="filters.date_from"
                                type="date"
                                class="w-full rounded-xl border border-muted-light bg-muted-light/40 pl-9 pr-2 py-2.5 text-sm text-secondary focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary focus:bg-white transition-colors"
                                @change="emitChange(true)"
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            class="text-[11px] font-medium uppercase tracking-wide text-muted mb-1.5 block"
                        >
                            To
                        </label>

                        <div class="relative">
                            <CalendarDays
                                class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted pointer-events-none"
                            />

                            <input
                                v-model="filters.date_to"
                                type="date"
                                class="w-full rounded-xl border border-muted-light bg-muted-light/40 pl-9 pr-2 py-2.5 text-sm text-secondary focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary focus:bg-white transition-colors"
                                @change="emitChange(true)"
                            />
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <p
                        class="text-[11px] font-medium uppercase tracking-wide text-muted mb-2"
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
                                (
                                    status.value === ''
                                        ? filters.statuses.length === 0
                                        : filters.statuses.includes(
                                              status.value,
                                          )
                                )
                                    ? 'bg-primary border-primary text-white'
                                    : 'bg-white border-muted-light text-muted hover:border-primary/40 hover:text-primary-600'
                            "
                            @click="toggleStatus(status.value)"
                        >
                            {{ status.label }}
                        </button>
                    </div>
                </div>

                <div>
                    <p
                        class="text-[11px] font-medium uppercase tracking-wide text-muted mb-2"
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
                                    ? 'bg-primary border-primary text-white'
                                    : 'bg-white border-muted-light text-muted hover:border-primary/40 hover:text-primary-600'
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
import { reactive, computed, watch, ref, onMounted } from "vue";
import BaseInput from "~/components/ui/BaseInput.vue";
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
const panelRef = ref<HTMLElement | null>(null);

const yesterday = new Date(Date.now() - 86400000).toISOString().slice(0, 10);
const nextWeek = new Date(Date.now() + 7 * 86400000).toISOString().slice(0, 10);

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

        date_from: toStr(route.query.date_from) || yesterday,

        date_to: toStr(route.query.date_to) || nextWeek,

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
        label: "Card view",
        value: "card",
        icon: LayoutGrid,
    },
    {
        label: "Table view",
        value: "table",
        icon: Table,
    },
];

const statusOptions = [
    {
        label: "All",
        value: "",
    },
    {
        label: "Pending",
        value: "pending",
    },
    {
        label: "On going",
        value: "ongoing",
    },
    {
        label: "Completed",
        value: "completed",
    },
    {
        label: "Cancelled",
        value: "cancelled",
    },
    {
        label: "Missed",
        value: "missed",
    },
];

const typeOptions = [
    {
        label: "Medical Services",
        value: "medical",
    },
    {
        label: "Activities of Daily Living (ADL)",
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
    if (value === "") {
        filters.statuses = [];
        emitChange(true);
        return;
    }

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
    filters.date_from = yesterday;
    filters.date_to = nextWeek;
    filters.statuses = [];
    filters.type = ["medical"];
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
