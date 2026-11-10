import { computed, type Ref } from "vue";
import type { BranchActivation } from "~/types/branch";

export const formatActivationDate = (date?: string | null) =>
    date
        ? new Date(date).toLocaleDateString("en-US", {
              month: "short",
              day: "numeric",
              year: "numeric",
          })
        : "";

export function useBranchActivation(activation: Ref<BranchActivation | null>) {
    const deactivated = computed(() => Boolean(activation.value?.active_until));

    const reactivationAmount = computed(
        () => activation.value?.reactivation_amount ?? 0,
    );

    const action = computed<"deactivate" | "reactivate" | null>(() => {
        const link = activation.value;
        if (!link || link.status !== "approved") return null;
        if (link.active_until) return "reactivate";
        return link.is_main ? null : "deactivate";
    });

    const note = computed(() => {
        const link = activation.value;
        if (!link?.active_until) return null;

        if (link.slot_type === "included") {
            return "View-only · included slot still reserved.";
        }

        return link.activity_state === "ending"
            ? `View-only · free to reactivate until ${formatActivationDate(link.active_until)}.`
            : "View-only · not billed at renewal.";
    });

    return { deactivated, reactivationAmount, action, note };
}
