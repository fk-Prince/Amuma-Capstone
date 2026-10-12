<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-[9999] flex h-full w-full items-center justify-center p-4"
        >
            <div
                class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                @click="$emit('close')"
            />

            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95 translate-y-2"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="open"
                    class="relative flex max-h-[85vh] w-full max-w-3xl flex-col rounded-2xl bg-white shadow-2xl dark:bg-secondary"
                >
                    <div
                        class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-white/10"
                    >
                        <div>
                            <h2
                                class="text-lg font-semibold text-gray-900 dark:text-white"
                            >
                                Select Service
                            </h2>

                            <p
                                class="mt-0.5 text-xs text-gray-400 dark:text-gray-500"
                            >
                                Only one service can be booked at a time
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="$emit('close')"
                            class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/5 dark:hover:text-gray-300"
                            aria-label="Close"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div
                        class="flex flex-col gap-3 border-b border-gray-100 px-6 py-3 sm:flex-row sm:items-center dark:border-white/10"
                    >
                        <div class="relative flex-1">
                            <Search
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500"
                            />

                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search services..."
                                class="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm focus:border-primary/40 focus:outline-none focus:ring-2 focus:ring-primary/30 dark:border-white/10 dark:bg-secondary dark:text-white dark:placeholder-gray-500"
                            />
                        </div>

                        <div class="w-full sm:w-52">
                            <Combobox
                                :model-value="selectedCategory"
                                @update:model-value="selectCategory"
                                :items="categoryOptions"
                                placeholder="All categories"
                                search-bar
                            />
                        </div>
                    </div>

                    <div class="flex-1 space-y-6 overflow-y-auto px-6 py-5">
                        <div
                            v-if="!filteredServices.length"
                            class="flex flex-col items-center gap-2 py-14 text-center"
                        >
                            <PackageSearch
                                class="h-6 w-6 text-gray-300 dark:text-gray-600"
                            />

                            <p
                                class="text-sm font-medium text-gray-500 dark:text-gray-400"
                            >
                                No services found
                            </p>

                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                Try a different search term or category.
                            </p>
                        </div>

                        <div
                            v-for="group in groupedServices"
                            :key="group.category"
                            class="space-y-2.5"
                        >
                            <div
                                v-if="groupedServices.length > 1"
                                class="flex items-center gap-2"
                            >
                                <p
                                    class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    {{ group.category }}
                                </p>
                                <span
                                    class="rounded-full bg-gray-100 px-1.5 text-[10px] font-semibold text-gray-500 dark:bg-white/10 dark:text-gray-400"
                                >
                                    {{ group.items.length }}
                                </span>
                                <span
                                    class="h-px flex-1 bg-gray-100 dark:bg-white/10"
                                />
                            </div>

                            <button
                                v-for="service in group.items"
                                :key="service.service_id"
                                type="button"
                                role="radio"
                                :aria-checked="isChecked(service)"
                                :disabled="service.is_available === false"
                                class="group flex w-full items-start gap-3.5 rounded-xl border p-4 text-left transition-all disabled:cursor-not-allowed disabled:opacity-50"
                                :class="
                                    isChecked(service)
                                        ? 'border-primary bg-primary/5 shadow-sm shadow-primary/10 ring-1 ring-primary/20 dark:bg-primary-500/10'
                                        : 'border-gray-200 hover:border-primary/40 hover:bg-gray-50/70 dark:border-white/10 dark:hover:border-primary/40 dark:hover:bg-white/5'
                                "
                                @click="select(service)"
                            >
                                <span
                                    class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition"
                                    :class="
                                        isChecked(service)
                                            ? 'border-primary bg-primary text-white'
                                            : 'border-gray-300 group-hover:border-primary/60 dark:border-white/20'
                                    "
                                >
                                    <Check
                                        v-if="isChecked(service)"
                                        class="h-3 w-3"
                                        stroke-width="3"
                                    />
                                </span>

                                <div class="min-w-0 flex-1">
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="text-sm font-semibold leading-tight text-gray-900 dark:text-white"
                                            >
                                                {{ service.service_name }}
                                            </p>
                                            <p
                                                v-if="
                                                    durationLabel(service) ||
                                                    (groupedServices.length <=
                                                        1 &&
                                                        service.category_name)
                                                "
                                                class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-gray-400 dark:text-gray-500"
                                            >
                                                <span
                                                    v-if="durationLabel(service)"
                                                    class="inline-flex items-center gap-1"
                                                >
                                                    <Clock class="h-3.5 w-3.5" />
                                                    {{ durationLabel(service) }}
                                                    per session
                                                </span>
                                                <span
                                                    v-if="
                                                        groupedServices.length <=
                                                            1 &&
                                                        service.category_name
                                                    "
                                                >
                                                    {{ service.category_name }}
                                                </span>
                                            </p>
                                        </div>

                                        <div
                                            class="flex shrink-0 flex-col items-end gap-1"
                                        >
                                            <span
                                                class="whitespace-nowrap text-sm font-bold text-primary"
                                            >
                                                {{
                                                    formatCurrency(service.price)
                                                }}
                                            </span>
                                            <span
                                                v-if="
                                                    service.is_available ===
                                                    false
                                                "
                                                class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-500 dark:bg-white/10 dark:text-gray-400"
                                            >
                                                Unavailable
                                            </span>
                                        </div>
                                    </div>

                                    <template v-if="service.description">
                                        <p
                                            class="mt-1.5 text-xs leading-5 text-gray-500 dark:text-gray-400"
                                            :class="
                                                isExpanded(service)
                                                    ? ''
                                                    : 'line-clamp-2'
                                            "
                                        >
                                            {{ service.description }}
                                        </p>

                                        <span
                                            v-if="
                                                service.description.length >
                                                DESCRIPTION_PREVIEW_LENGTH
                                            "
                                            role="button"
                                            tabindex="0"
                                            class="mt-1 inline-flex items-center gap-0.5 text-xs font-semibold text-primary hover:underline"
                                            @click.stop="
                                                toggleDescription(service)
                                            "
                                            @keydown.enter.stop.prevent="
                                                toggleDescription(service)
                                            "
                                        >
                                            {{
                                                isExpanded(service)
                                                    ? "Show less"
                                                    : "Show more"
                                            }}
                                            <ChevronDown
                                                class="h-3.5 w-3.5 transition-transform"
                                                :class="
                                                    isExpanded(service)
                                                        ? 'rotate-180'
                                                        : ''
                                                "
                                            />
                                        </span>
                                    </template>
                                </div>
                            </button>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between gap-3 rounded-b-2xl border-t border-gray-100 bg-gray-50/60 px-6 py-4 dark:border-white/10 dark:bg-white/5"
                    >
                        <div class="min-w-0">
                            <p
                                class="truncate text-xs text-gray-500 dark:text-gray-400"
                            >
                                {{
                                    selectedService
                                        ? selectedService.service_name
                                        : "No service selected"
                                }}
                            </p>
                            <p
                                class="text-lg font-bold leading-tight text-gray-900 dark:text-white"
                            >
                                {{ formatCurrency(selectedTotal) }}
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                                @click="$emit('close')"
                            >
                                Cancel
                            </button>

                            <button
                                type="button"
                                :disabled="localSelected === null"
                                class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white shadow-sm shadow-primary/30 transition-all hover:opacity-90 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
                                @click="apply"
                            >
                                Select Service
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import {
    Check,
    ChevronDown,
    Clock,
    PackageSearch,
    Search,
    X,
} from "lucide-vue-next";
import type { Service } from "~/types/service";
import type { BookedService } from "~/types/booking";
import Combobox from "~/components/ui/Combobox.vue";
import { formatCurrency } from "~/utils/currency";
import { formatDuration } from "~/utils/time";

