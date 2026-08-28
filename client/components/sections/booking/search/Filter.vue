<template>
    <header
        class="hidden md:block sticky top-0 z-50 border-b border-slate-200/50 bg-white/95 backdrop-blur-md shadow-sm"
    >
        <div class="relative px-6 lg:px-10 py-4">
            <div class="flex gap-4 items-center flex-1">
                <div class="relative flex-1 max-w-sm">
                    <BaseInput
                        v-model="searchName"
                        :is-search="true"
                        placeholder="Provider name or service"
                        input-class="px-4 py-2.5 rounded-lg"
                    />
                </div>

                <div class="relative shrink-0">
                    <button
                        type="button"
                        class="flex items-center gap-4 rounded-lg border px-4 py-2.5 text-sm transition-colors"
                        :class="
                            hasActiveFilters
                                ? 'border-primary/30 bg-primary/5 hover:bg-primary/10'
                                : 'border-slate-300 bg-white hover:bg-slate-50'
                        "
                        @click="dropdownOpen = !dropdownOpen"
                    >
                        <span class="flex items-center gap-1.5 text-slate-600">
                            <MapPin class="h-3.5 w-3.5 text-slate-400" />
                            <span class="text-slate-900 font-medium">{{
                                locationLabel
                            }}</span>
                        </span>
                        <span class="text-slate-300">|</span>
                        <span class="flex items-center gap-1.5 text-slate-600">
                            <HeartPulse class="h-3.5 w-3.5 text-slate-400" />
                            <span class="text-slate-900 font-medium">{{
                                careTypeLabel
                            }}</span>
                        </span>
                        <span class="text-slate-300">|</span>
                        <span class="flex items-center gap-1.5 text-slate-600">
                            <ArrowUpDown class="h-3.5 w-3.5 text-slate-400" />
                            <span class="text-slate-900 font-medium">{{
                                sortLabel
                            }}</span>
                        </span>

                        <span
                            v-if="hasActiveFilters"
                            class="h-1.5 w-1.5 shrink-0 rounded-full bg-primary"
                        />

                        <Dropdown :isOpen="dropdownOpen" />
                    </button>

                    <div
                        v-if="dropdownOpen"
                        class="fixed inset-0 z-20"
                        @click="dropdownOpen = false"
                    />

                    <transition name="fade-slide">
                        <div
                            v-if="dropdownOpen"
                            class="absolute right-0 z-30 mt-2 w-[650px] max-w-[90vw] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
                        >
                            <div
                                class="flex items-start justify-between gap-4 border-b border-slate-100 bg-slate-50/80 px-6 py-4"
                            >
                                <div>
                                    <p
                                        class="text-sm font-semibold text-slate-900"
                                    >
                                        Refine Your Search
                                    </p>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        Narrow providers down by location, care
                                        type, and how results are sorted.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="shrink-0 text-slate-400 hover:text-slate-600"
                                    @click="dropdownOpen = false"
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
                            </div>

                            <div class="p-6">
                                <div class="flex items-start gap-6 py-2">
                                    <p
                                        class="flex w-24 shrink-0 items-center gap-1.5 pt-1.5 text-sm font-semibold text-slate-900"
                                    >
                                        <MapPin
                                            class="h-3.5 w-3.5 text-slate-400"
                                        />
                                        Location
                                    </p>
                                    <div class="flex-1">
                                        <BaseInput
                                            :model-value="searchLocation"
                                            @update:model-value="
                                                onLocationInput
                                            "
                                            :placeholder="
                                                locating
                                                    ? 'Locating...'
                                                    : 'Enter your city'
                                            "
                                            input-class="py-2.5 rounded-lg w-full"
                                            :readonly="locating"
                                        >
                                            <template #suffix>
                                                <span
                                                    class="pr-3 flex items-center"
                                                >
                                                    <Location
                                                        clickable
                                                        @get-location="
                                                            handleLocation
                                                        "
                                                        @loading="
                                                            locating = $event
                                                        "
                                                    />
                                                </span>
                                            </template>
                                        </BaseInput>
                                    </div>
                                </div>

                                <div class="h-px bg-slate-200 my-4" />

                                <div class="flex items-start gap-6 py-2">
                                    <p
                                        class="flex w-24 shrink-0 items-center gap-1.5 pt-1.5 text-sm font-semibold text-slate-900"
                                    >
                                        <HeartPulse
                                            class="h-3.5 w-3.5 text-slate-400"
                                        />
                                        Care Type
                                    </p>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="item in planCodeList"
                                            :key="item.value"
                                            type="button"
                                            class="rounded-full border px-3.5 py-1.5 text-sm font-medium transition"
                                            :class="
                                                planCodeType === item.value
                                                    ? 'border-primary bg-primary text-white'
                                                    : 'border-slate-200 text-slate-600 hover:border-primary/40 hover:text-primary'
                                            "
                                            @click="planCodeType = item.value"
                                        >
                                            {{ item.label }}
                                        </button>
                                    </div>
                                </div>

                                <div class="h-px bg-slate-200 my-4" />

                                <div class="flex items-start gap-6 py-2">
                                    <p
                                        class="flex w-24 shrink-0 items-center gap-1.5 pt-1.5 text-sm font-semibold text-slate-900"
                                    >
                                        <ArrowUpDown
                                            class="h-3.5 w-3.5 text-slate-400"
                                        />
                                        Sort by
                                    </p>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="sort in sortOptions"
                                            :key="sort.value"
                                            type="button"
                                            class="rounded-full border px-3.5 py-1.5 text-sm font-medium transition"
                                            :class="
                                                activeSortOption === sort.value
                                                    ? 'border-primary bg-primary text-white'
                                                    : 'border-slate-200 text-slate-600 hover:border-primary/40 hover:text-primary'
                                            "
                                            @click="
                                                activeSortOption = sort.value
                                            "
                                        >
                                            {{ sort.label }}
                                        </button>
                                    </div>
                                </div>

                                <div class="h-px bg-slate-200 my-4" />

                                <div class="flex justify-end gap-3">
                                    <button
                                        type="button"
                                        class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition"
                                        @click="resetFilters"
                                    >
                                        Reset
                                    </button>
                                    <button
                                        type="button"
                                        class="px-5 py-2 text-sm font-medium rounded-lg bg-primary text-white hover:opacity-90 transition"
                                        @click="applyFilters"
                                    >
                                        Apply
                                    </button>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
        </div>
    </header>

    <header
        class="md:hidden border-b border-slate-200/50 bg-white shadow-sm sticky top-0 z-40"
    >
        <div class="p-4 space-y-3">
            <div class="flex items-center gap-2">
                <div class="flex-1">
                    <BaseInput
                        v-model="searchName"
                        :is-search="true"
                        placeholder="Search provider name"
                        input-class="px-4 py-2.5 rounded-lg"
                    />
                </div>

                <button
                    type="button"
                    class="relative flex h-[42px] shrink-0 items-center gap-1.5 rounded-lg border px-3.5 text-sm font-medium transition-colors"
                    :class="
                        hasActiveFilters
                            ? 'border-primary/30 bg-primary/5 text-primary'
                            : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50'
                    "
                    @click="mobileFiltersOpen = !mobileFiltersOpen"
                >
                    <SlidersHorizontal class="h-4 w-4" />
                    Filters
                    <span
                        v-if="hasActiveFilters"
                        class="absolute -top-1 -right-1 h-2 w-2 rounded-full bg-primary"
                    />
                </button>
            </div>

            <div v-if="mobileFiltersOpen" class="space-y-4 pt-1">
                <div>
                    <label
                        class="mb-2 block text-xs font-semibold text-slate-600 uppercase tracking-wide"
                        >Location</label
                    >
                    <BaseInput
                        :model-value="searchLocation"
                        @update:model-value="onLocationInput"
                        :placeholder="
                            locating ? 'Locating...' : 'Enter your city'
                        "
                        input-class="px-4 py-2.5 rounded-lg w-full"
                        :readonly="locating"
                    >
                        <template #suffix>
                            <span class="pr-3 flex items-center">
                                <Location
                                    clickable
                                    @get-location="handleLocation"
                                    @loading="locating = $event"
                                />
                            </span>
                        </template>
                    </BaseInput>
                </div>

                <div>
                    <label
                        class="mb-2 block text-xs font-semibold text-slate-600 uppercase tracking-wide"
                        >Care Type</label
                    >
                    <Combobox
                        v-model="planCodeType"
                        :items="planCodeList"
                        input-class="px-4 py-2.5 rounded-lg"
                        :search-bar="false"
                        @update:modelValue="updateQuery"
                    />
                </div>

                <div>
                    <label
                        class="mb-2 block text-xs font-semibold text-slate-600 uppercase tracking-wide"
                        >Sort by</label
                    >
                    <div class="flex gap-2 flex-wrap">
                        <button
                            v-for="sort in sortOptions"
                            :key="sort.value"
                            type="button"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors"
                            :class="
                                activeSortOption === sort.value
                                    ? 'bg-primary text-white border-primary'
                                    : 'bg-white text-slate-600 border-slate-300 hover:border-primary hover:text-primary'
                            "
                            @click="activeSortOption = sort.value"
                        >
                            {{ sort.label }}
                        </button>
                    </div>
                </div>

                <div class="pt-2">
                    <button
                        type="button"
                        class="w-full py-2.5 text-sm font-medium text-slate-600 hover:text-slate-900 border border-slate-300 rounded-lg transition"
                        @click="resetFilters"
                    >
                        Reset
                    </button>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup lang="ts">
