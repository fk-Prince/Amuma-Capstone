<script setup lang="ts">
import { computed } from "vue";
import {
    ChevronLeft,
    ChevronRight,
    Download,
    History,
    LogIn,
    LogOut,
    PencilLine,
    Plus,
    ShieldAlert,
    Activity,
} from "lucide-vue-next";
import {
    AUDIT_ACTION_CLASSES,
    auditActionLabel,
    auditModuleLabel,
    auditSummary,
    humanize,
    type AuditLog,
} from "~/types/audit-log";
import { initials } from "~/utils/user";

/**
 * Activity feed: entries grouped by day, newest first, each showing who did
 * what, where and when. Clicking one opens its full details.
 */
const props = withDefaults(
    defineProps<{
        logs: AuditLog[];
        loading: boolean;
        currentPage?: number;
        totalItems?: number;
        pageSize?: number;
        view?: "table" | "card";
    }>(),
    {
        currentPage: 1,
        totalItems: 0,
        pageSize: 15,
        view: "table",
    },
);

const emit = defineEmits<{
    select: [log: AuditLog];
    "page-change": [page: number];
}>();

const ACTION_ICONS: Record<string, unknown> = {
    created: Plus,
    updated: PencilLine,
    login: LogIn,
    login_failed: ShieldAlert,
    logout: LogOut,
    exported: Download,
};

const actionIcon = (action: string) => ACTION_ICONS[action] ?? Activity;

const actionClass = (action: string) =>
    AUDIT_ACTION_CLASSES[action] ??
    "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-300";

const pad = (n: number) => String(n).padStart(2, "0");
const dayKey = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

function dayLabel(key: string) {
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);

    if (key === dayKey(today)) return "Today";
    if (key === dayKey(yesterday)) return "Yesterday";

    return new Date(`${key}T00:00:00`).toLocaleDateString("en-US", {
        weekday: "long",
        month: "long",
        day: "numeric",
        year: new Date(`${key}T00:00:00`).getFullYear() === today.getFullYear() ? undefined : "numeric",
    });
}

const timeLabel = (value: string) => {
    const d = new Date(value);
    return Number.isNaN(d.getTime())
        ? ""
        : d.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit" });
};

const groups = computed(() => {
    const map = new Map<string, AuditLog[]>();

    for (const log of props.logs) {
        const d = new Date(log.created_at);
        const key = Number.isNaN(d.getTime()) ? "unknown" : dayKey(d);
        if (!map.has(key)) map.set(key, []);
        map.get(key)!.push(log);
    }

    return [...map.entries()].map(([key, items]) => ({
        key,
        label: key === "unknown" ? "Unknown date" : dayLabel(key),
        items,
    }));
});

const actorName = (log: AuditLog) => log.actor_name || log.actor_email || "System";
const actorMeta = (log: AuditLog) =>
    [log.actor_role ? humanize(log.actor_role) : null, log.actor_name ? log.actor_email : null]
        .filter(Boolean)
        .join(" · ");

// Pagination
const totalPages = computed(() => Math.max(1, Math.ceil(props.totalItems / props.pageSize)));
const firstItem = computed(() =>
    props.totalItems ? (props.currentPage - 1) * props.pageSize + 1 : 0,
);
const lastItem = computed(() => Math.min(props.currentPage * props.pageSize, props.totalItems));

const pages = computed<(number | "gap")[]>(() => {
    const total = totalPages.value;
    const current = props.currentPage;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

    const list: (number | "gap")[] = [1];
    const start = Math.max(2, current - 1);
    const end = Math.min(total - 1, current + 1);
    if (start > 2) list.push("gap");
    for (let i = start; i <= end; i++) list.push(i);
    if (end < total - 1) list.push("gap");
    list.push(total);
    return list;
});

function goTo(page: number) {
    if (page < 1 || page > totalPages.value || page === props.currentPage) return;
    emit("page-change", page);
}
</script>

