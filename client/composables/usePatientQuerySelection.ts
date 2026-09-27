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

    function selectedParams() {
        const requested = String(route.query.patient ?? "").trim();

        return requested
            ? { selected: 1, patient_uuid: requested }
            : { selected: 1 };
    }

    function indexOfPatient(
        list: { patient_id: number; uuid?: string | null }[],
        patientId: unknown,
    ) {
        const index = list.findIndex(
            (item) => item.patient_id === Number(patientId),
        );

        return index >= 0 ? index : resolveIndex(list);
    }

    return { resolveIndex, syncQuery, selectedParams, indexOfPatient };
}
