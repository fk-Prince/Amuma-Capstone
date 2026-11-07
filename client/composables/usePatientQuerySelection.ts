import { watch, type Ref } from "vue";
import { useRoute, useRouter } from "vue-router";

export function usePatientQuerySelection() {
    const route = useRoute();
    const router = useRouter();

    const remembered = useState<string | null>("portal_selected_patient", () => null);
    const isPortal = () => route.path.startsWith("/portal");

    function requestedUuid() {
        const fromQuery = String(route.query.patient ?? "").trim();

        if (fromQuery) return fromQuery;

        return isPortal() ? (remembered.value ?? "") : "";
    }

    function resolveIndex(list: { uuid?: string | null }[]) {
        const requested = requestedUuid();

        if (!requested) return 0;

        const index = list.findIndex((item) => item.uuid === requested);

        return index >= 0 ? index : 0;
    }

    function syncQuery(uuid?: string | null) {
        if (!uuid) return;

        if (isPortal()) remembered.value = uuid;

        if (route.query.patient === uuid) return;

        router.replace({ query: { ...route.query, patient: uuid } });
    }

    function selectedParams() {
        const requested = requestedUuid();

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

    function followQuery(
        list: Ref<{ uuid?: string | null }[]>,
        selectedIndex: Ref<number>,
    ) {
        watch(
            () => route.query.patient,
            (uuid) => {
                if (!uuid) return;

                const index = list.value.findIndex((item) => item.uuid === uuid);

                if (index >= 0 && index !== selectedIndex.value) {
                    selectedIndex.value = index;
                }
            },
        );
    }

    return { resolveIndex, syncQuery, selectedParams, indexOfPatient, followQuery };
}
