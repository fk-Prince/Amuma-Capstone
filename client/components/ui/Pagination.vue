<template>
    <div class="flex justify-between items-center">
        <div class="text-sm text-gray-600">
            Showing {{ startItem }} to {{ endItem }} of {{ totalItems }} items
        </div>

        <div class="flex gap-1">
            <button
                v-for="page in totalPages"
                :key="page"
                @click="selectPage(page)"
                :class="[
                    'px-3 py-2 border rounded-lg font-medium',
                    currentPage === page
                        ? 'bg-blue-600 text-white border-blue-600'
                        : 'bg-white hover:bg-gray-50',
                ]"
            >
                {{ page }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

interface Props {
    currentPage: number;
    totalPages: number;
    totalItems: number;
    itemsPerPage: number;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    "change-page": [page: number];
}>();

const startItem = computed(() => {
    if (props.totalItems === 0) return 0;
    return (props.currentPage - 1) * props.itemsPerPage + 1;
});

const endItem = computed(() => {
    const end = props.currentPage * props.itemsPerPage;
    return end > props.totalItems ? props.totalItems : end;
});

const selectPage = (page: number) => {
    emit("change-page", page);
};
</script>