const props = withDefaults(
    defineProps<{
        open: boolean;
        services: Service[];
        modelValue: BookedService[];
    }>(),
    {
        services: () => [],
        modelValue: () => [],
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: BookedService[]): void;
    (e: "close"): void;
}>();

const localSelected = ref<number | null>(null);

const searchQuery = ref("");
const selectedCategory = ref<string | null>(null);
const DESCRIPTION_PREVIEW_LENGTH = 110;

const durationLabel = (service: Service) => {
    const value = String(service.maximum_duration ?? "").trim();
    if (!value) return "";

    const [h = 0, m = 0, s = 0] = value.split(":").map(Number);
    const hours = value.includes(":")
        ? h + m / 60 + s / 3600
        : Number(value) / 60;

    return Number.isFinite(hours) ? formatDuration(hours) : "";
};
const expandedServices = ref<Record<number, boolean>>({});

const isExpanded = (service: Service) =>
    service.service_id != null && !!expandedServices.value[service.service_id];

function toggleDescription(service: Service) {
    if (service.service_id == null) return;
    expandedServices.value[service.service_id] = !isExpanded(service);
}

watch(
    () => props.modelValue,
    (val) => {
        localSelected.value =
            val.length && val[0]?.service_id != null ? val[0].service_id : null;
    },
    { immediate: true },
);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            searchQuery.value = "";
            selectedCategory.value = null;
            expandedServices.value = {};

            localSelected.value =
                props.modelValue.length &&
                props.modelValue[0]?.service_id != null
                    ? props.modelValue[0].service_id
                    : null;
        }
    },
);