import { ref, computed, watch, onBeforeUnmount } from "vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import Location from "~/components/icons/location.vue";
import Dropdown from "~/components/icons/dropdown.vue";
import {
    MapPin,
    HeartPulse,
    ArrowUpDown,
    SlidersHorizontal,
} from "lucide-vue-next";

const route = useRoute();
const router = useRouter();

const dropdownOpen = ref(false);
const mobileFiltersOpen = ref(false);
const locating = ref(false);

const props = defineProps<{
    searchName?: string;
    searchLocation?: string;
    lat?: number;
    long?: number;
    codeType?: string;
    perPage?: number;
}>();

const planCodeList = [
    { label: "All (Homecare & Inhouse Facility)", value: "C" },
    { label: "Homecare Services", value: "A" },
    { label: "In-house Facility", value: "B" },
];

const sortOptions = [
    { label: "Recommended", value: "recommended" },
    { label: "Highest Rated", value: "highest_rated" },
    { label: "Most Popular", value: "most_popular" },
    { label: "Nearest", value: "nearest" },
];

const DEFAULT_LOCATION = {
    label: "Davao City",
    lat: 7.1907,
    long: 125.4553,
};

const activeSortOption = ref((route.query.sort as string) ?? "recommended");

const searchName = ref(
    (route.query.provider_name as string) ?? props.searchName ?? "",
);