<template>
    <div class="flex min-h-0 flex-1 flex-col">
        <!-- Loading -->
        <div v-if="loading" class="flex-1 space-y-2 overflow-hidden p-4 sm:p-6">
            <div
                v-for="n in 8"
                :key="n"
                class="flex animate-pulse items-center gap-3 rounded-xl px-2 py-3"
            >
                <div class="h-10 w-10 shrink-0 rounded-full bg-slate-100 dark:bg-white/10" />
                <div class="flex-1 space-y-2">
                    <div class="h-3 w-1/3 rounded bg-slate-100 dark:bg-white/10" />
                    <div class="h-2.5 w-2/3 rounded bg-slate-100 dark:bg-white/5" />
                </div>
                <div class="h-3 w-14 rounded bg-slate-100 dark:bg-white/10" />
            </div>
        </div>

        <!-- Empty -->
        <div
            v-else-if="!logs.length"
            class="flex flex-1 flex-col items-center justify-center px-6 py-20 text-center"
        >
            <span
                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-white/10 dark:text-gray-500"
            >
                <History class="h-6 w-6" />
            </span>
            <p class="mt-4 text-sm font-semibold text-slate-700 dark:text-white">
                No activity found
            </p>
            <p class="mt-1 max-w-sm text-sm text-slate-500 dark:text-gray-400">
                Actions taken in this branch will be recorded here. Try clearing the
                filters if you expected to see something.
            </p>
        </div>

        <!-- Feed -->
        <div v-else class="min-h-0 flex-1 overflow-y-auto">
            <section v-for="group in groups" :key="group.key">
                <h3
                    class="sticky top-0 z-[1] flex items-center gap-3 border-b border-slate-100 bg-slate-50/95 px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-500 backdrop-blur sm:px-6 dark:border-white/10 dark:bg-[#1a2234]/95 dark:text-gray-400"
                >
                    {{ group.label }}
                    <span
                        class="rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold normal-case tracking-normal text-slate-500 ring-1 ring-slate-200 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10"
                    >
                        {{ group.items.length }} {{ group.items.length === 1 ? "entry" : "entries" }}
                    </span>
                </h3>

                <!-- Card view: a grid of entries -->
                <ul
                    v-if="view === 'card'"
                    class="grid grid-cols-1 gap-3 p-3 sm:grid-cols-2 sm:p-5 2xl:grid-cols-3"
                >
                    <li v-for="log in group.items" :key="log.uuid">
                        <button
                            type="button"
                            class="group flex h-full w-full flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 text-left transition hover:-translate-y-0.5 hover:border-primary-200 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 dark:border-white/10 dark:bg-white/[0.02] dark:hover:border-primary-500/40"
                            @click="emit('select', log)"
                        >
                            <div class="flex items-start gap-3">
                                <span class="relative shrink-0">
                                    <span
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-50 text-xs font-bold text-primary dark:bg-primary-500/15 dark:text-primary-300"
                                    >
                                        {{ initials(actorName(log)) }}
                                    </span>
                                    <span
                                        class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full ring-2 ring-white dark:ring-secondary"
                                        :class="actionClass(log.action)"
                                    >
                                        <component :is="actionIcon(log.action)" class="h-3 w-3" />
                                    </span>
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-800 dark:text-white">
                                        {{ actorName(log) }}
                                    </p>
                                    <p
                                        v-if="actorMeta(log)"
                                        class="truncate text-xs text-slate-400 dark:text-gray-500"
                                    >
                                        {{ actorMeta(log) }}
                                    </p>
                                </div>

                                <span class="shrink-0 text-xs font-medium tabular-nums text-slate-500 dark:text-gray-400">
                                    {{ timeLabel(log.created_at) }}
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5">
                                <span
                                    class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                    :class="actionClass(log.action)"
                                >
                                    {{ auditActionLabel(log.action) }}
                                </span>
                                <span
                                    class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-500 dark:bg-white/5 dark:text-gray-400"
                                >
                                    {{ auditModuleLabel(log.module) }}
                                </span>
                            </div>

                            <p
                                class="line-clamp-3 text-sm leading-6 text-slate-600 dark:text-gray-300"
                                :title="auditSummary(log)"
                            >
                                {{ auditSummary(log) }}
                            </p>
                        </button>
                    </li>
                </ul>

                <!-- Table view: one row per entry (columns on wide screens) -->
                <ul v-else class="divide-y divide-slate-100 dark:divide-white/[0.06]">
                    <li v-for="log in group.items" :key="log.uuid">
                        <button
                            type="button"
                            class="group flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-slate-50 focus-visible:bg-slate-50 focus-visible:outline-none sm:gap-4 sm:px-6 lg:grid lg:grid-cols-[4.5rem_minmax(0,15rem)_7.5rem_9rem_minmax(0,1fr)_1rem] lg:items-center dark:hover:bg-white/[0.03] dark:focus-visible:bg-white/[0.03]"
                            @click="emit('select', log)"
                        >
                            <!-- Time (wide screens) -->
                            <span
                                class="hidden whitespace-nowrap text-xs font-semibold tabular-nums text-slate-500 lg:block dark:text-gray-400"
                            >
                                {{ timeLabel(log.created_at) }}
                            </span>

                            <!-- Who -->
                            <span class="flex min-w-0 items-start gap-3 lg:items-center">
                                <span class="relative mt-0.5 shrink-0 lg:mt-0">
                                    <span
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-50 text-xs font-bold text-primary lg:h-9 lg:w-9 dark:bg-primary-500/15 dark:text-primary-300"
                                    >
                                        {{ initials(actorName(log)) }}
                                    </span>
                                    <span
                                        class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full ring-2 ring-white dark:ring-secondary"
                                        :class="actionClass(log.action)"
                                    >
                                        <component :is="actionIcon(log.action)" class="h-3 w-3" />
                                    </span>
                                </span>

                                <span class="hidden min-w-0 lg:block">
                                    <span class="block truncate text-sm font-semibold text-slate-800 dark:text-white">
                                        {{ actorName(log) }}
                                    </span>
                                    <span
                                        v-if="actorMeta(log)"
                                        class="block truncate text-xs text-slate-400 dark:text-gray-500"
                                    >
                                        {{ actorMeta(log) }}
                                    </span>
                                </span>
                            </span>

                            <!-- Action and module (wide screens) -->
                            <span class="hidden lg:block">
                                <span
                                    class="whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                    :class="actionClass(log.action)"
                                >
                                    {{ auditActionLabel(log.action) }}
                                </span>
                            </span>
                            <span class="hidden truncate text-sm text-slate-600 lg:block dark:text-gray-300">
                                {{ auditModuleLabel(log.module) }}
                            </span>

                            <!-- What (wide screens) -->
                            <span
                                class="hidden truncate text-sm text-slate-600 lg:block dark:text-gray-300"
                                :title="auditSummary(log)"
                            >
                                {{ auditSummary(log) }}
                            </span>

                            <!-- Everything stacked (phones and tablets) -->
                            <div class="min-w-0 flex-1 lg:hidden">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <p class="truncate text-sm font-semibold text-slate-800 dark:text-white">
                                        {{ actorName(log) }}
                                    </p>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                        :class="actionClass(log.action)"
                                    >
                                        {{ auditActionLabel(log.action) }}
                                    </span>
                                    <span
                                        class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-500 dark:bg-white/5 dark:text-gray-400"
                                    >
                                        {{ auditModuleLabel(log.module) }}
                                    </span>
                                </div>

                                <p
                                    class="mt-0.5 line-clamp-2 text-sm text-slate-600 dark:text-gray-300"
                                    :title="auditSummary(log)"
                                >
                                    {{ auditSummary(log) }}
                                </p>

                                <p
                                    v-if="actorMeta(log)"
                                    class="mt-0.5 truncate text-xs text-slate-400 dark:text-gray-500"
                                >
                                    {{ actorMeta(log) }}
                                </p>
                            </div>

                            <span class="flex shrink-0 items-center gap-1.5 pt-0.5 lg:pt-0">
                                <span
                                    class="whitespace-nowrap text-xs font-medium tabular-nums text-slate-500 lg:hidden dark:text-gray-400"
                                >
                                    {{ timeLabel(log.created_at) }}
                                </span>
                                <ChevronRight
                                    class="h-4 w-4 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-primary dark:text-gray-600"
                                />
                            </span>
                        </button>
                    </li>
                </ul>
            </section>
        </div>

        <!-- Pages -->
        <footer
            v-if="!loading && totalItems"
            class="flex shrink-0 flex-col gap-3 border-t border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-white/10"
        >
            <p class="text-xs text-slate-500 dark:text-gray-400">
                Showing
                <span class="font-semibold text-slate-700 dark:text-gray-200">{{ firstItem }}–{{ lastItem }}</span>
                of
                <span class="font-semibold text-slate-700 dark:text-gray-200">{{ totalItems }}</span>
            </p>

            <nav class="flex items-center gap-1" aria-label="Pages">
                <button
                    type="button"
                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-slate-200 disabled:hover:text-slate-500 dark:border-white/10 dark:text-gray-400"
                    aria-label="Previous page"
                    :disabled="currentPage <= 1"
                    @click="goTo(currentPage - 1)"
                >
                    <ChevronLeft class="h-4 w-4" />
                </button>

                <template v-for="(page, i) in pages" :key="`${page}-${i}`">
                    <span v-if="page === 'gap'" class="px-1 text-xs text-slate-400">…</span>
                    <button
                        v-else
                        type="button"
                        class="h-8 min-w-[2rem] rounded-lg px-2 text-xs font-semibold transition"
                        :class="
                            page === currentPage
                                ? 'bg-primary text-white shadow-sm'
                                : 'text-slate-600 hover:bg-slate-100 dark:text-gray-300 dark:hover:bg-white/10'
                        "
                        :aria-current="page === currentPage ? 'page' : undefined"
                        @click="goTo(page)"
                    >
                        {{ page }}
                    </button>
                </template>

                <button
                    type="button"
                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-slate-200 disabled:hover:text-slate-500 dark:border-white/10 dark:text-gray-400"
                    aria-label="Next page"
                    :disabled="currentPage >= totalPages"
                    @click="goTo(currentPage + 1)"
                >
                    <ChevronRight class="h-4 w-4" />
                </button>
            </nav>
        </footer>
    </div>
</template>
