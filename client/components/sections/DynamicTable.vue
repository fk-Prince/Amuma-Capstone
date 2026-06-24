<template>
    <div class="space-y-4">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-100 border-b">
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            class="px-6 py-3 text-left font-semibold text-sm"
                        >
                            {{ column.label }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(row, index) in paginatedData"
                        :key="index"
                        class="border-b hover:bg-gray-50"
                    >
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            class="px-6 py-4 text-sm"
                        >
                            <slot
                                :name="`cell-${column.key}`"
                                :row="row"
                                :value="row[column.key]"
                            >
                                {{ row[column.key] }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination
            :current-page="currentPage"
            :total-pages="tPages"
            :total-items="data.length"
            :items-per-page="itemsPerPage"
            @change-page="currentPage = $event"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import Pagination from "../ui/Pagination.vue";

export interface TableColumn {
    key: string;
    label: string;
}

interface Props {
    columns: TableColumn[];
    data: Record<string, any>[];
    itemsPerPage?: number;
}

const props = withDefaults(defineProps<Props>(), {
    itemsPerPage: 10,
});

const currentPage = ref(1);

const tPages = computed(() => {
    return Math.ceil(props.data.length / props.itemsPerPage);
});

const paginatedData = computed(() => {
    const start = (currentPage.value - 1) * props.itemsPerPage;
    const end = start + props.itemsPerPage;
    return props.data.slice(start, end);
});
</script>
