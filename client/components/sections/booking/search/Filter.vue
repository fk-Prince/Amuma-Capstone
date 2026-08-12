<template>
    <header
        class="hidden md:block sticky top-0 z-50 border-b border-muted-light bg-white/95 backdrop-blur shadow-sm"
    >
        <div class="relative px-6 lg:px-10 py-5">
            <button
                @click="isHide = !isHide"
                aria-label="Toggle search filters"
                class="absolute z-20 left-6 lg:left-10 w-9 h-9 rounded-full border border-muted-light bg-white flex items-center justify-center shadow-md hover:border-primary hover:text-primary transition-all duration-300"
                :style="{ bottom: isHide ? '-14px' : '-18px' }"
            >
                <Dropdown :isOpen="!isHide" />
            </button>

            <Transition name="collapse">
                <div
                    v-show="!isHide"
                    class="w-full max-w-[80rem] grid grid-cols-[1.2fr_1fr_1fr_auto] items-end gap-4"
                >
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold text-muted uppercase tracking-wide"
                            >Search</label
                        >
                        <BaseInput
                            v-model="searchName"
                            :is-search="true"
                            placeholder="Provider name or service"
                            input-class="px-4 py-3 rounded-xl"
                            @keyup.enter="handleSearch"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold text-muted uppercase tracking-wide"
                            >Location</label
                        >
                        <BaseInput
                            :model-value="searchLocation"
                            @update:model-value="onLocationInput"
                            :placeholder="
                                locating ? 'Locating...' : 'Enter your city'
                            "
                            input-class="px-4 py-3 rounded-xl w-full"
                            :readonly="locating"
                            @keyup.enter="handleSearch"
                        >
                            <template #suffix>
                                <span class="pr-4 flex items-center">
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
                            class="mb-1.5 block text-xs font-semibold text-muted uppercase tracking-wide"
                            >Care Type</label
                        >
                        <Combobox
                            v-model="planCodeType"
                            :items="planCodeList"
                            input-class="px-4 py-3 rounded-xl"
                            :searchBar="false"
                            @update:modelValue="updateQuery"
                        />
                    </div>

                    <button
                        class="w-12 h-12 bg-primary hover:bg-primary-600 active:scale-95 flex items-center justify-center rounded-full border border-primary shadow-sm transition-all duration-150"
                        @click="handleSearch"
                        aria-label="Search"
                    >
                        <Search extraClass="text-white" />
                    </button>
                    <h3>TO BE CHANGE</h3>
                </div>
            </Transition>

            <Transition name="collapse">
                <div
                    class="flex items-center gap-3 flex-wrap"
                    :class="
                        isHide
                            ? 'mt-0'
                            : 'mt-5 pt-4 border-t border-muted-light'
                    "
                >
                    <span
                        class="text-xs font-semibold text-muted uppercase tracking-wide shrink-0"
                        >Sort</span
                    >
                    <button
                        v-for="sort in sortOptions"
                        :key="sort.value"
                        @click="handleSortChange(sort.value)"
                        :class="[
                            'px-4 py-1.5 rounded-full text-sm font-medium border transition-colors',
                            activeSortOption === sort.value
                                ? 'bg-primary text-white border-primary shadow-sm'
                                : 'bg-white text-muted border-muted-light hover:border-primary hover:text-primary',
                        ]"
                    >
                        {{ sort.label }}
                    </button>
                </div>
            </Transition>
        </div>
    </header>

    <header
        class="md:hidden border-b border-muted-light bg-white shadow-sm relative"
    >
        <div
            class="flex items-center justify-between px-4 py-3 absolute -top-[10px] right-0"
        >
            <button
                @click="isHide = !isHide"
                aria-label="Toggle search filters"
                class="shrink-0 w-9 h-9 rounded-full border border-primary bg-white flex items-center justify-center shadow-sm transition-transform duration-300"
            >
                <Dropdown :isOpen="!isHide" />
            </button>
        </div>

        <Transition name="dropdown">
            <div v-show="!isHide" class="p-4 pt-1 space-y-4 bg-white">
                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold text-muted uppercase tracking-wide"
                        >Search</label
                    >
                    <BaseInput
                        v-model="searchName"
                        :is-search="true"
                        placeholder="Provider name or service"
                        input-class="px-4 py-2.5 rounded-xl"
                        @keyup.enter="handleSearch"
                    />
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold text-muted uppercase tracking-wide"
                        >Location</label
                    >

                    <BaseInput
                        v-model="searchLocation"
                        :placeholder="
                            locating ? 'Locating...' : 'Enter your city'
                        "
                        input-class="px-4 py-3 rounded-xl w-full"
                        :readonly="locating"
                        @keyup.enter="handleSearch"
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
                        class="mb-1.5 block text-xs font-semibold text-muted uppercase tracking-wide"
                        >Care Type</label
                    >
                    <Combobox
                        v-model="planCodeType"
                        :items="planCodeList"
                        input-class="px-4 py-2.5 rounded-xl"
                        :searchBar="false"
                        @update:modelValue="updateQuery"
                    />
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold text-muted uppercase tracking-wide"
                        >Sort by</label
                    >
                    <div class="flex gap-2 flex-wrap">
                        <button
                            v-for="sort in sortOptions"
                            :key="sort.value"
                            @click="handleSortChange(sort.value)"
                            class="px-3 py-1.5 rounded-full text-xs font-medium border transition-colors"
                            :class="
                                activeSortOption === sort.value
                                    ? 'bg-primary text-white border-primary'
                                    : 'bg-white text-muted border-muted-light'
                            "
                        >
                            {{ sort.label }}
                        </button>
                    </div>
                </div>

                <button
                    class="w-full py-3 bg-primary hover:bg-primary-600 active:scale-[0.99] flex items-center justify-center gap-2 rounded-xl border border-primary shadow-sm transition-all duration-150"
                    @click="handleSearch"
                >
                    <Search extraClass="text-white" />
                    <span class="text-white text-sm font-semibold">Search</span>
                </button>
            </div>
        </Transition>
    </header>
</template>

<script setup lang="ts">
import { ref } from "vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import Location from "~/components/icons/location.vue";
import Search from "~/components/icons/search.vue";
import Dropdown from "~/components/icons/dropdown.vue";

const route = useRoute();
const router = useRouter();

const isHide = ref(false);
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

const handleSortChange = (value: string) => {
    activeSortOption.value = value;
    updateQuery();
};

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
</script>

<style scoped>
.collapse-enter-active,
.collapse-leave-active,
.dropdown-enter-active,
.dropdown-leave-active {
    transition: all 0.25s ease;
}

.collapse-enter-from,
.collapse-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

.dropdown-enter-active,
.dropdown-leave-active {
    transition: all 0.3s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-12px);
    max-height: 0;
}

.dropdown-enter-to,
.dropdown-leave-from {
    opacity: 1;
    transform: translateY(0);
    max-height: 600px;
}
</style>