// Debounced instead of requiring a search button click / Enter press —
// waits for a pause in typing so we're not firing a request per keystroke.
let searchDebounce: ReturnType<typeof setTimeout> | undefined;

watch(searchName, () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => updateQuery(), 400);
});

onBeforeUnmount(() => clearTimeout(searchDebounce));

const searchLocation = ref(
    (route.query.location as string) ??
        props.searchLocation ??
        DEFAULT_LOCATION.label,
);

const planCodeType = ref(
    (route.query.plan_code as string) ?? props.codeType ?? "C",
);

const lat = ref<string | number>(
    (route.query.lat as string) ?? props.lat ?? DEFAULT_LOCATION.lat,
);

const long = ref<string | number>(
    (route.query.long as string) ?? props.long ?? DEFAULT_LOCATION.long,
);

const locationLabel = computed(() => {
    const loc = searchLocation.value || DEFAULT_LOCATION.label;
    return loc.length > 15 ? loc.substring(0, 12) + "..." : loc;
});

const careTypeLabel = computed(() => {
    const found = planCodeList.find((p) => p.value === planCodeType.value);
    return found?.label.split(" ")[0] ?? "All";
});

const sortLabel = computed(() => {
    const found = sortOptions.find((s) => s.value === activeSortOption.value);
    const label = found?.label ?? "Recommended";
    return label.split(" ")[0];
});

function buildQuery() {
    return {
        provider_name: String(searchName.value ?? ""),
        location: String(searchLocation.value || DEFAULT_LOCATION.label),
        lat: String(lat.value ?? ""),
        long: String(long.value ?? ""),
        plan_code: String(planCodeType.value ?? "C"),
        sort: String(activeSortOption.value ?? "recommended"),
    };
}

const updateQuery = () => {
    const query = buildQuery();
    const current = {
        provider_name: String(route.query.provider_name ?? ""),
        location: String(route.query.location ?? DEFAULT_LOCATION.label),
        lat: String(route.query.lat ?? ""),
        long: String(route.query.long ?? ""),
        plan_code: String(route.query.plan_code ?? "C"),
        sort: String(route.query.sort ?? "recommended"),
    };

    if (JSON.stringify(current) === JSON.stringify(query)) {
        return;
    }

    router.replace({ query });
};

const onLocationInput = (value: string) => {
    searchLocation.value = value;
    lat.value = "";
    long.value = "";
};

const handleLocation = (data: any) => {
    searchLocation.value = data.label || DEFAULT_LOCATION.label;
    lat.value = data.lat ?? DEFAULT_LOCATION.lat;
    long.value = data.lng ?? DEFAULT_LOCATION.long;
    locating.value = false;
    updateQuery();
};

function applyFilters() {
    updateQuery();
    dropdownOpen.value = false;
}

function resetFilters() {
    searchName.value = "";
    searchLocation.value = DEFAULT_LOCATION.label;
    lat.value = DEFAULT_LOCATION.lat;
    long.value = DEFAULT_LOCATION.long;
    planCodeType.value = "C";
    activeSortOption.value = "recommended";

    // Was only resetting the local form state — the URL query (and the
    // results it drives) stayed on whatever was previously applied.
    updateQuery();
    dropdownOpen.value = false;
}

const hasActiveFilters = computed(
    () =>
        planCodeType.value !== "C" ||
        activeSortOption.value !== "recommended" ||
        (searchLocation.value || "") !== DEFAULT_LOCATION.label,
);
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.2s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
