import { computed, toValue, watch, type MaybeRefOrGetter } from "vue";
import { useRoute } from "vue-router";
import { useBranchStore } from "~/stores/branch";
import { useAuthUser } from "~/composables/useAuthUser";
import { userService } from "~/api/user/UserService";
import type { SetupOnboarding } from "~/types/auth";

export type SetupArea = "portal" | "dashboard";

export const setupAreaOf = (scope: string): SetupArea =>
    scope === "portal" ? "portal" : "dashboard";

type SetupChange = {
    visit?: string;
    scope?: string;
    finished?: boolean;
    tour?: string;
    skipped?: boolean;
};

function useSetupState() {
    const user = useAuthUser();

    const setupOf = (area: SetupArea): SetupOnboarding =>
        user.value?.onboarding?.[area]?.setup ?? {};

    const patch = (area: SetupArea, apply: (setup: SetupOnboarding) => SetupOnboarding) => {
        if (!user.value) return;

        const onboarding = user.value.onboarding ?? {};
        const current = onboarding[area] ?? {};

        user.value = {
            ...user.value,
            onboarding: {
                ...onboarding,
                [area]: { ...current, setup: apply({ ...(current.setup ?? {}) }) },
            },
        };
    };

    const send = (area: SetupArea, change: SetupChange) => {
        if (!user.value) return;
        userService.updateSetupGuide(area, change).catch(() => {});
    };

    return { setupOf, patch, send };
}

export function useSetupVisits(area: MaybeRefOrGetter<SetupArea> = "dashboard") {
    const { setupOf, patch, send } = useSetupState();
    const freshVisit = useState<string>("setup_fresh_visit", () => "");

    const visits = computed(() => setupOf(toValue(area)).visits ?? []);

    const markVisited = (path: string) => {
        if (import.meta.server) return;

        const clean = path.split("?")[0]!.replace(/\/+$/, "");
        if (visits.value.includes(clean)) {
            freshVisit.value = "";
            return;
        }

        freshVisit.value = clean;

        const target = toValue(area);
        patch(target, (setup) => ({ ...setup, visits: [...(setup.visits ?? []), clean].slice(-200) }));
        send(target, { visit: clean });
    };

    const hasVisited = (path: string) =>
        visits.value.some((visit) => visit === path || visit.startsWith(`${path}/`));

    return { visits, freshVisit, markVisited, hasVisited };
}

export function useSetupBranchUuid() {
    const route = useRoute();
    const branchStore = useBranchStore();

    return computed(() => {
        const pick = (value: unknown) =>
            (Array.isArray(value) ? value[0] : value) as string | undefined;

        return (
            pick(route.params.uuid) ||
            pick(route.query.branch) ||
            branchStore.activeBranch?.uuid ||
            undefined
        );
    });
}

export function useTrackSetupVisits(area: SetupArea) {
    const route = useRoute();
    const user = useAuthUser();
    const { markVisited } = useSetupVisits(area);
    const { isNewAccount, isSkipped } = useFirstTimeUser();

    watch(
        () => [route.path, user.value?.uuid],
        () => {
            if (!user.value || !isNewAccount.value || isSkipped(area)) return;
            markVisited(route.path);
        },
        { immediate: true },
    );
}

const NEW_ACCOUNT_DAYS = 14;

export const todayKey = () => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};

export function useFirstTimeUser() {
    const user = useAuthUser();
    const { setupOf, patch, send } = useSetupState();

    const isNewAccount = computed(() => {
        const created = (user.value as { created_at?: string } | null)?.created_at;
        if (!created) return false;

        const time = new Date(created).getTime();
        if (Number.isNaN(time)) return false;

        return Date.now() - time < NEW_ACCOUNT_DAYS * 86_400_000;
    });

    const isSkipped = (area: SetupArea) => !!setupOf(area).skipped;

    const isFinished = (scope: string) => !!setupOf(setupAreaOf(scope)).finished?.[scope];

    const showFor = (scope: string) =>
        isNewAccount.value && !isSkipped(setupAreaOf(scope)) && !isFinished(scope);

    const skip = (scope: string) => {
        const area = setupAreaOf(scope);
        patch(area, (setup) => ({ ...setup, skipped: new Date().toISOString() }));
        send(area, { skipped: true });
    };

    // Saved for next time only, so the "Setup complete" tip still shows now.
    const markFinished = (scope: string) => {
        if (!isFinished(scope)) send(setupAreaOf(scope), { scope, finished: true });
    };

    const tourSeenToday = (scope: string) =>
        setupOf(setupAreaOf(scope)).tour?.[scope] === todayKey();

    const markTourSeen = (scope: string) => {
        const area = setupAreaOf(scope);
        const today = todayKey();
        patch(area, (setup) => ({ ...setup, tour: { ...setup.tour, [scope]: today } }));
        send(area, { scope, tour: today });
    };

    return { isNewAccount, isSkipped, showFor, skip, markFinished, tourSeenToday, markTourSeen };
}
