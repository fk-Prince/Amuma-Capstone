<script setup lang="ts">
import { ref } from "vue";
import type { Service } from "~/types/service";
import { Modules } from "~/types/module";
import { usePermissions } from "~/composables/usePermission";
import { formatCurrency } from "~/utils/currency";
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

const formatPrice = (price: number | string) => {
    const num = Number(price);
    return isNaN(num) ? price : formatCurrency(num);
};

type CategoryStyle = { bg: string; text: string; dot: string };

const categoryPalette: CategoryStyle[] = [
    { bg: "bg-primary-50", text: "text-primary-600", dot: "bg-primary-500" },
    { bg: "bg-accent-50", text: "text-accent-600", dot: "bg-accent-500" },
    { bg: "bg-primary-100", text: "text-primary-700", dot: "bg-primary-600" },
    { bg: "bg-accent-100", text: "text-accent-700", dot: "bg-accent-600" },
    { bg: "bg-light", text: "text-primary-700", dot: "bg-primary-400" },
    { bg: "bg-muted-light", text: "text-muted-dark", dot: "bg-muted" },
];

const defaultCategoryStyle: CategoryStyle = categoryPalette[0]!;

const categoryStyle = (name: string | undefined | null): CategoryStyle => {
    if (!name) return defaultCategoryStyle;

    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = (hash * 31 + name.charCodeAt(i)) >>> 0;
    }

    return (
        categoryPalette[hash % categoryPalette.length] ?? defaultCategoryStyle
    );
};
</script>

