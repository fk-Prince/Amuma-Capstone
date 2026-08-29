<template>
    <section class="py-16 mx-auto max-w-[100rem]">
        <div class="mx-auto px-6">
            <div class="mb-10">
                <div class="flex items-center gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
                    <span
                        class="text-xs font-medium tracking-[0.16em] uppercase text-muted dark:text-gray-400"
                    >
                        Featured
                    </span>
                </div>
                <h2
                    class="mt-3 text-3xl md:text-4xl font-medium text-secondary leading-tight dark:text-white"
                >
                    Most trusted homecare
                </h2>
                <p class="mt-2 text-muted text-sm leading-relaxed max-w-md dark:text-gray-400">
                    Explore highly rated caregiving branches based on reviews,
                    availability, and service quality.
                </p>
            </div>

            <div
                v-if="loading"
                class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4"
            >
                <div
                    v-for="n in 3"
                    :key="n"
                    class="border border-muted-light rounded-2xl overflow-hidden animate-pulse dark:border-white/10"
                >
                    <div class="h-32 bg-muted-light dark:bg-white/10"></div>
                    <div class="p-4 space-y-3">
                        <div class="h-4 bg-muted-light rounded w-3/4 dark:bg-white/10"></div>
                        <div class="h-3 bg-muted-light rounded w-1/2 dark:bg-white/10"></div>
                        <div class="flex justify-between mt-4">
                            <div
                                class="h-6 w-16 bg-muted-light rounded-full dark:bg-white/10"
                            ></div>
                            <div class="h-3 w-20 bg-muted-light rounded dark:bg-white/10"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <CardBooking
                    :variant="1"
                    v-for="branch in branches"
                    :key="branch.uuid"
                    :branch="branch"
                    @select="handleSelect"
                />
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
import { branchService } from "~/api/branch/BranchService";
import CardBooking from "./CardBooking.vue";
import type { BranchRetrieve } from "~/types/branch";

defineEmits(["select"]);

const branches = ref<BranchRetrieve[]>([]);
const loading = ref(true);

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
