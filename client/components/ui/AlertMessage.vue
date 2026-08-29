<script setup>
import { CheckCircle2, XCircle, Info } from "lucide-vue-next";

const props = defineProps({
    type: {
        type: String,
        default: "info",
        validator: (value) => ["success", "error", "info"].includes(value),
    },
    message: {
        type: String,
        required: true,
    },
});

const styles = {
    success: {
        wrapper: "bg-green-50 border-green-200 dark:bg-green-500/10 dark:border-green-500/20",
        icon: "text-green-600 dark:text-green-400",
        text: "text-green-800 dark:text-green-300",
    },
    error: {
        wrapper: "bg-red-50 border-red-200 dark:bg-red-500/10 dark:border-red-500/20",
        icon: "text-red-600 dark:text-red-400",
        text: "text-red-800 dark:text-red-300",
    },
    info: {
        wrapper: "bg-blue-50 border-blue-200 dark:bg-blue-500/10 dark:border-blue-500/20",
        icon: "text-blue-600 dark:text-blue-400",
        text: "text-blue-800 dark:text-blue-300",
    },
};

const icons = {
    success: CheckCircle2,
    error: XCircle,
    info: Info,
};
</script>

<template>
    <div
        class="flex items-start gap-2.5 rounded-xl border px-4 py-3"
        :class="styles[type].wrapper"
        role="alert"
    >
        <component
            :is="icons[type]"
            class="h-5 w-5 shrink-0 mt-0.5"
            :class="styles[type].icon"
        />
        <p class="text-sm leading-relaxed" :class="styles[type].text">
            {{ message }}
        </p>
    </div>
</template>
