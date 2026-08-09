<template>
    <div class="w-full mx-auto px-6">
        <div v-if="!isMounted || props.loading" class="flex flex-col gap-4">
            <div
                v-for="n in 2"
                :key="n"
                class="border rounded-2xl overflow-hidden animate-pulse"
            >
                <div class="h-32 bg-gray-200"></div>
                <div class="p-4 space-y-3">
                    <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                    <div class="flex justify-between mt-4">
                        <div class="h-6 w-16 bg-gray-200 rounded-full"></div>
                        <div class="h-3 w-20 bg-gray-200 rounded"></div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="flex flex-col gap-4">
            <CardBooking
                :variant="2"
                v-for="branch in props.branches"
                :key="branch.uuid"
                :branch="branch"
                @select="handleSelect"
            />
        </div>

        <div
            v-if="isMounted && !props.loading && props.branches.length === 0"
            class="text-center py-16 text-slate-400"
        >
            No branches found.
        </div>
    </div>
</template>

<script setup lang="ts">
import CardBooking from "./CardBooking.vue";
import type { BranchRetrieve } from "~/types/branch";
import { ref, onMounted } from "vue";

defineEmits(["select"]);

const props = defineProps<{
    branches: BranchRetrieve[];
    loading?: boolean;
}>();

const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
});

const handleSelect = (branch: BranchRetrieve) => {
    navigateTo({
        path: `/booking/provider/${branch.uuid}`,
    });
};
</script>
