import { ref, watch, type Ref } from "vue";
import { branchService } from "~/api/branch/BranchService";

export interface DutyBlock {
    type: "shift" | "adl" | "medical";
    setting?: "facility" | "homecare";
    title: string;
    patient_name: string | null;
    location: string | null;
    note?: string | null;
    is_stay_in?: boolean;
    activities?: string[] | null;
    start_at: string;
    end_at: string;
    total_minutes: number;
}

export interface DutyData {
    on_duty: boolean;
    current: DutyBlock | null;
    next: DutyBlock | null;
    blocks: DutyBlock[];
    server_now: string;
}

export interface OverviewActivity {
    type: string;
    title: string;
    subtitle: string;
    status: string;
    date: string;
}

export interface BranchOverviewData {
    patients: { admitted: number; waiting: number; new_this_month: number };
    staff: { total: number };
    occupancy: { occupied: number; available: number; maintenance: number };
    bookings: {
        pending_confirmation: number;
        approved: number;
        expired: number;
        cancelled: number;
        rejected: number;
        expiring_soon: number;
        today: number;
    };
    contracts: {
        total_active_plans: number;
        total_patients: number;
        caregivers: number;
        scheduled_visits: number;
    };
    recent_activity: OverviewActivity[];
    my_duty: DutyData | null;
}

const num = (value: unknown) => Number(value) || 0;

export const emptyOverview = (): BranchOverviewData => ({
    patients: { admitted: 0, waiting: 0, new_this_month: 0 },
    staff: { total: 0 },
    occupancy: { occupied: 0, available: 0, maintenance: 0 },
    bookings: {
        pending_confirmation: 0,
        approved: 0,
        expired: 0,
        cancelled: 0,
        rejected: 0,
        expiring_soon: 0,
        today: 0,
    },
    contracts: {
        total_active_plans: 0,
        total_patients: 0,
        caregivers: 0,
        scheduled_visits: 0,
    },
    recent_activity: [],
    my_duty: null,
});

export const normalizeOverview = (raw: any): BranchOverviewData => {
    const data = raw?.data ?? raw ?? {};

    return {
        patients: {
            admitted: num(data.patients?.admitted),
            waiting: num(data.patients?.waiting),
            new_this_month: num(data.patients?.new_this_month),
        },
        staff: { total: num(data.staff?.total) },
        occupancy: {
            occupied: num(data.occupancy?.occupied?.value),
            available: num(data.occupancy?.available?.value),
            maintenance: num(data.occupancy?.maintenance?.value),
        },
        bookings: {
            pending_confirmation: num(data.bookings?.pending_confirmation),
            approved: num(data.bookings?.approved) + num(data.bookings?.accepted),
            expired: num(data.bookings?.expired),
            cancelled: num(data.bookings?.cancelled),
            rejected: num(data.bookings?.rejected),
            expiring_soon: num(data.bookings?.expiring_soon),
            today: num(data.bookings?.today),
        },
        contracts: {
            total_active_plans: num(data.contracts?.total_active_plans),
            total_patients: num(data.contracts?.total_patients),
            caregivers: num(data.contracts?.caregivers),
            scheduled_visits: num(data.contracts?.scheduled_visits),
        },
        recent_activity: Array.isArray(data.recent_activity)
            ? data.recent_activity
            : [],
        my_duty: data.my_duty ?? null,
    };
};

export const fetchBranchOverview = async (
    branchUuid: string,
): Promise<BranchOverviewData> => {
    const res: any = await branchService.dashboard({ branch_uuid: branchUuid });

    return normalizeOverview(res);
};

export const useBranchOverview = (branchUuid: Ref<string | null>) => {
    const overview = ref<BranchOverviewData>(emptyOverview());
    const loading = ref(true);
    const accessDenied = ref(false);
    const accessDeniedMessage = ref("");

    const load = async () => {
        if (!branchUuid.value) return;

        loading.value = true;
        accessDenied.value = false;

        try {
            overview.value = await fetchBranchOverview(branchUuid.value);
        } catch (err: any) {
            console.error("Failed to fetch branch overview:", err);

            if (err?.status === 403) {
                accessDenied.value = true;
                accessDeniedMessage.value =
                    err?.message ?? "You don't have access to this branch.";
            }
        } finally {
            loading.value = false;
        }
    };

    watch(branchUuid, (uuid) => uuid && load(), { immediate: true });

    return { overview, loading, accessDenied, accessDeniedMessage, reload: load };
};