import { branchService } from "~/api/branch/BranchService";
import type { BranchRetrieve } from "~/types/branch";

export const useBranch = () => {
    const branch = useState<BranchRetrieve | null>(
        "branch",
        () => null
    );

    const loading = useState<boolean>(
        "branchLoading",
        () => false
    );

    const fetchBranch = async (id: string) => {
        loading.value = true;

        try {
            const res: any = await branchService.get(id);

            branch.value = res.data ?? res;

            return branch.value;
        } catch (err: any) {
            throw err;
        } finally {
            loading.value = false;
        }
    };


    const has = (type: "homecare" | "facility") => {
        if (!branch.value) return false;

        if (type === "homecare") {
            return branch.value.homecare;
        }

        if (type === "facility") {
            return branch.value.facility?.length > 0;
        }

        return false;
    };


    const availableSlots = computed(() => {
        const facilities = branch.value?.facility ?? [];
        if (!facilities.length) return 0;

        return Math.max(
            ...facilities.map(item => item.available_slot)
        );
    });


    const canUseHomecare = computed(() => {
        const data = branch.value;

        if (!data?.homecare) {
            return false;
        }

        const hasRate =
            Number(data.homecare.adl_hourly_rate ?? 0) > 0;

        const hasService =
            (data.services?.length ?? 0) > 0;

        return hasRate || hasService;
    });


    const canUseFacility = computed(() => {
        return (
            (branch.value?.facility?.length ?? 0) > 0 &&
            availableSlots.value > 0
        );
    });


    return {
        branch,
        loading,
        fetchBranch,

        has,
        availableSlots,
        canUseHomecare,
        canUseFacility,
    };
};