<template>
    <div class="space-y-3">
        <div class="space-y-3 scrollbar-thin pr-1">
            <template v-if="loading">
                <div
                    v-for="n in 5"
                    :key="n"
                    class="rounded-2xl border border-[#E4EFED] bg-white p-4 animate-pulse"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="h-5 w-32 bg-slate-100 rounded-md" />

                            <div class="flex gap-2 mt-3">
                                <div class="h-3 w-20 bg-slate-100 rounded" />
                                <div class="h-3 w-16 bg-slate-100 rounded" />
                                <div class="h-3 w-24 bg-slate-100 rounded" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="w-14 h-5 rounded-full bg-slate-100" />
                            <div class="w-4 h-4 rounded bg-slate-100" />
                        </div>
                    </div>
                </div>
            </template>

            <template v-else>
                <div
                    v-if="services.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center rounded-2xl border border-dashed border-[#E4EFED] bg-[#F7FAF9]/40"
                >
                    <div
                        class="w-14 h-14 rounded-full bg-primary-50 flex items-center justify-center mb-3"
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

                    <p class="text-sm font-medium text-[#16302E]">
                        No services found
                    </p>
                    <p class="text-xs text-[#6B8A87] mt-1 max-w-xs">
                        Try adjusting your search or filters, or add a new
                        service.
                    </p>
                </div>

                <template v-else>
                    <div
                        v-for="service in services"
                        :key="service.service_id"
                        class="group rounded-2xl border border-[#E4EFED] bg-white overflow-hidden transition-all duration-300 hover:border-primary/30 hover:shadow-lg"
                    >
                        <button
                            type="button"
                            @click="toggleService(service.service_id)"
                            class="w-full flex items-center justify-between gap-4 p-5 text-left"
                            :class="
                                isExpanded(service.service_id)
                                    ? 'bg-[#F7FAF9]'
                                    : 'hover:bg-[#FAFCFB]'
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
                                            class="font-semibold text-[#16302E] truncate"
                                        >
                                            {{ service.service_name }}
                                        </p>

                                        <span
                                            v-if="service.category_name"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset ring-black/5"
                                            :class="[
                                                categoryStyle(
                                                    service.category_name,
                                                ).bg,
                                                categoryStyle(
                                                    service.category_name,
                                                ).text,
                                            ]"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full"
                                                :class="
                                                    categoryStyle(
                                                        service.category_name,
                                                    ).dot
                                                "
                                            />
                                            {{ service.category_name }}
                                        </span>
                                    </div>

                                    <div
                                        class="flex flex-wrap items-center gap-3 mt-2 text-xs text-[#6B8A87]"
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
                                            ? 'bg-accent-50 text-accent-600'
                                            : 'bg-muted-light text-muted-dark'
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
                                    class="w-4 h-4 text-muted transition-transform"
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
                                class="border-t border-[#E4EFED] bg-[#FAFCFB] p-5 space-y-6"
                            >
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                                >
                                    <div>
                                        <h4
                                            class="text-sm font-semibold text-[#16302E]"
                                        >
                                            Service Management
                                        </h4>

                                        <p class="text-xs text-[#9AB3AF] mt-1">
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
                                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-medium text-[#16302E] bg-white border border-[#E4EFED] hover:border-primary/40 hover:text-primary hover:bg-[#F7FAF9] transition"
                                        >
                                            <Pencil class="w-4 h-4" />
                                            Edit
                                        </button>

                                        <button
                                            v-if="canCreate(Modules.Services)"
                                            @click.stop="
                                                openAssignService(service)
                                            "
                                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-medium text-[#16302E] bg-white border border-[#E4EFED] hover:border-primary/40 hover:text-primary hover:bg-[#F7FAF9] transition"
                                        >
                                            <UserPlus class="w-4 h-4" />
                                            Assign Nurse
                                        </button>
                                    </div>
                                </div>

                                <div
                                    class="rounded-2xl border border-[#E4EFED] bg-white overflow-hidden shadow-sm"
                                >
                                    <div
                                        class="px-5 py-4 border-b border-[#E4EFED] bg-gradient-to-r from-[#F7FAF9] to-white"
                                    >
                                        <div class="flex items-center gap-2">
                                            <Stethoscope
                                                class="w-4 h-4 text-primary"
                                            />

                                            <h3
                                                class="text-sm font-semibold text-[#16302E]"
                                            >
                                                Service Information
                                            </h3>
                                        </div>

                                        <p class="text-xs text-[#9AB3AF] mt-1">
                                            Overview of service configuration
                                        </p>
                                    </div>

                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4"
                                    >
                                        <div class="p-5">
                                            <div
                                                class="flex items-center gap-2 mb-3"
                                            >
                                                <Tag
                                                    class="w-4 h-4 text-primary"
                                                />

                                                <span
                                                    class="text-[11px] uppercase tracking-wide text-[#9AB3AF]"
                                                >
                                                    Category
                                                </span>
                                            </div>

                                            <span
                                                v-if="service.category_name"
                                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ring-black/5"
                                                :class="[
                                                    categoryStyle(
                                                        service.category_name,
                                                    ).bg,
                                                    categoryStyle(
                                                        service.category_name,
                                                    ).text,
                                                ]"
                                            >
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full"
                                                    :class="
                                                        categoryStyle(
                                                            service.category_name,
                                                        ).dot
                                                    "
                                                />
                                                {{ service.category_name }}
                                            </span>

                                            <p
                                                v-else
                                                class="text-sm font-semibold text-[#16302E]"
                                            >
                                                —
                                            </p>
                                        </div>

                                        <div
                                            class="p-5 border-b lg:border-b-0 lg:border-r border-[#E4EFED]"
                                        >
                                            <div
                                                class="flex items-center gap-2 mb-3"
                                            >
                                                <Banknote
                                                    class="w-4 h-4 text-primary"
                                                />

                                                <span
                                                    class="text-[11px] uppercase tracking-wide text-[#9AB3AF]"
                                                >
                                                    Price
                                                </span>
                                            </div>

                                            <p
                                                class="text-lg font-bold text-primary"
                                            >
                                                {{ formatPrice(service.price) }}
                                            </p>
                                        </div>

                                        <div
                                            class="p-5 border-b lg:border-b-0 lg:border-r border-[#E4EFED]"
                                        >
                                            <div
                                                class="flex items-center gap-2 mb-3"
                                            >
                                                <Clock
                                                    class="w-4 h-4 text-primary"
                                                />

                                                <span
                                                    class="text-[11px] uppercase tracking-wide text-[#9AB3AF]"
                                                >
                                                    Duration
                                                </span>
                                            </div>

                                            <p
                                                class="text-lg font-bold text-[#16302E]"
                                            >
                                                {{
                                                    formatDuration(
                                                        service.maximum_duration,
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            class="p-5 border-b lg:border-b-0 lg:border-r border-[#E4EFED]"
                                        >
                                            <div
                                                class="flex items-center gap-2 mb-3"
                                            >
                                                <Tag
                                                    class="w-4 h-4 text-primary"
                                                />

                                                <span
                                                    class="text-[11px] uppercase tracking-wide text-[#9AB3AF]"
                                                >
                                                    Service Type
                                                </span>
                                            </div>

                                            <p
                                                class="text-sm font-semibold text-[#16302E]"
                                            >
                                                {{ service.type_formatted }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <div
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-[#E4EFED] text-xs text-[#6B8A87]"
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
