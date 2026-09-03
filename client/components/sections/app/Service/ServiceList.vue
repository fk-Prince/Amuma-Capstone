<script setup lang="ts">
import { computed, ref } from "vue";
import type { Service } from "~/types/service";
import { Modules } from "~/types/module";
import { usePermissions } from "~/composables/usePermission";
import { formatCurrency } from "~/utils/currency";
import { categoryLabel, categoryStyle, type CategoryStyle } from "~/utils/category";
import {
    ChevronDown,
    Pencil,
    UserPlus,
    Trash2,
    Clock,
    Tag,
    Banknote,
    Stethoscope,
    CheckCircle2,
    XCircle,
} from "lucide-vue-next";

const { canUpdate, canCreate } = usePermissions();

const props = defineProps<{
    loading: boolean;
    services: Service[];
}>();

const emit = defineEmits<{
    edit: [service: Service];
    assign: [service: Service];
}>();

const expandedService = ref<number | null>(null);

const isExpanded = (service_id: number | undefined) => {
    if (!service_id) return false;

    return expandedService.value === service_id;
};

const toggleService = (service_id: number | undefined) => {
    if (!service_id) return;

    if (expandedService.value === service_id) {
        expandedService.value = null;
        return;
    }

    expandedService.value = service_id;
};

const openEditService = (service: Service) => {
    emit("edit", service);
};

const openAssignService = (service: Service) => {
    emit("assign", service);
};

const formatDuration = (duration: string) => {
    const [h, m] = duration.split(":").map(Number);
    const parts = [];
    if (h != null && h > 0) parts.push(`${h}h`);
    if (m != null && m > 0) parts.push(`${m}m`);
    return parts.length ? parts.join(" ") : "—";
};

const formatDurationLong = (duration: string) => {
    const [h, m] = duration.split(":").map(Number);
    const parts = [];

    if (h != null && h > 0) parts.push(`${h} ${h === 1 ? "hour" : "hours"}`);
    if (m != null && m > 0) parts.push(`${m} ${m === 1 ? "minute" : "minutes"}`);

    return parts.length ? parts.join(" ") : "Not set";
};

const SERVICE_TYPE_STYLES: Record<string, CategoryStyle> = {
    homecare: {
        bg: "bg-primary-50 dark:bg-primary-500/10",
        text: "text-primary-700 dark:text-primary-300",
        dot: "bg-primary-500",
    },
    inhouse: {
        bg: "bg-accent-50 dark:bg-accent-500/15",
        text: "text-accent-700 dark:text-accent-300",
        dot: "bg-accent-500",
    },
    both: {
        bg: "bg-violet-50 dark:bg-violet-500/15",
        text: "text-violet-700 dark:text-violet-300",
        dot: "bg-violet-500",
    },
};

const serviceTypeStyle = (service: Service): CategoryStyle => {
    const label = String(service.type_formatted ?? "").toLowerCase();
    const isHomecare = label.includes("homecare");
    const isInhouse = label.includes("inhouse") || label.includes("in-house");

    if (isHomecare && isInhouse) return SERVICE_TYPE_STYLES.both!;
    if (isInhouse) return SERVICE_TYPE_STYLES.inhouse!;

    return SERVICE_TYPE_STYLES.homecare!;
};

const formatPrice = (price: number | string) => {
    const num = Number(price);
    return isNaN(num) ? price : formatCurrency(num);
};

const serviceDetails = (service: Service) => [
    {
        label: "Category",
        icon: Tag,
        value: categoryLabel(service.category_name),
        caption: "",
    },
    {
        label: "Price",
        icon: Banknote,
        value: formatPrice(service.price),
        caption: "Per session",
    },
    {
        label: "Duration",
        icon: Clock,
        value: formatDurationLong(service.maximum_duration),
        caption: "Maximum per session",
    },
    {
        label: "Service Type",
        icon: Stethoscope,
        value: service.type_formatted,
        caption: "",
    },
];

const groupedServices = computed(() => {
    const groups = new Map<string, Service[]>();

    for (const service of props.services) {
        const name = categoryLabel(service.category_name);
        const bucket = groups.get(name);

        if (bucket) bucket.push(service);
        else groups.set(name, [service]);
    }

    return [...groups.entries()]
        .map(([name, list]) => ({
            name,
            style: categoryStyle(name === "Uncategorized" ? null : name),
            services: list,
        }))
        .sort((a, b) => {
            if (a.name === "Uncategorized") return 1;
            if (b.name === "Uncategorized") return -1;
            return a.name.localeCompare(b.name);
        });
});
</script>