const categories = computed(() => {
    const names = props.services
        .map((service) => service.category_name)
        .filter((name): name is string => Boolean(name));

    return [...new Set(names)].sort();
});

const categoryOptions = computed(() => [
    {
        label: "All categories",
        value: null,
    },
    ...categories.value.map((category) => ({
        label: category,
        value: category,
    })),
]);

const filteredServices = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return props.services.filter((service) => {
        if (service.service_id == null) {
            return false;
        }

        const matchesSearch =
            !query || service.service_name.toLowerCase().includes(query);

        const matchesCategory =
            !selectedCategory.value ||
            service.category_name === selectedCategory.value;

        return matchesSearch && matchesCategory;
    });
});

const groupedServices = computed(() => {
    const groups = new Map<string, Service[]>();

    for (const service of filteredServices.value) {
        const key = service.category_name || "Other";

        if (!groups.has(key)) {
            groups.set(key, []);
        }

        groups.get(key)!.push(service);
    }

    return Array.from(groups.entries())
        .sort((a, b) => a[0].localeCompare(b[0]))
        .map(([category, items]) => ({
            category,
            items,
        }));
});

const selectedService = computed(() => {
    if (localSelected.value === null) {
        return null;
    }

    return (
        props.services.find(
            (service) => service.service_id === localSelected.value,
        ) ?? null
    );
});

const selectedTotal = computed(() => {
    return selectedService.value ? Number(selectedService.value.price) : 0;
});

function isChecked(service: Service) {
    return (
        service.service_id != null && service.service_id === localSelected.value
    );
}

// Tapping the chosen service again clears it, which is the only way out of a
// selection now that there is no checkbox to untick.
function select(service: Service) {
    if (service.service_id == null || service.is_available === false) return;

    localSelected.value = isChecked(service) ? null : service.service_id;
}

function selectCategory(value: string | null) {
    selectedCategory.value = value;
}

function apply() {
    if (localSelected.value === null) {
        return;
    }

    const service = props.services.find(
        (item) => item.service_id === localSelected.value,
    );

    if (!service || service.service_id == null) {
        return;
    }

    const selected: BookedService[] = [
        {
            service_id: service.service_id,
            service_name: service.service_name,
            price: Number(service.price),
        },
    ];

    emit("update:modelValue", selected);
    emit("close");
}
</script>
