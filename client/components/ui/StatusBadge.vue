<template>
    <span
        class="inline-flex w-[76px] items-center justify-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
        :style="getStatusStyle(status)"
    >
        <span
            class="h-1.5 w-1.5 shrink-0 rounded-full"
            :style="{ backgroundColor: getDotColor(status) }"
        />

        <span>{{ formatStatus(status) }}</span>
    </span>
</template>

<script setup lang="ts">
interface Props {
    status: string;
}

defineProps<Props>();

const getStatusStyle = (status: string): Record<string, string> => {
    switch (status.toLowerCase()) {
        case "pending":
            return {
                backgroundColor: "#fffbeb",
                color: "#a16207",
                boxShadow: "inset 0 0 0 1px #fde68a",
            };

        case "active":
            return {
                backgroundColor: "#ecfdf5",
                color: "#047857",
                boxShadow: "inset 0 0 0 1px #a7f3d0",
            };

        case "inactive":
            return {
                backgroundColor: "#f8fafc",
                color: "#475569",
                boxShadow: "inset 0 0 0 1px #e2e8f0",
            };

        case "expired":
            return {
                backgroundColor: "#fff7ed",
                color: "#c2410c",
                boxShadow: "inset 0 0 0 1px #fed7aa",
            };

        default:
            return {
                backgroundColor: "#f8fafc",
                color: "#475569",
                boxShadow: "inset 0 0 0 1px #e2e8f0",
            };
    }
};

const getDotColor = (status: string): string => {
    switch (status.toLowerCase()) {
        case "pending":
            return "#f59e0b";
        case "active":
            return "#10b981";
        case "inactive":
            return "#94a3b8";
        case "expired":
            return "#f97316";
        default:
            return "#94a3b8";
    }
};

const formatStatus = (status: string): string => {
    if (!status) return "Unknown";

    return status.charAt(0).toUpperCase() + status.slice(1);
};
</script>
