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
                        @keyup.enter="handleSearch"
                    />
                </div>

                <div class="relative shrink-0">
                    <button
                        type="button"
                        class="flex items-center gap-4 px-4 py-2.5 text-sm border border-slate-300 rounded-lg bg-white hover:bg-slate-50 transition-colors"
                        @click="dropdownOpen = !dropdownOpen"
                    >
                        <span class="text-slate-600">
                            Location
                            <span class="text-slate-900 font-medium ml-1">{{
                                locationLabel
                            }}</span>
                        </span>
                        <span class="text-slate-300">|</span>
                        <span class="text-slate-600">
                            Care Type
                            <span class="text-slate-900 font-medium ml-1">{{
                                careTypeLabel
                            }}</span>
                        </span>
                        <span class="text-slate-300">|</span>
                        <span class="text-slate-600">
                            Sort by
                            <span class="text-slate-900 font-medium ml-1">{{
                                sortLabel
                            }}</span>
                        </span>
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
                            class="absolute right-0 z-30 mt-2 w-[650px] max-w-[90vw] bg-white rounded-xl shadow-lg border border-slate-200 p-6"
                        >
                            <button
                                type="button"
                                class="absolute right-4 top-4 text-slate-400 hover:text-slate-600"
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

                            <div class="flex items-start gap-6 py-2">
                                <p
                                    class="w-24 shrink-0 text-sm font-semibold text-slate-900 pt-1.5"
                                >
                                    Location
                                </p>
                                <div class="flex-1">
                                    <BaseInput
                                        :model-value="searchLocation"
                                        @update:model-value="onLocationInput"
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
                                                    @loading="locating = $event"
                                                />
                                            </span>
                                        </template>
                                    </BaseInput>
                                </div>
                            </div>

                            <div class="h-px bg-slate-200 my-4" />

                            <div class="flex items-start gap-6 py-2">
                                <p
                                    class="w-24 shrink-0 text-sm font-semibold text-slate-900 pt-1.5"
                                >
                                    Care Type
                                </p>
                                <div class="flex flex-wrap gap-x-6 gap-y-3">
                                    <button
                                        v-for="item in planCodeList"
                                        :key="item.value"
                                        type="button"
                                        class="text-sm transition"
                                        :class="
                                            planCodeType === item.value
                                                ? 'text-primary font-medium'
                                                : 'text-slate-600 hover:text-primary'
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
                                    class="w-24 shrink-0 text-sm font-semibold text-slate-900 pt-1.5"
                                >
                                    Sort by
                                </p>
                                <div class="flex flex-wrap gap-x-6 gap-y-3">
                                    <button
                                        v-for="sort in sortOptions"
                                        :key="sort.value"
                                        type="button"
                                        class="text-sm transition"
                                        :class="
                                            activeSortOption === sort.value
                                                ? 'text-primary font-medium'
                                                : 'text-slate-600 hover:text-primary'
                                        "
                                        @click="activeSortOption = sort.value"
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
                    </transition>
                </div>

                <button
                    class="w-12 h-12 bg-primary hover:bg-primary-600 active:scale-95 flex items-center justify-center rounded-lg border border-primary shadow-sm transition-all duration-150 shrink-0"
                    @click="handleSearch"
                    type="button"
                    aria-label="Search"
                >
                    <Search extraClass="text-white" />
                </button>
            </div>
        </div>
    </header>

    <header
        class="md:hidden border-b border-slate-200/50 bg-white shadow-sm sticky top-0 z-40"
    >
        <div class="p-4 space-y-4">
            <div>
                <BaseInput
                    v-model="searchName"
                    :is-search="true"
                    placeholder="Search provider name"
                    input-class="px-4 py-2.5 rounded-lg"
                    @keyup.enter="handleSearch"
                />
            </div>

            <div>
                <label
                    class="mb-2 block text-xs font-semibold text-slate-600 uppercase tracking-wide"
                    >Location</label
                >
                <BaseInput
                    :model-value="searchLocation"
                    @update:model-value="onLocationInput"
                    :placeholder="locating ? 'Locating...' : 'Enter your city'"
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

            <div class="flex gap-3 pt-2">
                <button
                    type="button"
                    class="flex-1 py-2.5 text-sm font-medium text-slate-600 hover:text-slate-900 border border-slate-300 rounded-lg transition"
                    @click="resetFilters"
                >
                    Reset
                </button>
                <button
                    type="button"
                    class="flex-1 py-2.5 bg-primary hover:opacity-90 flex items-center justify-center gap-2 rounded-lg border border-primary text-white text-sm font-medium transition"
                    @click="handleSearch"
                >
                    <Search extraClass="text-white" />
                    <span>Search</span>
                </button>
            </div>
        </div>
    </header>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import Location from "~/components/icons/location.vue";
import Search from "~/components/icons/search.vue";
import Dropdown from "~/components/icons/dropdown.vue";

const route = useRoute();
const router = useRouter();

const dropdownOpen = ref(false);
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

const handleSearch = () => {
    if (!searchLocation.value?.trim()) {
        searchLocation.value = DEFAULT_LOCATION.label;
        lat.value = DEFAULT_LOCATION.lat;
        long.value = DEFAULT_LOCATION.long;
    } else if (!lat.value || !long.value) {
        lat.value = "";
        long.value = "";
    }

    updateQuery();
};

function resetFilters() {
    searchName.value = "";
    searchLocation.value = DEFAULT_LOCATION.label;
    lat.value = DEFAULT_LOCATION.lat;
    long.value = DEFAULT_LOCATION.long;
    planCodeType.value = "C";
    activeSortOption.value = "recommended";
}
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
