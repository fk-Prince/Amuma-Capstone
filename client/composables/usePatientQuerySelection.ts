import { useRoute, useRouter } from "vue-router";

export function usePatientQuerySelection() {
    const route = useRoute();
    const router = useRouter();

    function resolveIndex(list: { uuid?: string | null }[]) {
        const requested = String(route.query.patient ?? "").trim();

        if (!requested) return 0;

        const index = list.findIndex((item) => item.uuid === requested);

        return index >= 0 ? index : 0;
    }

    function syncQuery(uuid?: string | null) {
        if (!uuid || route.query.patient === uuid) return;

        router.replace({ query: { ...route.query, patient: uuid } });
    }

    return { resolveIndex, syncQuery };
}
