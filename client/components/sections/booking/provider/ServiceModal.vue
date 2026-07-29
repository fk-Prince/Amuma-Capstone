<!-- <template>
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
            class="fixed inset-0 w-full h-full z-50 flex items-center justify-center p-4"
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
                    class="relative bg-white w-full max-w-3xl rounded-2xl shadow-2xl flex flex-col max-h-[85vh]"
                >
                    <div
                        class="flex justify-between items-center px-6 py-4 border-b border-gray-100"
                    >
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                Select Services
                            </h2>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ localSelected.length }} selected
                            </p>
                        </div>
                        <button
                            @click="$emit('close')"
                            class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                            aria-label="Close"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                            >
                                <path d="M18 6 6 18M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div
                        class="flex items-center gap-3 px-6 py-3 border-b border-gray-100"
                    >
                        <div class="relative flex-1">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <circle cx="11" cy="11" r="7" />
                                <path d="m21 21-4.3-4.3" />
                            </svg>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search services..."
                                class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary/40"
                            />
                        </div>

                        <div class="w-52">
                            <Combobox
                                :model-value="selectedCategory"
                                @update:model-value="selectCategory"
                                :items="categoryOptions"
                                placeholder="All categories"
                                search-bar
                            />
                        </div>
                    </div>

                    <div class="overflow-y-auto px-6 py-4 space-y-2 flex-1">
                        <label
                            v-for="service in filteredServices"
                            :key="service.service_id"
                            class="flex items-center justify-between rounded-xl p-3 cursor-pointer border transition-all"
                            :class="
                                localSelected.includes(
                                    Number(service.service_id),
                                )
                                    ? 'border-primary/40 bg-primary/5 ring-1 ring-primary/20'
                                    : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                            "
                        >
                            <div class="flex items-center gap-3">
                                <input
                                    type="checkbox"
                                    :value="service.service_id"
                                    v-model="localSelected"
                                    class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/40 focus:ring-offset-0 cursor-pointer"
                                />
                                <div>
                                    <p
                                        class="font-medium text-sm text-gray-900 leading-tight"
                                    >
                                        {{ service.service_name }}
                                    </p>
                                    <p
                                        v-if="service.category_name"
                                        class="text-xs text-gray-400 mt-0.5"
                                    >
                                        {{ service.category_name }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="text-primary font-semibold text-sm whitespace-nowrap pl-3"
                            >
                                ₱{{ Number(service.price).toFixed(2) }}
                            </div>
                        </label>

                        <div
                            v-if="!filteredServices.length"
                            class="text-center text-sm text-gray-400 py-10"
                        >
                            No services match your search.
                        </div>
                    </div>

                    <div
                        class="flex justify-between items-center gap-2 px-6 py-4 border-t border-gray-100 bg-gray-50/60 rounded-b-2xl"
                    >
                        <div class="flex gap-3 items-center">
                            <p class="text-xs text-gray-400 leading-tight">
                                Total
                            </p>
                            <p class="text-base font-semibold text-gray-900">
                                ₱{{ selectedTotal.toFixed(2) }}
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <button
                                class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors"
                                @click="$emit('close')"
                            >
                                Cancel
                            </button>

                            <button
                                class="px-4 py-2 text-sm font-medium bg-primary text-white rounded-lg hover:opacity-90 active:scale-[0.98] transition-all shadow-sm shadow-primary/30"
                                @click="apply"
                            >
                                Add Selected
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
import type { Service } from "~/types/service";
import type { BookedService } from "~/types/booking";
import Combobox from "~/components/ui/Combobox.vue";

const props = defineProps<{
    open: boolean;
    services: Service[];
    modelValue: BookedService[];
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: BookedService[]): void;
    (e: "close"): void;
}>();

const localSelected = ref<number[]>([]);

watch(
    () => props.modelValue,
    (val) => {
        localSelected.value = val.map((s) => Number(s.service_id));
    },
    { immediate: true },
);

const searchQuery = ref("");

const selectedCategory = ref<string | null>(null);

const categories = computed(() => {
    const names = props.services
        .map((s) => s.category_name)
        .filter((name): name is string => Boolean(name));

    return [...new Set(names)].sort();
});

const categoryOptions = computed(() => [
    {
        label: "All categories",
        value: null,
    },
    ...categories.value.map((cat) => ({
        label: cat,
        value: cat,
    })),
]);

const filteredServices = computed(() => {
    return props.services.filter((service) => {
        const matchesSearch = service.service_name
            .toLowerCase()
            .includes(searchQuery.value.toLowerCase());

        const matchesCategory =
            !selectedCategory.value ||
            service.category_name === selectedCategory.value;

        return matchesSearch && matchesCategory;
    });
});

const selectedTotal = computed(() => {
    return props.services
        .filter((service) =>
            localSelected.value.includes(Number(service.service_id)),
        )
        .reduce((sum, service) => sum + Number(service.price), 0);
});

function selectCategory(value: string | null) {
    selectedCategory.value = value;
}

