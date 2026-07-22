import { userService } from "~/api/user/UserService";
import type { Branch } from "~/types/branch";
import type { RouteLocationNormalizedLoaded } from "vue-router";

export const useBranchStore = defineStore("branch", () => {
    const router = useRouter();

    const branches = ref<Branch[]>([]);
    const loading = ref(false);
    const showModal = ref(false);
    const lastSelectedBranch = ref<Branch | null>(null);
    const currentRoute = ref<RouteLocationNormalizedLoaded | null>(null);


    const routeUuid = computed(() => {
        if (!currentRoute || !currentRoute.value) return;
        const v = currentRoute.value.params.uuid;
        const uuid = Array.isArray(v) ? v[0] : v;

        if (!uuid || uuid === "[uuid]") return null;

        return uuid;
    });

    const activeBranch = computed<Branch | null>(() => {
        const uuid = routeUuid.value;

        if (!uuid) return null;

        return branches.value.find((b) => b.uuid === uuid) ?? null;
    });

    const hasMultipleBranches = computed(() => branches.value.length > 1);


    async function refreshBranch() {
        try {
            const res = await userService.userBranch();
            branches.value = res.data?.branches ?? [];
        } finally {
            loading.value = false;
        }
    }

    async function fetchBranches(route: RouteLocationNormalizedLoaded) {
        loading.value = true;
        currentRoute.value = route;
        try {
            const res = await userService.userBranch();
            branches.value = res.data?.branches ?? [];
            const uuid = routeUuid.value;

            const first = branches.value[0];

            // if (!uuid && first?.uuid) {
            //     lastSelectedBranch.value = first;
            //     await router.replace(
            //         `/app/branches/${first.uuid}/dashboard`
            //     );
            //     return;
            // }
            if (!uuid && !route.path.startsWith("/app/branches/") && first?.uuid) {
                lastSelectedBranch.value = first;
                await router.replace(
                    `/app/branches/${first.uuid}/dashboard`
                );
                return;
            }
            const exists = branches.value.some(
                (b) => b.uuid === uuid
            );

            if (!exists && first?.uuid) {
                lastSelectedBranch.value = first;

                await router.replace(
                    `/app/branches/${first.uuid}/dashboard`
                );
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
        if (!branches.value.length || branches.value.length === 1) return;
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
        branches,
        loading,
        showModal,
        routeUuid,
        activeBranch,
        hasMultipleBranches,
        fetchBranches,
        openModal,
        closeModal,
        selectBranch,
        refreshBranch,
        lastSelectedBranch,
    };
});