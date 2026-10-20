import { computed, onMounted, ref, watch } from "vue";
import { useBranchStore } from "~/stores/branch";

// The splash only appears for the browser that saw the branch as "pending"
// first, so branches that were already approved never trigger it.
const pendingKey = (uuid: string) => `amuma_sub_pending_${uuid}`;
const welcomedKey = (uuid: string) => `amuma_sub_welcomed_${uuid}`;

export function useApprovalSplash() {
    const branchStore = useBranchStore();
    const show = ref(false);

    const branch = computed(() => branchStore.activeBranch);

    const agencyName = computed(() => branch.value?.agency?.name ?? "");
    const branchName = computed(() => branch.value?.name ?? "");

    const validUntil = computed(() => {
        const raw = branch.value?.subscription_end_date;

        if (!raw) return "";

        const date = new Date(raw);

        return Number.isNaN(date.getTime())
            ? ""
            : date.toLocaleDateString("en-PH", { dateStyle: "medium" });
    });

    function check() {
        const b = branch.value;

        if (!import.meta.client || !b?.uuid) return;

        try {
            if (b.subscription_status === "pending") {
                localStorage.setItem(pendingKey(b.uuid), "1");
                return;
            }

            if (b.subscription_status !== "approved") return;
            if (localStorage.getItem(welcomedKey(b.uuid))) return;
            if (!localStorage.getItem(pendingKey(b.uuid))) return;

            show.value = true;
        } catch {
            // Storage blocked (private mode), so skip the splash.
        }
    }

    function dismiss() {
        const uuid = branch.value?.uuid;

        show.value = false;

        if (!uuid) return;

        try {
            localStorage.setItem(welcomedKey(uuid), "1");
            localStorage.removeItem(pendingKey(uuid));
        } catch {
            // ignore
        }
    }

    onMounted(check);

    watch(
        () => [branch.value?.uuid, branch.value?.subscription_status],
        check,
    );

    return { show, agencyName, branchName, validUntil, dismiss };
}