<template>
    <section class="py-20 bg-white">
        <div class="max-w-6xl mx-auto px-6">
            <div class="flex items-end justify-between flex-wrap gap-4 mb-10">
                <div>
                    <span
                        class="text-xs font-semibold uppercase tracking-widest text-slate-400"
                    >
                        Featured
                    </span>
                    <h2
                        class="mt-2 text-3xl md:text-4xl font-bold text-slate-900 leading-tight"
                    >
                        Most trusted care facilities
                    </h2>
                    <p
                        class="mt-2 text-slate-500 text-sm leading-relaxed max-w-md"
                    >
                        Explore highly rated caregiving branches based on
                        reviews, availability, and service quality.
                    </p>
                </div>

                <button
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-slate-900 transition-colors"
                    @click="$emit('view-all')"
                >
                    View all
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3"
                        />
                    </svg>
                </button>
            </div>

            <div
                v-if="loading"
                class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3"
            >
                <div
                    v-for="n in 6"
                    :key="n"
                    class="border border-slate-200 rounded-2xl overflow-hidden animate-pulse"
                >
                    <div class="h-32 bg-slate-100" />
                    <div class="p-4 space-y-2">
                        <div class="h-4 bg-slate-100 rounded w-3/4" />
                        <div class="h-3 bg-slate-100 rounded w-1/2" />
                        <div class="h-3 bg-slate-100 rounded w-1/3 mt-3" />
                    </div>
                </div>
            </div>

            <div
                v-else-if="facilities.length"
                class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3"
            >
                <div
                    v-for="facility in facilities"
                    :key="facility.uuid"
                    class="relative bg-white rounded-2xl overflow-hidden cursor-pointer transition-all duration-200 hover:shadow-md"
                    :class="
                        facility.availability.is_open
                            ? 'border-2 border-blue-400 hover:border-blue-500'
                            : 'border border-slate-200 hover:border-slate-300'
                    "
                    @click="$emit('select', facility)"
                >
                    <span
                        v-if="facility.availability.is_open"
                        class="absolute top-2.5 left-2.5 z-10 text-xs font-semibold bg-blue-100 text-blue-700 px-2.5 py-1 rounded-lg"
                    >
                        Open now
                    </span>

                    <div
                        class="h-32 bg-slate-100 flex items-center justify-center overflow-hidden"
                    >
                        <img
                            v-if="facility.image"
                            :src="facility.image"
                            :alt="facility.name"
                            class="w-full h-full object-cover"
                        />
                        <svg
                            v-else
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-9 h-9 text-slate-300"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"
                            />
                        </svg>
                    </div>

                    <div class="p-4">
                        <h3
                            class="text-sm font-semibold text-slate-900 truncate"
                        >
                            {{ facility.name }}
                        </h3>

                        <p
                            class="mt-1 flex items-center gap-1 text-xs text-slate-400"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-3 h-3 shrink-0"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0z"
                                />
                            </svg>
                            {{ facility.availability.timezone }}
                            <template v-if="activePlan(facility)">
                                · {{ activePlan(facility)?.plan_name }}
                            </template>
                        </p>

                        <p
                            v-if="facility.description"
                            class="mt-1.5 text-xs text-slate-400 line-clamp-2 leading-relaxed"
                        >
                            {{ facility.description }}
                        </p>

                        <div class="mt-3 flex items-center justify-between">
                            <span
                                class="flex items-center gap-1 text-xs text-slate-500"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-3.5 h-3.5 text-amber-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.75"
                                    stroke="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5z"
                                    />
                                </svg>
                                <strong class="font-semibold text-slate-700">{{
                                    facility.averageRating ?? "—"
                                }}</strong>
                                <span class="text-slate-400"
                                    >({{ facility.reviewCount }})</span
                                >
                            </span>

                            <span
                                class="text-xs font-semibold px-2.5 py-1 rounded-full"
                                :class="
                                    facility.availability.is_open
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-red-100 text-red-600'
                                "
                            >
                                {{
                                    facility.availability.is_open
                                        ? "Open"
                                        : "Closed"
                                }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-16 text-slate-400 text-sm">
                No facilities found.
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { branchService } from "~/api/branch/BranchService";
import { type BranchRetrieve } from "~/types/branch";

defineEmits(["view-all", "select"]);

const facilities = ref<BranchRetrieve[]>([]);
const loading = ref(false);

function activePlan(facility: BranchRetrieve) {
    return (
        facility.subscriptions?.find((s) => s.status === "active") ??
        facility.subscriptions?.[0] ??
        null
    );
}

onMounted(async () => {
    loading.value = true;
    try {
        const res = await branchService.list({ per_page: 9 });
        facilities.value = res.data;
        console.log(facilities.value);
    } catch (err) {
        console.error(err);
    } finally {
        loading.value = false;
    }
});
</script>
