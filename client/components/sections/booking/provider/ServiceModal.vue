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
                    class="relative flex max-h-[85vh] w-full max-w-3xl flex-col rounded-2xl bg-white shadow-2xl"
                >
                    <div
                        class="flex items-center justify-between border-b border-gray-100 px-6 py-4"
                    >
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                Select Service
                            </h2>

                            <p class="mt-0.5 text-xs text-gray-400">
                                Select one service for this booking
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="$emit('close')"
                            class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                            aria-label="Close"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div
                        class="flex flex-col gap-3 border-b border-gray-100 px-6 py-3 sm:flex-row sm:items-center"
                    >
                        <div class="relative flex-1">
                            <Search
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                            />

                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search services..."
                                class="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm focus:border-primary/40 focus:outline-none focus:ring-2 focus:ring-primary/30"
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

                    <div
                        v-if="selectedService"
                        class="flex items-center justify-between border-b border-primary/10 bg-primary/5 px-6 py-2"
                    >
                        <p class="text-xs font-medium text-primary">
                            1 service selected · ₱{{
                                Number(selectedService.price).toFixed(2)
                            }}
                        </p>

                        <button
                            type="button"
                            class="text-xs font-medium text-gray-500 transition-colors hover:text-red-500"
                            @click="localSelected = null"
                        >
                            Clear
                        </button>
                    </div>

                    <div class="flex-1 space-y-5 overflow-y-auto px-6 py-4">
                        <div
                            v-if="!filteredServices.length"
                            class="flex flex-col items-center gap-2 py-14 text-center"
                        >
                            <PackageSearch class="h-6 w-6 text-gray-300" />

                            <p class="text-sm font-medium text-gray-500">
                                No services found
                            </p>

                            <p class="text-xs text-gray-400">
                                Try a different search term or category.
                            </p>
                        </div>

                        <div
                            v-for="group in groupedServices"
                            :key="group.category"
                            class="space-y-2"
                        >
                            <p
                                v-if="groupedServices.length > 1"
                                class="text-xs font-semibold uppercase tracking-wide text-gray-400"
                            >
                                {{ group.category }}
                            </p>

                            <label
                                v-for="service in group.items"
                                :key="service.service_id"
                                class="flex cursor-pointer items-center justify-between rounded-xl border p-3 transition-all"
                                :class="
                                    isChecked(service)
                                        ? 'border-primary/40 bg-primary/5 ring-1 ring-primary/20'
                                        : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                                "
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <span
                                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border transition"
                                        :class="
                                            isChecked(service)
                                                ? 'border-primary bg-primary text-white'
                                                : 'border-gray-300 bg-white'
                                        "
                                    >
                                        <Check
                                            v-if="isChecked(service)"
                                            class="h-3 w-3"
                                        />
                                    </span>

                                    <input
                                        type="radio"
                                        name="booking-service"
                                        :value="service.service_id"
                                        v-model="localSelected"
                                        class="sr-only"
                                    />

                                    <div class="min-w-0">
                                        <p
                                            class="truncate text-sm font-medium leading-tight text-gray-900"
                                        >
                                            {{ service.service_name }}
                                        </p>

                                        <p
                                            v-if="
                                                groupedServices.length <= 1 &&
                                                service.category_name
                                            "
                                            class="mt-0.5 text-xs text-gray-400"
                                        >
                                            {{ service.category_name }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="whitespace-nowrap pl-3 text-sm font-semibold text-primary"
                                >
                                    ₱{{ Number(service.price).toFixed(2) }}
                                </div>
                            </label>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between gap-2 rounded-b-2xl border-t border-gray-100 bg-gray-50/60 px-6 py-4"
                    >
                        <div class="flex items-center gap-3">
                            <p class="text-xs leading-tight text-gray-400">
                                Total
                            </p>

                            <p class="text-base font-semibold text-gray-900">
                                ₱{{ selectedTotal.toFixed(2) }}
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100"
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
import { Check, PackageSearch, Search, X } from "lucide-vue-next";
import type { Service } from "~/types/service";
import type { BookedService } from "~/types/booking";
import Combobox from "~/components/ui/Combobox.vue";

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
