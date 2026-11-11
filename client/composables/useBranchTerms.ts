import { ref, watch, type MaybeRefOrGetter, toValue } from "vue";
import { branchService } from "~/api/branch/BranchService";
import type { BranchTermsData } from "~/utils/paymentTerms";

const cache = new Map<string, Promise<BranchTermsData | null>>();

export function loadBranchTerms(uuid: string, fresh = false): Promise<BranchTermsData | null> {
    if (fresh || !cache.has(uuid)) {
        cache.set(
            uuid,
            branchService
                .terms(uuid)
                .then((res: any) => (res?.data ?? null) as BranchTermsData | null)
                .catch(() => {
                    cache.delete(uuid);
                    return null;
                }),
        );
    }

    return cache.get(uuid)!;
}

export function useBranchTerms(uuid: MaybeRefOrGetter<string | null | undefined>) {
    const terms = ref<BranchTermsData | null>(null);
    const loading = ref(false);

    watch(
        () => toValue(uuid),
        async (value) => {
            terms.value = null;
            if (!value || import.meta.server) return;

            loading.value = true;
            const data = await loadBranchTerms(value);
            if (toValue(uuid) === value) terms.value = data;
            loading.value = false;
        },
        { immediate: true },
    );

    return { terms, loading };
}