<template>
    <div class="space-y-3">
        <div class="space-y-3 scrollbar-thin pr-1">
            <template v-if="loading">
                <div
                    v-for="n in 5"
                    :key="n"
                    class="rounded-2xl border border-[#E4EFED] bg-white p-4 animate-pulse dark:bg-secondary dark:border-white/10"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="h-5 w-32 bg-slate-100 rounded-md dark:bg-white/10" />

                            <div class="flex gap-2 mt-3">
                                <div class="h-3 w-20 bg-slate-100 rounded dark:bg-white/10" />
                                <div class="h-3 w-16 bg-slate-100 rounded dark:bg-white/10" />
                                <div class="h-3 w-24 bg-slate-100 rounded dark:bg-white/10" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="w-14 h-5 rounded-full bg-slate-100 dark:bg-white/10" />
                            <div class="w-4 h-4 rounded bg-slate-100 dark:bg-white/10" />
                        </div>
                    </div>
                </div>
            </template>

            <template v-else>
                <div
                    v-if="services.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center rounded-2xl border border-dashed border-[#E4EFED] bg-[#F7FAF9]/40 dark:border-white/10"
                >
                    <div
                        class="w-14 h-14 rounded-full bg-primary-50 flex items-center justify-center mb-3 dark:bg-primary-500/10"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            class="w-6 h-6 text-primary"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.75"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M3 21h18M5 21V7l7-4 7 4v14" />
                            <path d="M9 21v-6h6v6" />
                        </svg>
                    </div>

                    <p class="text-sm font-medium text-[#16302E] dark:text-white">
                        No services found
                    </p>
                    <p class="text-xs text-[#6B8A87] mt-1 max-w-xs dark:text-gray-400">
                        Try adjusting your search or filters, or add a new
                        service.
                    </p>
                </div>

                <template v-else>
                <section
                    v-for="group in groupedServices"
                    :key="group.name"
                    class="space-y-3"
                >
                    <p
                        class="px-1 text-[11px] font-semibold uppercase tracking-wide text-[#9AB3AF] dark:text-gray-500"
                    >
                        {{ group.name }} ({{ group.services.length }})
                    </p>

                    <div
                        v-for="service in group.services"
                        :key="service.service_id"
                        class="group rounded-2xl border border-[#E4EFED] bg-white overflow-hidden transition-all duration-300 hover:border-primary/30 hover:shadow-lg dark:bg-secondary dark:border-white/10"
                    >
                        <button
                            type="button"
                            @click="toggleService(service.service_id)"
                            class="w-full flex items-center justify-between gap-4 p-5 text-left"
                            :class="
                                isExpanded(service.service_id)
                                    ? 'bg-[#F7FAF9] dark:bg-white/5'
                                    : 'hover:bg-[#FAFCFB] dark:hover:bg-white/5'
                            "
                        >
                            <div class="flex items-center gap-4 min-w-0">
                                <div
                                    class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center shrink-0"
                                >
                                    <Stethoscope class="w-5 h-5 text-primary" />
                                </div>

                                <div class="min-w-0">
                                    <div
                                        class="flex items-center gap-2 flex-wrap"
                                    >
                                        <p
                                            class="font-semibold text-[#16302E] truncate dark:text-white"
                                        >
                                            {{ service.service_name }}
                                        </p>
                                    </div>

                                    <div
                                        class="flex flex-wrap items-center gap-3 mt-2 text-xs text-[#6B8A87] dark:text-gray-400"
                                    >
                                        <span class="flex items-center gap-1">
                                            <Tag class="w-3.5 h-3.5" />
                                            {{ service.type_formatted }}
                                        </span>

                                        <span class="flex items-center gap-1">
                                            <Clock class="w-3.5 h-3.5" />
                                            {{
                                                formatDuration(
                                                    service.maximum_duration,
                                                )
                                            }}
                                        </span>

                                        <span
                                            class="flex items-center gap-1 font-semibold text-primary"
                                        >
                                            <Banknote class="w-3.5 h-3.5" />
                                            {{ formatPrice(service.price) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                <span
                                    class="flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium"
                                    :class="
                                        service.is_available
                                            ? 'bg-accent-50 text-accent-600 dark:bg-accent-500/15 dark:text-accent-300'
                                            : 'bg-muted-light text-muted-dark dark:bg-white/10 dark:text-gray-300'
                                    "
                                >
                                    <CheckCircle2
                                        v-if="service.is_available"
                                        class="w-3.5 h-3.5"
                                    />

                                    <XCircle v-else class="w-3.5 h-3.5" />

                                    {{
                                        service.is_available
                                            ? "Available"
                                            : "Unavailable"
                                    }}
                                </span>

                                <ChevronDown
                                    class="w-4 h-4 text-muted transition-transform dark:text-gray-400"
                                    :class="{
                                        'rotate-180': isExpanded(
                                            service.service_id,
                                        ),
                                    }"
                                />
                            </div>
                        </button>

                        <Transition name="slide">
                            <div
                                v-if="isExpanded(service.service_id)"
                                class="border-t border-[#E4EFED] bg-[#FAFCFB] p-5 space-y-6 dark:border-white/10 dark:bg-white/5"
                            >
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                                >
                                    <div>
                                        <h4
                                            class="text-sm font-semibold text-[#16302E] dark:text-white"
                                        >
                                            Service Management
                                        </h4>

                                        <p class="text-xs text-[#9AB3AF] mt-1 dark:text-gray-500">
                                            Manage service settings and
                                            assignments
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-if="canUpdate(Modules.Services)"
                                            @click.stop="
                                                openEditService(service)
                                            "
                                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-medium text-[#16302E] bg-white border border-[#E4EFED] hover:border-primary/40 hover:text-primary hover:bg-[#F7FAF9] transition dark:bg-secondary dark:border-white/10 dark:text-white dark:hover:bg-white/5"
                                        >
                                            <Pencil class="w-4 h-4" />
                                            Edit
                                        </button>

                                        <button
                                            v-if="canCreate(Modules.Services)"
                                            @click.stop="
                                                openAssignService(service)
                                            "
                                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-medium text-[#16302E] bg-white border border-[#E4EFED] hover:border-primary/40 hover:text-primary hover:bg-[#F7FAF9] transition dark:bg-secondary dark:border-white/10 dark:text-white dark:hover:bg-white/5"
                                        >
                                            <UserPlus class="w-4 h-4" />
                                            Assign Nurse
                                        </button>
                                    </div>
                                </div>

                                <div
                                    class="rounded-2xl border border-[#E4EFED] bg-white overflow-hidden shadow-sm dark:bg-secondary dark:border-white/10"
                                >
                                    <div
                                        class="px-5 py-4 border-b border-[#E4EFED] bg-gradient-to-r from-[#F7FAF9] to-white dark:from-white/5 dark:to-secondary dark:border-white/10"
                                    >
                                        <div class="flex items-center gap-2">
                                            <Stethoscope
                                                class="w-4 h-4 text-primary"
                                            />

                                            <h3
                                                class="text-sm font-semibold text-[#16302E] dark:text-white"
                                            >
                                                Service Information
                                            </h3>
                                        </div>

                                        <p class="text-xs text-[#9AB3AF] mt-1 dark:text-gray-500">
                                            Overview of service configuration
                                        </p>
                                    </div>

                                    <dl
                                        class="grid grid-cols-2 lg:grid-cols-4 divide-y divide-[#E4EFED] sm:divide-y-0 sm:divide-x dark:divide-white/10"
                                    >
                                        <div
                                            v-for="detail in serviceDetails(service)"
                                            :key="detail.label"
                                            class="p-5"
                                        >
                                            <dt
                                                class="flex items-center gap-2 text-[11px] uppercase tracking-wide text-[#9AB3AF] dark:text-gray-500"
                                            >
                                                <component
                                                    :is="detail.icon"
                                                    class="w-3.5 h-3.5"
                                                />
                                                {{ detail.label }}
                                            </dt>

                                            <dd
                                                class="mt-2 text-sm font-semibold text-[#16302E] dark:text-white"
                                            >
                                                {{ detail.value }}
                                            </dd>

                                            <dd
                                                v-if="detail.caption"
                                                class="mt-0.5 text-[11px] font-normal text-[#9AB3AF] dark:text-gray-500"
                                            >
                                                {{ detail.caption }}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <div
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-[#E4EFED] text-xs text-[#6B8A87] dark:bg-secondary dark:border-white/10 dark:text-gray-400"
                                    >
                                        <span
                                            class="w-2 h-2 rounded-full bg-accent"
                                        />

                                        Available for booking
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </section>
                </template>
            </template>
        </div>
    </div>
</template>

<style scoped>
.scrollbar-thin {
    scrollbar-width: thin;
    scrollbar-color: #c7d9d6 transparent;
}

.scrollbar-thin::-webkit-scrollbar {
    width: 8px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
    background-color: #c7d9d6;
    border-radius: 8px;
    border: 2px solid transparent;
    background-clip: padding-box;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background-color: #9fb8b4;
}
</style>
