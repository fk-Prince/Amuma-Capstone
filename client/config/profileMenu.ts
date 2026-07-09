import { useBranchStore } from "~/stores/branch";


export const handleMenuClick = async (item: any) => {
    const branchStore = useBranchStore();
    if (item.action === "dashboard") {
        if (!branchStore.branches.length) {
            await branchStore.fetchBranches();
        }

        let uuid =
            branchStore.activeBranch?.uuid ??
            branchStore.branches[0]?.uuid;


        if (!uuid) {
            const branch = branchStore.branches.find(
                (branch) => branch?.uuid
            );
            uuid = branch?.uuid;
        };

        await navigateTo(`/app/branches/${uuid}/dashboard`);
    } else {
        await navigateTo(item.to);
    }
};

export const profileMenuDropDownList = [
    { icon: "user", label: "My profile", to: "/" },
    {
        icon: "user",
        label: "Dashboard",
        action: "dashboard",
        roles: [
            "owner",
            "branch_owner",
            "administrator",
            "caregiver",
            "nurses",
            "accounting",
        ],
    },
    { icon: "Family Portal", label: "Family Portal", to: "/" },
    { icon: "Subscription Management", label: "Subscription Management", to: "/" },
    { icon: "user", label: "Settings", to: "/" },
];