import { computed } from "vue";

const trimSlash = (url: unknown) => String(url || "").replace(/\/$/, "");

export const useSandbox = () => {
    const config = useRuntimeConfig();

    const isSandbox = computed(() =>
        ["1", "true"].includes(String(config.public.sandbox).toLowerCase()),
    );

    const sandboxUrl = computed(() =>
        isSandbox.value ? "" : trimSlash(config.public.sandboxUrl),
    );

    const liveUrl = computed(() =>
        isSandbox.value ? trimSlash(config.public.liveUrl) : "",
    );

    const switchUrl = computed(() => sandboxUrl.value || liveUrl.value);

    return { isSandbox, sandboxUrl, liveUrl, switchUrl };
};
