<template>
    <section class="py-16 font-sans">
        <div class="w-[88%] max-w-[1600px] mx-auto px-4 sm:px-10">
            <div class="mb-10">
                <div class="flex items-center gap-2.5">
                    <span class="relative flex h-2 w-2">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-75"
                        ></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-primary"></span>
                    </span>
                    <span
                        class="text-xs font-bold tracking-[0.16em] uppercase text-primary dark:text-primary-300"
                    >
                        Featured
                    </span>
                </div>

                <h2
                    class="mt-3 text-2xl md:text-3xl font-bold text-secondary dark:text-white"
                >
                    Most trusted homecare
                </h2>

                <p
                    class="mt-4 text-sm text-muted max-w-xl leading-relaxed dark:text-gray-400"
                >
                    Explore highly rated caregiving branches based on reviews,
                    availability, and service quality.
                </p>
            </div>

            <!-- Loading skeleton: same grid as the real list -->
            <div v-if="loading" :class="gridClass">
                <div
                    v-for="n in 3"
                    :key="n"
                    class="border border-muted-light rounded-2xl overflow-hidden animate-pulse bg-white dark:bg-secondary dark:border-white/10"
                >
                    <div class="h-44 bg-muted-light dark:bg-white/10"></div>
                    <div class="p-4 space-y-3">
                        <div class="h-4 bg-muted-light rounded w-3/4 dark:bg-white/10"></div>
                        <div class="h-3 bg-muted-light rounded w-1/2 dark:bg-white/10"></div>
                        <div class="flex gap-2 mt-4">
                            <div
                                class="h-6 w-24 bg-muted-light rounded-full dark:bg-white/10"
                            ></div>
                            <div
                                class="h-6 w-24 bg-muted-light rounded-full dark:bg-white/10"
                            ></div>
                        </div>
                    </div>
                    <div class="h-[52px] border-t border-muted-light bg-light dark:border-white/10 dark:bg-white/5"></div>
                </div>
            </div>

            <!-- Cards stretch to fill the row, so two branches use the full width -->
            <div v-else :class="gridClass">
                <CardBooking
                    :variant="1"
                    v-for="branch in branches"
                    :key="branch.uuid"
                    :branch="branch"
                    @select="handleSelect"
                />

                <!-- Fills the empty slot when there are fewer than three branches -->
                <NuxtLink
                    v-if="branches.length > 0 && branches.length < 3"
                    to="/booking/search"
                    class="group flex min-h-[260px] flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-primary-200 bg-white/60 p-6 text-center transition-all duration-300 hover:-translate-y-1 hover:border-primary hover:shadow-xl dark:border-primary-500/30 dark:bg-white/5"
                >
                    <span
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary dark:bg-primary-500/10 dark:text-primary-300"
                    >
                        <Search class="h-5 w-5" />
                    </span>
                    <span class="font-semibold text-secondary dark:text-white">
                        Browse all providers
                    </span>
                    <span class="max-w-[220px] text-xs leading-relaxed text-muted dark:text-gray-400">
                        Search by location, service and availability.
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary dark:text-primary-300">
                        Start searching
                        <ArrowRight class="h-3 w-3 transition-transform group-hover:translate-x-1" />
                    </span>
                </NuxtLink>
            </div>

            <div
                v-if="!loading && branches && branches.length === 0"
                class="text-center py-16"
            >
                <p class="text-sm text-muted dark:text-gray-400">No branches available.</p>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { Search, ArrowRight } from "lucide-vue-next";
import { branchService } from "~/api/branch/BranchService";
import CardBooking from "./CardBooking.vue";
import type { BranchRetrieve } from "~/types/branch";

defineEmits(["select"]);

const branches = ref<BranchRetrieve[]>([]);
const loading = ref(true);

// Same card size everywhere: 1 column on phones, 2 on tablets, 3 on desktop.
const gridClass = "grid gap-5 sm:grid-cols-2 lg:grid-cols-3";

const handleSelect = (branch: BranchRetrieve) => {
    navigateTo({
        path: `/booking/provider/${branch.uuid}`,
    });
};
onMounted(async () => {
    loading.value = true;
    try {
        const res = await branchService.featured({ per_page: 9 });
        branches.value = res?.data ?? [];
    } catch (err) {
        console.error(err);
        branches.value = [];
    } finally {
        loading.value = false;
    }
});
</script>