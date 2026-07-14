<template>
    <section class="py-16">
        <div class="max-w-6xl mx-auto px-6">
            <div class="mb-10">
                <span
                    class="text-xs font-semibold uppercase tracking-widest text-slate-400"
                >
                    Featured
                </span>
                <h2
                    class="mt-2 text-3xl md:text-4xl font-bold text-slate-900 leading-tight"
                >
                    Most trusted homecare
                </h2>
                <p class="mt-2 text-slate-500 text-sm leading-relaxed max-w-md">
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
                    class="border rounded-2xl overflow-hidden animate-pulse"
                >
                    <div class="h-32 bg-slate-100"></div>
                    <div class="p-4 space-y-3">
                        <div class="h-4 bg-slate-100 rounded w-3/4"></div>
                        <div class="h-3 bg-slate-100 rounded w-1/2"></div>
                        <div class="flex justify-between mt-4">
                            <div
                                class="h-6 w-16 bg-slate-100 rounded-full"
                            ></div>
                            <div class="h-3 w-20 bg-slate-100 rounded"></div>
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
                class="text-center py-16 text-slate-400"
            >
                No branches available.
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
const loading = ref(false);

const handleSelect = (branch: BranchRetrieve) => {
    navigateTo({
        path: `/booking/provider/${branch.uuid}`,
    });
};
onMounted(async () => {
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
