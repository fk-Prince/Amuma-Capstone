<template>
    <header
        class="hidden md:block sticky top-0 z-100 p-5 border-b bg-white shadow-sm"
    >
        <div class="relative">
            <button
                @click="isHide = !isHide"
                class="absolute z-100 left-0 rotate-180 -bottom-9 w-9 h-9 rounded-full border bg-accent flex items-center justify-center shadow-md transition-transform duration-300"
                :class="isHide ? ' -bottom-6' : '-bottom-4'"
            >
                <Dropdown :isOpen="isHide" />
            </button>

            <Transition name="collapse">
                <div
                    v-show="!isHide"
                    class="max-w-[80rem] grid grid-cols-[1fr_2fr_1fr_50px] ml-10 items-end gap-5 relative"
                >
                    <div>
                        <label
                            class="mb-2 block text-xs font-semibold text-slate-500 uppercase"
                        >
                            Search
                        </label>
                        <BaseInput
                            v-model="searchName"
                            :is-search="true"
                            placeholder="Provider name or service"
                            input-class="px-4 py-3"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-xs font-semibold text-slate-500 uppercase"
                        >
                            Location
                        </label>
                        <BaseInput
                            v-model="searchLocation"
                            placeholder="Choose City / Area"
                            input-class="px-4 py-3 w-full"
                        >
                            <template #suffix>
                                <Location clickable />
                            </template>
                        </BaseInput>
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-xs font-semibold text-slate-500 uppercase"
                        >
                            Care Type
                        </label>
                        <Combobox
                            v-model="careType"
                            :items="careTypeList"
                            input-class="px-4 py-2.5"
                            :searchBar="false"
                        />
                    </div>

                    <button
                        class="w-12 h-12 bg-secondary flex items-center justify-center rounded-full border"
                        @click="handleSearch"
                    >
                        <Search extraClass="text-white" />
                    </button>
                </div>
            </Transition>

            <Transition name="collapse">
                <div
                    class="ml-10 max-w-5xl flex gap-2 flex-wrap"
                    :class="isHide ? 'mt-0 delay-200' : 'mt-4'"
                >
                    <button
                        v-for="sort in sortOptions"
                        :key="sort.value"
                        @click="activeSortOption = sort.value"
                        :class="[
                            'px-4 py-1.5 rounded-full text-sm font-medium border transition-colors',
                            activeSortOption === sort.value
                                ? 'bg-secondary text-white border-secondary'
                                : 'bg-white text-slate-600 border-slate-200 hover:border-secondary hover:text-secondary',
                        ]"
                    >
                        {{ sort.label }}
                    </button>
                </div>
            </Transition>
        </div>
    </header>

    <header class="md:hidden border-b bg-white shadow-sm relative">
        <button
            @click="isHide = !isHide"
            class="absolute z-[1000] rotate-180 left-9 -translate-x-1/2 w-9 h-9 rounded-full border bg-accent flex items-center justify-center shadow-md transition-transform duration-300"
            :class="isHide ? '-bottom-6' : '-bottom-5'"
        >
            <Dropdown :isOpen="isHide" />
        </button>

        <Transition name="dropdown">
            <div v-show="!isHide" class="p-4 space-y-4 bg-white">
                <div>
                    <label
                        class="mb-2 block text-xs font-semibold text-slate-500 uppercase"
                    >
                        Search
                    </label>
                    <BaseInput
                        v-model="searchName"
                        :is-search="true"
                        placeholder="Provider name or service"
                        input-class="px-4 py-2.5"
                    />
                </div>

                <div>
                    <label
                        class="mb-2 block text-xs font-semibold text-slate-500 uppercase"
                    >
                        Location
                    </label>
                    <BaseInput
                        v-model="searchLocation"
                        placeholder="Choose City / Area"
                        input-class="px-4 py-2.5"
                    >
                        <template #suffix>
                            <Location clickable />
                        </template>
                    </BaseInput>
                </div>

                <div>
                    <label
                        class="mb-2 block text-xs font-semibold text-slate-500 uppercase"
                    >
                        Care Type
                    </label>
                    <Combobox
                        v-model="careType"
                        :items="careTypeList"
                        input-class="px-4 py-2.5"
                        :searchBar="false"
                    />
                </div>

                <div>
                    <label
                        class="mb-2 block text-xs font-semibold text-slate-500 uppercase"
                    >
                        Sort By
                    </label>

                    <div class="flex gap-2 flex-wrap">
                        <button
                            v-for="sort in sortOptions"
                            :key="sort.value"
                            @click="activeSortOption = sort.value"
                            class="px-3 py-1.5 rounded-full text-xs font-medium border transition-all"
                            :class="
                                activeSortOption === sort.value
                                    ? 'bg-secondary text-white border-secondary'
                                    : 'bg-white text-slate-600 border-slate-200'
                            "
                        >
                            {{ sort.label }}
                        </button>
                    </div>
                </div>

                <button
                    class="w-full py-2.5 bg-secondary flex items-center justify-center gap-2 rounded-full border"
                    @click="handleSearch"
                >
                    <Search class="text-white" />
                    <span class="text-white text-sm font-medium">Search</span>
                </button>
            </div>
        </Transition>
    </header>
</template>

<script setup lang="ts">
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import Location from "~/components/icons/location.vue";
import Search from "~/components/icons/search.vue";
import Dropdown from "~/components/icons/dropdown.vue";

const route = useRoute();

const isHide = ref(true);
const searchName = ref("");
const searchLocation = ref("");
const careType = ref("all");
const activeSortOption = ref("recommended");

const careTypeList = [
    { label: "All (Homecare & Inhouse Facility)", value: "all" },
    { label: "Homecare", value: "homecare" },
    { label: "Inhouse Facility", value: "facility" },
];

const sortOptions = [
    { label: "Recommended", value: "recommended" },
    { label: "Highest Rated", value: "highest_rated" },
    { label: "Most Popular", value: "most_popular" },
    { label: "Nearest", value: "nearest" },
    { label: "Price: Low to High", value: "price_asc" },
    { label: "Price: High to Low", value: "price_desc" },
];

const handleSearch = async () => {
    await navigateTo({
        path: "/booking/filter",
        query: {
            service: searchName.value,
            location: searchLocation.value,
            type: careType.value,
            sort: activeSortOption.value,
        },
    });
};

onMounted(() => {
    if (route.query.service) searchName.value = route.query.service as string;
    if (route.query.location)
        searchLocation.value = route.query.location as string;
    if (route.query.type) careType.value = route.query.type as string;
    if (route.query.sort) activeSortOption.value = route.query.sort as string;
});
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
