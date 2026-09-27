import { userService } from "~/api/user/UserService";
import type { Branch, UserAgency } from "~/types/branch";

export const useBranchStore = defineStore("branch", () => {
    const router = useRouter();

    const agencies = ref<UserAgency[]>([]);
    const branches = ref<Branch[]>([]);
    const loading = ref(false);
    const loaded = ref(false);
    const showModal = ref(false);
    const lastSelectedBranch = ref<Branch | null>(null);
    const defaultBranchUuid = ref<string | null>(null);

    const routeUuid = computed(() => {
        const v = router.currentRoute.value.params.uuid;
        const uuid = Array.isArray(v) ? v[0] : v;

        if (uuid && uuid !== "[uuid]") return uuid;
        const q = router.currentRoute.value.query.branch;
        const queryUuid = Array.isArray(q) ? q[0] : q;

        return queryUuid || null;
    });

    const activeBranch = computed<Branch | null>(() => {
        const uuid = routeUuid.value;

        if (!uuid) return null;

        return branches.value.find((b) => b.uuid === uuid) ?? null;
    });

    // Only non-rejected branches count as "there's a real choice here" — a
    // rejected branch isn't a genuine alternative, so it shouldn't make the
    // picker pop up or get labelled "default". Unverified-but-pending
    // branches still count, matching what the picker itself shows.
    const eligibleBranches = computed(() =>
        branches.value.filter((b) => b.subscription_status !== "rejected"),
    );

    const hasMultipleBranches = computed(
        () => eligibleBranches.value.length > 1,
    );

    function pickDefaultBranch(list: Branch[]): Branch | undefined {
        const score = (b: Branch) => {
            if (b.status === "verified" && b.agency?.status !== "rejected") return 0;
            if (b.subscription_status === "rejected") return 2;
            return 1;
        };

        return [...list].sort((a, b) => score(a) - score(b))[0];
    }

    function setAgencies(list: UserAgency[]) {
        agencies.value = list;
        branches.value = list.flatMap(
            ({ branches: agencyBranches, ...agency }) =>
                agencyBranches.map((branch) => ({ ...branch, agency })),
        );
        loaded.value = true;
    }

    async function refreshBranch() {
        try {
            const res = await userService.userBranch();
            setAgencies(res.data?.agencies ?? []);
        } finally {
            loading.value = false;
        }
    }
    async function fetchBranches(targetUuid?: string) {
        loading.value = true;

        try {
            const res = await userService.userBranch();
            setAgencies(res.data?.agencies ?? []);

            const uuid = targetUuid ?? routeUuid.value;
            //    const first = branches.value[0];
            const first = pickDefaultBranch(branches.value);
            defaultBranchUuid.value = first?.uuid ?? null;

            // Counting only verified, non-rejected branches covers both
            // cases — 2+ branches under one agency, and 2+ agencies — since
            // an agency only ever contributes branches of its own to this
            // list either way.
            const hasMultipleOptions = eligibleBranches.value.length > 1;

            if (!uuid && first?.uuid) {
                lastSelectedBranch.value = first;

                await router.replace(`/app/branches/${first.uuid}/dashboard`);

                if (hasMultipleOptions) openModal();

                return;
            }

            const exists = branches.value.some((b) => b.uuid === uuid);

            if (!exists && first?.uuid) {
                lastSelectedBranch.value = first;

                await router.replace(`/app/branches/${first.uuid}/dashboard`);

                if (hasMultipleOptions) openModal();

                return;
            }

            if (activeBranch.value) {
                lastSelectedBranch.value = activeBranch.value;
            }
        } finally {
            loading.value = false;
        }
    }
    function openModal() {
        if (!branches.value.length) return;
        showModal.value = true;
    }

    function closeModal() {
        showModal.value = false;
    }

    function selectBranch(branch: Branch) {
        if (!branch?.uuid) return;

        showModal.value = false;
        lastSelectedBranch.value = branch;

        const current = routeUuid.value;

        if (branch.uuid !== current) {
            router.push(`/app/branches/${branch.uuid}/dashboard`);
        }
    }

    return {
        agencies,
        branches,
        loading,
        loaded,
        showModal,
        routeUuid,
        activeBranch,
        eligibleBranches,
        hasMultipleBranches,
        fetchBranches,
        openModal,
        closeModal,
        selectBranch,
        refreshBranch,
        lastSelectedBranch,
        defaultBranchUuid,
    };
});
