import { computed } from "vue";
import { useBranchStore } from "~/stores/branch";

export function useVatInfo() {
    const branchStore = useBranchStore();

    return computed(() => {
        const rate = Number(branchStore.activeBranch?.settings?.vat_rate ?? 12);

        return `This price is VAT-inclusive (${rate}% VAT). VAT is already part of the amount, not added on top.`;
    });
}