function apply() {
    const selected: BookedService[] = props.services
        .filter((service) =>
            localSelected.value.includes(Number(service.service_id)),
        )
        .map((service) => ({
            service_id: Number(service.service_id),
            service_name: service.service_name,
            price: Number(service.price),
        }));

    emit("update:modelValue", selected);
    emit("close");
}
</script> -->

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
            class="fixed inset-0 w-full h-full z-50 flex items-center justify-center p-4"
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
                    class="relative bg-white w-full max-w-3xl rounded-2xl shadow-2xl flex flex-col max-h-[85vh]"
                >
                    <!-- Header -->
                    <div
                        class="flex justify-between items-center px-6 py-4 border-b border-gray-100"
                    >
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                Select Services
                            </h2>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ localSelected.length }} of
                                {{ services.length }} selected
                            </p>
                        </div>
                        <button
                            @click="$emit('close')"
                            class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                            aria-label="Close"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Search + Category filter -->
                    <div
                        class="flex items-center gap-3 px-6 py-3 border-b border-gray-100"
                    >
                        <div class="relative flex-1">
                            <Search
                                class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
                            />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search services..."
                                class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary/40"
                            />
                        </div>

                        <div class="w-52">
                            <Combobox
                                :model-value="selectedCategory"
                                @update:model-value="selectCategory"
                                :items="categoryOptions"
                                placeholder="All categories"
                                search-bar
                            />
                        </div>
                    </div>

                    <!-- Selected count + clear all -->
                    <div
                        v-if="localSelected.length"
                        class="flex items-center justify-between px-6 py-2 bg-primary/5 border-b border-primary/10"
                    >
                        <p class="text-xs font-medium text-primary">
                            {{ localSelected.length }} service{{
                                localSelected.length === 1 ? "" : "s"
                            }}
                            selected · ₱{{ selectedTotal.toFixed(2) }}
                        </p>
                        <button
                            type="button"
                            class="text-xs font-medium text-gray-500 hover:text-red-500 transition-colors"
                            @click="localSelected = []"
                        >
                            Clear all
                        </button>
                    </div>

                    <!-- Service list -->
                    <div class="overflow-y-auto px-6 py-4 space-y-5 flex-1">
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
                                class="flex items-center justify-between rounded-xl p-3 cursor-pointer border transition-all"
                                :class="
                                    isChecked(service)
                                        ? 'border-primary/40 bg-primary/5 ring-1 ring-primary/20'
                                        : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                                "
                            >
                                <div class="flex items-center gap-3">
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
                                        type="checkbox"
                                        :value="service.service_id"
                                        v-model="localSelected"
                                        class="sr-only"
                                    />

                                    <div>
                                        <p
                                            class="font-medium text-sm text-gray-900 leading-tight"
                                        >
                                            {{ service.service_name }}
                                        </p>
                                        <p
                                            v-if="
                                                groupedServices.length <= 1 &&
                                                service.category_name
                                            "
                                            class="text-xs text-gray-400 mt-0.5"
                                        >
                                            {{ service.category_name }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="text-primary font-semibold text-sm whitespace-nowrap pl-3"
                                >
                                    ₱{{ Number(service.price).toFixed(2) }}
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div
                        class="flex justify-between items-center gap-2 px-6 py-4 border-t border-gray-100 bg-gray-50/60 rounded-b-2xl"
                    >
                        <div class="flex gap-3 items-center">
                            <p class="text-xs text-gray-400 leading-tight">
                                Total
                            </p>
                            <p class="text-base font-semibold text-gray-900">
                                ₱{{ selectedTotal.toFixed(2) }}
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <button
                                class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors"
                                @click="$emit('close')"
                            >
                                Cancel
                            </button>

                            <button
                                type="button"
                                :disabled="!localSelected.length"
                                class="px-4 py-2 text-sm font-medium bg-primary text-white rounded-lg hover:opacity-90 active:scale-[0.98] transition-all shadow-sm shadow-primary/30 disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
                                @click="apply"
                            >
                                Add Selected
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

const localSelected = ref<number[]>([]);

watch(
    () => props.modelValue,
    (val) => {
        localSelected.value = val.map((s) => s.service_id);
    },
    { immediate: true },
);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            searchQuery.value = "";
            selectedCategory.value = null;
        }
    },
);

const searchQuery = ref("");
const selectedCategory = ref<string | null>(null);

const categories = computed(() => {
    const names = props.services
        .map((s) => s.category_name)
        .filter((name): name is string => Boolean(name));

    return [...new Set(names)].sort();
});

const categoryOptions = computed(() => [
    { label: "All categories", value: null },
    ...categories.value.map((cat) => ({ label: cat, value: cat })),
]);

const filteredServices = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return props.services.filter((service) => {
        if (service.service_id == null) return false;

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
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key)!.push(service);
    }

    return Array.from(groups.entries())
        .sort((a, b) => a[0].localeCompare(b[0]))
        .map(([category, items]) => ({ category, items }));
});

function isChecked(service: Service) {
    return (
        service.service_id != null &&
        localSelected.value.includes(service.service_id)
    );
}

const selectedTotal = computed(() =>
    props.services
        .filter(
            (service) =>
                service.service_id != null &&
                localSelected.value.includes(service.service_id),
        )
        .reduce((sum, service) => sum + Number(service.price), 0),
);

function selectCategory(value: string | null) {
    selectedCategory.value = value;
}

function apply() {
    const selected: BookedService[] = props.services
        .filter(
            (service): service is Service & { service_id: number } =>
                service.service_id != null &&
                localSelected.value.includes(service.service_id),
        )
        .map((service) => ({
            service_id: service.service_id,
            service_name: service.service_name,
            price: Number(service.price),
        }));

    emit("update:modelValue", selected);
    emit("close");
}
</script>
