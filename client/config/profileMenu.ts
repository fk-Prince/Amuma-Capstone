import { useBranchStore } from "~/stores/branch";
import { useRoute } from "vue-router";


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
        }

        if (!uuid) return;

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
        types: ["isEmployee"],
    },
    {
        icon: "Family Portal",
        label: "Family Portal",
        to: "/portal/bookings",
        types: ["isClient"],
    },
    {
        icon: "Subscription Management",
        label: "Subscription Management",
        to: "/app/owner/dashboard",
        types: ["isSystemOwner"],
    },
    { icon: "user", label: "Settings", to: "/" },
];