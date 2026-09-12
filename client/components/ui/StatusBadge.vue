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
import { useIsDark } from "~/composables/useTheme";

interface Props {
    status: string;
}

defineProps<Props>();

const isDark = useIsDark();

const getStatusStyle = (status: string): Record<string, string> => {
    if (isDark.value) {
        switch (status.toLowerCase()) {
            case "pending":
                return {
                    backgroundColor: "rgba(245, 158, 11, 0.12)",
                    color: "#fbbf24",
                    boxShadow: "inset 0 0 0 1px rgba(245, 158, 11, 0.3)",
                };

            case "active":
                return {
                    backgroundColor: "rgba(16, 185, 129, 0.12)",
                    color: "#34d399",
                    boxShadow: "inset 0 0 0 1px rgba(16, 185, 129, 0.3)",
                };

            case "inactive":
                return {
                    backgroundColor: "rgba(255, 255, 255, 0.06)",
                    color: "#cbd5e1",
                    boxShadow: "inset 0 0 0 1px rgba(255, 255, 255, 0.12)",
                };

            case "expired":
                return {
                    backgroundColor: "rgba(249, 115, 22, 0.12)",
                    color: "#fb923c",
                    boxShadow: "inset 0 0 0 1px rgba(249, 115, 22, 0.3)",
                };

            default:
                return {
                    backgroundColor: "rgba(255, 255, 255, 0.06)",
                    color: "#cbd5e1",
                    boxShadow: "inset 0 0 0 1px rgba(255, 255, 255, 0.12)",
                };
        }
    }

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