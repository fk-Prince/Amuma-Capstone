<template>
    <header
        class="hidden md:block sticky top-4 z-50 rounded-2xl border border-slate-200/50 bg-white/95 backdrop-blur-md shadow-md dark:bg-secondary/95 dark:border-white/10"
    >
        <div class="relative px-6 lg:px-10 py-4">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-[220px] flex-1 max-w-sm">
                    <BaseInput
                        v-model="searchName"
                        :is-search="true"
                        placeholder="Search by name or service..."
                        input-class="px-4 py-2.5 rounded-full"
                    />
                </div>

                <!-- Location trigger -->
                <div class="relative shrink-0">
                    <button
                        ref="locationBtn"
                        type="button"
                        class="flex items-center gap-2 rounded-full border px-4 py-2.5 text-sm font-medium transition-colors"
                        :class="
                            locationOpen
                                ? 'border-primary bg-primary/5 text-primary'
                                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:bg-secondary dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5'
                        "
                        @click="toggleMenu('location')"
                    >
                        <MapPin class="h-3.5 w-3.5 text-slate-400 dark:text-gray-500" />
                        {{ locationLabel }}
                        <Dropdown :isOpen="locationOpen" />
                    </button>
                </div>

                <!-- Service type trigger -->
                <div class="relative shrink-0">
                    <button
                        ref="serviceBtn"
                        type="button"
                        class="flex items-center gap-2 rounded-full border px-4 py-2.5 text-sm font-medium transition-colors"
                        :class="
                            serviceOpen || planCodeType !== 'C'
                                ? 'border-primary bg-primary/5 text-primary'
                                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:bg-secondary dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5'
                        "
                        @click="toggleMenu('service')"
                    >
                        <HeartPulse class="h-3.5 w-3.5 text-slate-400 dark:text-gray-500" />
                        {{ careTypeLabel }}
                        <Dropdown :isOpen="serviceOpen" />
                    </button>
                </div>

                <!-- Sort trigger -->
                <div class="relative shrink-0">
                    <button
                        ref="sortBtn"
                        type="button"
                        class="flex items-center gap-2 rounded-full border px-4 py-2.5 text-sm font-medium transition-colors"
                        :class="
                            sortOpen || activeSortOption !== 'recommended'
                                ? 'border-primary bg-primary/5 text-primary'
                                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:bg-secondary dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5'
                        "
                        @click="toggleMenu('sort')"
                    >
                        <ArrowUpDown class="h-3.5 w-3.5 text-slate-400 dark:text-gray-500" />
                        {{ sortLabel }}
                        <Dropdown :isOpen="sortOpen" />
                    </button>
                </div>

                <!-- Filters (advanced panel) trigger -->
                <div class="relative ml-auto shrink-0">
                    <button
                        ref="advancedBtn"
                        type="button"
                        class="flex items-center gap-2 rounded-full border px-4 py-2.5 text-sm font-semibold transition-colors"
                        :class="
                            advancedOpen || hasActiveFilters
                                ? 'border-primary bg-primary text-white shadow-sm'
                                : 'border-primary/30 text-primary hover:bg-primary/5 dark:border-primary-400/30'
                        "
                        @click="toggleMenu('advanced')"
                    >
                        <SlidersHorizontal class="h-3.5 w-3.5" />
                        Filters
                        <span
                            v-if="hasActiveFilters"
                            class="h-1.5 w-1.5 shrink-0 rounded-full"
                            :class="advancedOpen ? 'bg-white' : 'bg-primary'"
                        />
                    </button>
                </div>
            </div>
        </div>

        <!--
            All dropdown panels are teleported to <body> so no parent
            (overflow-hidden, backdrop-blur, etc.) can clip them.
        -->
        <Teleport to="body">
            <!-- One click-away overlay for every menu -->
            <div
                v-if="activeMenu"
                class="fixed inset-0 z-[60]"
                @click="closeMenus"
            />

            <!-- Location panel -->
            <transition name="fade-slide">
                <div
                    v-if="locationOpen"
                    class="fixed z-[70] w-72 overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-lg dark:bg-secondary dark:border-white/10"
                    :style="{ top: menuPos.top + 'px', left: menuPos.left + 'px' }"
                >
                    <p
                        class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-gray-400"
                    >
                        Location
                    </p>
                    <BaseInput
                        :model-value="searchLocation"
                        @update:model-value="onLocationInput"
                        :placeholder="
                            locating ? 'Locating...' : 'Enter your city'
                        "
                        input-class="py-2.5 rounded-lg w-full"
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
                    <div class="mt-3 flex justify-end">
                        <button
                            type="button"
                            class="rounded-lg bg-primary px-4 py-1.5 text-sm font-medium text-white hover:opacity-90 transition"
                            @click="applyLocation"
                        >
                            Apply
                        </button>
                    </div>
                </div>
            </transition>

            <!-- Service type panel -->
            <transition name="fade-slide">
                <div
                    v-if="serviceOpen"
                    class="fixed z-[70] w-60 overflow-hidden rounded-xl border border-slate-200 bg-white p-2 shadow-lg dark:bg-secondary dark:border-white/10"
                    :style="{ top: menuPos.top + 'px', left: menuPos.left + 'px' }"
                >
                    <button
                        v-for="item in planCodeList"
                        :key="item.value"
                        type="button"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm font-medium transition"
                        :class="
                            planCodeType === item.value
                                ? 'bg-primary/10 text-primary'
                                : 'text-slate-600 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-white/5'
                        "
                        @click="selectService(item.value)"
                    >
                        {{ item.label }}
                        <span
                            v-if="planCodeType === item.value"
                            class="h-1.5 w-1.5 shrink-0 rounded-full bg-primary"
                        />
                    </button>
                </div>
            </transition>

            <!-- Sort panel -->
            <transition name="fade-slide">
                <div
                    v-if="sortOpen"
                    class="fixed z-[70] w-52 overflow-hidden rounded-xl border border-slate-200 bg-white p-2 shadow-lg dark:bg-secondary dark:border-white/10"
                    :style="{ top: menuPos.top + 'px', left: menuPos.left + 'px' }"
                >
                    <button
                        v-for="sort in sortOptions"
                        :key="sort.value"
                        type="button"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm font-medium transition"
                        :class="
                            activeSortOption === sort.value
                                ? 'bg-primary/10 text-primary'
                                : 'text-slate-600 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-white/5'
                        "
                        @click="selectSort(sort.value)"
                    >
                        {{ sort.label }}
                        <span
                            v-if="activeSortOption === sort.value"
                            class="h-1.5 w-1.5 shrink-0 rounded-full bg-primary"
                        />
                    </button>
                </div>
            </transition>

            <!-- Advanced filters panel (right-aligned to the Filters button) -->
            <transition name="fade-slide">
                <div
                    v-if="advancedOpen"
                    class="fixed z-[70] w-[420px] max-w-[90vw] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg dark:bg-secondary dark:border-white/10"
                    :style="{ top: menuPos.top + 'px', right: menuPos.right + 'px' }"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-slate-100 bg-slate-50/80 px-6 py-4 dark:border-white/10 dark:bg-white/5"
                    >
                        <div>
                            <p
                                class="text-sm font-semibold text-slate-900 dark:text-white"
                            >
                                Refine Your Search
                            </p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                                Fine-tune your search all in one place.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="shrink-0 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-400"
                            @click="closeMenus"
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

                    <div class="max-h-[70vh] space-y-4 overflow-y-auto p-6">
                        <div>
                            <p
                                class="mb-2 flex items-center gap-1.5 text-sm font-semibold text-slate-900 dark:text-white"
                            >
                                <MapPin
                                    class="h-3.5 w-3.5 text-slate-400 dark:text-gray-500"
                                />
                                Location
                            </p>
                            <BaseInput
                                :model-value="searchLocation"
                                @update:model-value="onLocationInput"
                                :placeholder="
                                    locating ? 'Locating...' : 'Enter your city'
                                "
                                input-class="py-2.5 rounded-lg w-full"
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

                        <div class="h-px bg-slate-200 dark:bg-white/10" />

                        <div>
                            <p
                                class="mb-2 flex items-center gap-1.5 text-sm font-semibold text-slate-900 dark:text-white"
                            >
                                <HeartPulse
                                    class="h-3.5 w-3.5 text-slate-400 dark:text-gray-500"
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
                                            : 'border-slate-200 text-slate-600 hover:border-primary/40 hover:text-primary dark:border-white/10 dark:text-gray-300'
                                    "
                                    @click="planCodeType = item.value"
                                >
                                    {{ item.label }}
                                </button>
                            </div>
                        </div>

                        <div class="h-px bg-slate-200 dark:bg-white/10" />

                        <div>
                            <p
                                class="mb-2 flex items-center gap-1.5 text-sm font-semibold text-slate-900 dark:text-white"
                            >
                                <ArrowUpDown
                                    class="h-3.5 w-3.5 text-slate-400 dark:text-gray-500"
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
                                            : 'border-slate-200 text-slate-600 hover:border-primary/40 hover:text-primary dark:border-white/10 dark:text-gray-300'
                                    "
                                    @click="activeSortOption = sort.value"
                                >
                                    {{ sort.label }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4 dark:border-white/10"
                    >
                        <button
                            type="button"
                            class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition dark:text-gray-300 dark:hover:text-white"
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
        </Teleport>
    </header>

    <header
        class="md:hidden
border-b border-slate-200/50 bg-white shadow-sm sticky top-0 z-40 dark:bg-secondary dark:border-white/10"
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
                            : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50 dark:bg-secondary dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5'
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
                        class="mb-2 block text-xs font-semibold text-slate-600 uppercase tracking-wide dark:text-gray-300"
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
                        class="mb-2 block text-xs font-semibold text-slate-600 uppercase tracking-wide dark:text-gray-300"
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
                        class="mb-2 block text-xs font-semibold text-slate-600 uppercase tracking-wide dark:text-gray-300"
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
                                    : 'bg-white text-slate-600 border-slate-300 hover:border-primary hover:text-primary dark:bg-secondary dark:text-gray-300 dark:border-white/10'
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
                        class="w-full py-2.5 text-sm font-medium text-slate-600 hover:text-slate-900 border border-slate-300 rounded-lg transition dark:text-gray-300 dark:border-white/10 dark:hover:text-white"
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
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { useGeo } from "~/composables/useGeo";
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

const locationOpen = ref(false);
const serviceOpen = ref(false);
const sortOpen = ref(false);
const advancedOpen = ref(false);
const mobileFiltersOpen = ref(false);
const locating = ref(false);

type MenuKey = "location" | "service" | "sort" | "advanced";

// Trigger button refs, used to position the teleported panels
const locationBtn = ref<HTMLElement | null>(null);
const serviceBtn = ref<HTMLElement | null>(null);
const sortBtn = ref<HTMLElement | null>(null);
const advancedBtn = ref<HTMLElement | null>(null);

const menuPos = ref({ top: 0, left: 0, right: 0 });

const activeMenu = computed<MenuKey | null>(() =>
    locationOpen.value
        ? "location"
        : serviceOpen.value
          ? "service"
          : sortOpen.value
            ? "sort"
            : advancedOpen.value
              ? "advanced"
              : null,
);

const anchorFor = (menu: MenuKey) => {
    const map = {
        location: locationBtn,
        service: serviceBtn,
        sort: sortBtn,
        advanced: advancedBtn,
    };
    return map[menu].value;
};

const updatePosition = () => {
    const menu = activeMenu.value;
    if (!menu) return;
    const el = anchorFor(menu);
    if (!el) return;

    const rect = el.getBoundingClientRect();
    menuPos.value = {
        top: rect.bottom + 8,
        left: rect.left,
        right: document.documentElement.clientWidth - rect.right,
    };
};

onMounted(() => {
    window.addEventListener("scroll", updatePosition, true);
    window.addEventListener("resize", updatePosition);
});

onBeforeUnmount(() => {
    window.removeEventListener("scroll", updatePosition, true);
    window.removeEventListener("resize", updatePosition);
});

const closeMenus = () => {
    locationOpen.value = false;
    serviceOpen.value = false;
    sortOpen.value = false;
    advancedOpen.value = false;
};

const toggleMenu = (menu: MenuKey) => {
    const wasOpen =
        menu === "location"
            ? locationOpen.value
            : menu === "service"
              ? serviceOpen.value
              : menu === "sort"
                ? sortOpen.value
                : advancedOpen.value;

    closeMenus();
    if (wasOpen) return;

    if (menu === "location") locationOpen.value = true;
    else if (menu === "service") serviceOpen.value = true;
    else if (menu === "sort") sortOpen.value = true;
    else advancedOpen.value = true;

    updatePosition();
};

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

const { resolveDefaultCenter } = useGeo();

// Where a visitor starts and what Reset returns to: their own area, found the
// same way the page picks it on a fresh visit, and Davao City if that fails.
const defaultLocation = ref({ ...DEFAULT_LOCATION });

// Kept for the session so every visit to this page doesn't repeat the lookup.
const cachedCenter = useState<typeof DEFAULT_LOCATION | null>(
    "search_default_center",
    () => null,
);

const activeSortOption = ref((route.query.sort as string) ?? "recommended");

const searchName = ref(
    (route.query.provider_name as string) ?? props.searchName ?? "",
);

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

const selectService = (value: string) => {
    planCodeType.value = value;
    updateQuery();
    serviceOpen.value = false;
};

const selectSort = (value: string) => {
    activeSortOption.value = value;
    updateQuery();
    sortOpen.value = false;
};

const applyLocation = () => {
    updateQuery();
    locationOpen.value = false;
};

function applyFilters() {
    updateQuery();
    closeMenus();
}

async function resetFilters() {
    closeMenus();

    const center = await resolveDefaultCenter();
    defaultLocation.value = center;
    cachedCenter.value = center;

    searchName.value = "";
    searchLocation.value = center.label;
    lat.value = center.lat;
    long.value = center.long;
    planCodeType.value = "C";
    activeSortOption.value = "recommended";

    updateQuery();
}

// The page can change the query itself (its own Reset), so the fields here
// follow it instead of keeping what was typed before.
watch(
    () => route.query,
    (query) => {
        searchName.value = (query.provider_name as string) ?? "";
        searchLocation.value =
            (query.location as string) ?? defaultLocation.value.label;
        lat.value = (query.lat as string) ?? defaultLocation.value.lat;
        long.value = (query.long as string) ?? defaultLocation.value.long;
        planCodeType.value = (query.plan_code as string) ?? "C";
        activeSortOption.value = (query.sort as string) ?? "recommended";
    },
);

onMounted(async () => {
    if (!cachedCenter.value) {
        cachedCenter.value = await resolveDefaultCenter();
    }

    defaultLocation.value = cachedCenter.value;
});

const hasActiveFilters = computed(
    () =>
        planCodeType.value !== "C" ||
        activeSortOption.value !== "recommended" ||
        (searchLocation.value || "") !== defaultLocation.value.label,
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