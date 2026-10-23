import { computed } from "vue";
import { useBranchStore } from "~/stores/branch";

const trimSlash = (url: unknown) => String(url || "").replace(/\/$/, "");

export const useSandbox = () => {
    const config = useRuntimeConfig();
    const branchStore = useBranchStore();

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

    // Only the agency owner's own login exists on both sides.
    const switchLink = computed(() => {
        const branch = branchStore.activeBranch;

        if (!switchUrl.value || !branch?.uuid || branch.role_name !== "agency_owner") {
            return "";
        }

        return `${switchUrl.value}/app/branches/${branch.uuid}/dashboard`;
    });

    return { isSandbox, sandboxUrl, liveUrl, switchUrl, switchLink };
};
