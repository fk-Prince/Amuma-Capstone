<template>
    <div
        class="w-full rounded-t-2xl border border-muted-light/70 bg-white font-sans shadow-sm shadow-secondary/[0.03]"
    >
        <div class="flex flex-col gap-3 p-4 sm:p-5">
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="group flex min-w-0 flex-1 items-center gap-3 text-left"
                    :aria-expanded="expanded"
                    @click="expanded = !expanded"
                >
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600 transition-colors group-hover:bg-primary-100"
                    >
                        <SlidersHorizontal class="h-5 w-5" />
                    </span>

                    <span class="min-w-0">
                        <span class="flex items-center gap-2">
                            <span
                                class="font-semibold tracking-tight text-secondary"
                            >
                                Filter Schedule
                            </span>

                            <span
                                v-if="activeFilterCount"
                                class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-primary px-1.5 text-[10px] font-semibold text-white"
                            >
                                {{ activeFilterCount }}
                            </span>
                        </span>

                        <span class="mt-0.5 block truncate text-xs text-muted">
                            Refine appointments by date, status or service type
                        </span>
                    </span>
                </button>

                <div class="flex shrink-0 items-center gap-2 pl-2">
                    <div
                        class="flex items-center gap-1 rounded-xl bg-muted-light p-1"
                    >
                        <button
                            v-for="option in viewOptions"
                            :key="option.value"
                            type="button"
                            :aria-label="option.label"
                            :aria-pressed="filters.view === option.value"
                            class="flex h-8 w-8 items-center justify-center rounded-lg transition-colors"
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
                        type="button"
                        class="flex h-9 w-9 items-center justify-center rounded-lg text-muted transition-colors hover:bg-primary-50 hover:text-primary-600"
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

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative min-w-0 flex-1">
                    <Search
                        class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted"
                    />

                    <input
                        :value="filters.search"
                        type="text"
                        placeholder="Search patient, assigned nurse or schedule code"
                        class="w-full rounded-xl border border-muted-light bg-muted-light/40 py-2.5 pl-10 pr-9 text-sm text-secondary transition-colors focus:border-primary focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/25"
                        @input="onSearchInput"
                    />

                    <button
                        v-if="filters.search"
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted transition-colors hover:text-secondary"
                        aria-label="Clear search"
                        @click="clearSearch"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <button
                    v-if="activeFilterCount > 0"
                    type="button"
                    class="flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl border border-muted-light px-3 text-xs font-medium text-muted transition-colors hover:border-danger/30 hover:bg-danger/10 hover:text-danger"
                    @click="resetFilters"
                >
                    <X class="h-3.5 w-3.5" />
                    Clear all
                </button>
            </div>

            <div
                v-if="!expanded && summaryChips.length"
                class="flex flex-wrap items-center gap-1.5"
            >
                <span
                    v-for="chip in summaryChips"
                    :key="chip.key"
                    class="inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-1 text-[11px] font-medium text-primary-600"
                >
                    <component :is="chip.icon" class="h-3 w-3" />
                    {{ chip.label }}
                </span>
            </div>
        </div>

        <Transition
            name="dropdown"
            @enter="onEnter"
            @after-enter="onAfterEnter"
            @leave="onLeave"
        >
            <div v-show="expanded" ref="panelRef" class="overflow-hidden">
                <div
                    class="space-y-5 border-t border-muted-light/70 p-4 sm:p-5"
                >
                    <div>
                        <p
                            class="mb-2 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted"
                        >
                            <CalendarDays class="h-3.5 w-3.5" />
                            Date range
                        </p>

                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:items-center"
                        >
                            <input
                                v-model="filters.date_from"
                                type="date"
                                aria-label="From"
                                class="w-full min-w-0 rounded-xl border border-muted-light bg-muted-light/40 px-3 py-2.5 text-sm text-secondary transition-colors focus:border-primary focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/25 sm:w-44"
                                @change="emitChange(true)"
                            />

                            <span class="shrink-0 text-xs text-muted sm:px-1">
                                to
                            </span>

                            <input
                                v-model="filters.date_to"
                                type="date"
                                aria-label="To"
                                class="w-full min-w-0 rounded-xl border border-muted-light bg-muted-light/40 px-3 py-2.5 text-sm text-secondary transition-colors focus:border-primary focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/25 sm:w-44"
                                @change="emitChange(true)"
                            />
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        <div>
                            <p
                                class="mb-2 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted"
                            >
                                <CircleDot class="h-3.5 w-3.5" />
                                Status
                            </p>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="status in statusOptions"
                                    :key="status.value"
                                    type="button"
                                    class="rounded-full border px-3 py-1.5 text-xs font-medium transition-colors"
                                    :class="
                                        isStatusActive(status.value)
                                            ? 'border-primary bg-primary text-white'
                                            : 'border-muted-light bg-white text-muted hover:border-primary/40 hover:text-primary-600'
                                    "
                                    @click="toggleStatus(status.value)"
                                >
                                    {{ status.label }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <p
                                class="mb-2 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted"
                            >
                                <Layers class="h-3.5 w-3.5" />
                                Service type
                            </p>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="type in typeOptions"
                                    :key="type.value"
                                    type="button"
                                    class="rounded-full border px-3 py-1.5 text-xs font-medium transition-colors"
                                    :class="
                                        filters.type.includes(type.value)
                                            ? 'border-primary bg-primary text-white'
                                            : 'border-muted-light bg-white text-muted hover:border-primary/40 hover:text-primary-600'
                                    "
                                    @click="toggleType(type.value)"
                                >
                                    {{ type.label }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <p
                                class="mb-2 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted"
                            >
                                <UserCheck class="h-3.5 w-3.5" />
                                Caseload
                            </p>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="option in caseloadOptions"
                                    :key="option.value"
                                    type="button"
                                    class="rounded-full border px-3 py-1.5 text-xs font-medium transition-colors"
                                    :class="
                                        filters.assignment === option.value
                                            ? 'border-primary bg-primary text-white'
                                            : 'border-muted-light bg-white text-muted hover:border-primary/40 hover:text-primary-600'
                                    "
                                    @click="setAssignment(option.value)"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { reactive, computed, watch, ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
    Search,
    CalendarDays,
    ChevronDown,
    CircleDot,
    UserCheck,
    Layers,
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
    assignment: string;
    view: ScheduleView;
}

const props = withDefaults(
    defineProps<{
        defaultExpanded?: boolean;
    }>(),
    {
        defaultExpanded: false,
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

const DEFAULT_TYPE = "medical";

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
            : [DEFAULT_TYPE],

        assignment: toStr(route.query.assignment) || "all",

        view: (toStr(route.query.view) as ScheduleView) || "card",
    };
}

const filters = reactive<ScheduleFilters>(filtersFromQuery());

onMounted(() => {
    const needsDefaultQuery =
        !route.query.date_from || !route.query.date_to || !route.query.type;

    if (!route.query.type) {
        filters.type = [DEFAULT_TYPE];
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
    { label: "Card view", value: "card", icon: LayoutGrid },
    { label: "Table view", value: "table", icon: Table },
];

const statusOptions = [
    { label: "All", value: "" },
    { label: "Pending", value: "pending" },
    { label: "On going", value: "ongoing" },
    { label: "Completed", value: "completed" },
    { label: "Cancelled", value: "cancelled" },
    { label: "Missed", value: "missed" },
];

const caseloadOptions = [
    { label: "All Schedules", value: "all" },
    { label: "Assigned to me", value: "mine" },
];

const typeOptions = [
    { label: "Medical Services", value: "medical" },
    { label: "Activities of Daily Living (ADL)", value: "adl" },
];

const isDefaultDateRange = computed(
    () => filters.date_from === yesterday && filters.date_to === nextWeek,
);

const isDefaultType = computed(
    () => filters.type.length === 1 && filters.type[0] === DEFAULT_TYPE,
);

const activeFilterCount = computed(() => {
    let count = 0;

    if (filters.search) count++;
    if (!isDefaultDateRange.value) count++;
    if (!isDefaultType.value) count++;
    if (filters.assignment !== "all") count++;

    count += filters.statuses.length;

    return count;
});

const shortDate = (value: string) => {
    if (!value) return "";

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) return value;

    return parsed.toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
    });
};

const summaryChips = computed(() => {
    const chips: { key: string; label: string; icon: unknown }[] = [];

    chips.push({
        key: "type",
        icon: Layers,
        label:
            typeOptions.find((t) => filters.type.includes(t.value))?.label ??
            "All types",
    });

    chips.push({
        key: "period",
        icon: CalendarDays,
        label: `${shortDate(filters.date_from)} – ${shortDate(filters.date_to)}`,
    });

    if (filters.statuses.length) {
        chips.push({
            key: "status",
            icon: CircleDot,
            label: filters.statuses
                .map(
                    (value) =>
                        statusOptions.find((s) => s.value === value)?.label ??
                        value,
                )
                .join(", "),
        });
    }

    return chips;
});

const setView = (value: ScheduleView) => {
    filters.view = value;
    emitChange(true);
};

function isStatusActive(value: string) {
    return value === ""
        ? filters.statuses.length === 0
        : filters.statuses.includes(value);
}

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

const setAssignment = (value: string) => {
    filters.assignment = value;
    emitChange(true);
};

const onSearchInput = (event: Event) => {
    filters.search = (event.target as HTMLInputElement).value;
    emitChange();
};

const clearSearch = () => {
    filters.search = "";
    emitChange(true);
};

const resetFilters = () => {
    filters.search = "";
    filters.date_from = yesterday;
    filters.date_to = nextWeek;
    filters.statuses = [];
    filters.type = [DEFAULT_TYPE];
    filters.assignment = "all";
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
    setOrDelete(
        "assignment",
        filters.assignment === "all" ? "" : filters.assignment,
    );
    setOrDelete("view", filters.view === "card" ? "" : filters.view);

    router.replace({ query });

    emit("change", { ...filters });
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
