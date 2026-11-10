import { computed, toValue, type MaybeRefOrGetter } from "vue";
import { branchService } from "~/api/branch/BranchService";

export type BranchSetupKey =
    | "details"
    | "staff"
    | "services"
    | "rooms"
    | "contracts"
    | "subscription";

export type BranchSetupVisit = "details" | "operations" | "subscription";

export interface BranchSetupStep {
    key: BranchSetupKey;
    done: boolean;
    count?: number;
    visited?: { details: boolean; operations: boolean };
    homecare?: boolean;
    facility?: boolean;
}

export interface BranchSetupStatus {
    steps: BranchSetupStep[];
    complete: boolean;
    completed_at: string | null;
}

const latest: Record<string, number> = {};

export function useBranchSetup(branchUuid: MaybeRefOrGetter<string | undefined>) {
    const store = useState<Record<string, BranchSetupStatus>>("branch-setup", () => ({}));
    const sent = useState<Record<string, boolean>>("branch-setup-visits", () => ({}));

    const status = computed(() => {
        const uuid = toValue(branchUuid);
        return uuid ? (store.value[uuid] ?? null) : null;
    });

    const complete = computed(() => !!status.value?.complete);
    const loaded = computed(() => !!status.value);

    const save = (uuid: string, res: any) => {
        const data = (res?.data ?? res) as BranchSetupStatus | undefined;
        if (data?.steps) store.value = { ...store.value, [uuid]: data };
    };

    const track = async (uuid: string, request: () => Promise<any>) => {
        const ticket = (latest[uuid] = (latest[uuid] ?? 0) + 1);
        const res = await request();

        if (ticket === latest[uuid]) save(uuid, res);
    };

    async function refresh() {
        const uuid = toValue(branchUuid);
        if (!uuid || import.meta.server) return;

        try {
            await track(uuid, () => branchService.setup(uuid));
        } catch {}
    }

    async function markVisit(step: BranchSetupVisit) {
        const uuid = toValue(branchUuid);
        if (!uuid || import.meta.server) return;

        const key = `${uuid}:${step}`;
        if (sent.value[key]) return;
        sent.value = { ...sent.value, [key]: true };

        try {
            await track(uuid, () => branchService.setupVisit(uuid, step));
        } catch {
            sent.value = { ...sent.value, [key]: false };
        }
    }

    return { status, complete, loaded, refresh, markVisit };
}
