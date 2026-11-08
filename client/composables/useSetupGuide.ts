import { computed, toValue, watch, type MaybeRefOrGetter } from "vue";
import { useRoute } from "vue-router";
import { useBranchStore } from "~/stores/branch";
import { useAuthUser } from "~/composables/useAuthUser";
import { userService } from "~/api/user/UserService";
import type { SetupOnboarding } from "~/types/auth";

export type SetupArea = "portal" | "dashboard";

export const setupAreaOf = (scope: string): SetupArea =>
    scope === "portal" ? "portal" : "dashboard";

const nowStamp = () => {
    const d = new Date();
    const pad = (n: number) => String(n).padStart(2, "0");
    return `${todayKey()} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
};

function useSetupState() {
    const user = useAuthUser();

    const setupOf = (area: SetupArea): SetupOnboarding =>
        user.value?.onboarding?.[area]?.setup ?? {};

    const complete = async (area: SetupArea, path: string[], showNow = true) => {
        if (!user.value) return;

        if (showNow) {
            const onboarding = user.value.onboarding ?? {};
            const current = onboarding[area] ?? {};
            const setup: any = { ...(current.setup ?? {}) };

            if (path.length === 1) setup[path[0]!] = nowStamp();
            else setup[path[0]!] = { ...setup[path[0]!], [path[1]!]: nowStamp() };

            user.value = { ...user.value, onboarding: { ...onboarding, [area]: { ...current, setup } } };
        }

        try {
            await userService.completeOnboarding(area, ["setup", ...path].join("->"));
        } catch { }
    };

    return { setupOf, complete };
}

export function useSetupVisits(area: MaybeRefOrGetter<SetupArea> = "dashboard") {
    const { setupOf, complete } = useSetupState();
    const freshVisit = useState<string>("setup_fresh_visit", () => "");

    const visits = computed(() => Object.keys(setupOf(toValue(area)).visits ?? {}));

    const markVisited = (path: string) => {
        if (import.meta.server) return;

        const clean = path.split("?")[0]!.replace(/\/+$/, "");
        if (visits.value.includes(clean)) {
            freshVisit.value = "";
            return;
        }

        freshVisit.value = clean;
        complete(toValue(area), ["visits", clean]);
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

const isMenuPage = (path: string) => {
    const depth = path.split("/").filter(Boolean).length;
    return path.startsWith("/app/branches/") ? depth <= 4 : depth <= 3;
};

export function useTrackSetupVisits(area: SetupArea) {
    const route = useRoute();
    const user = useAuthUser();
    const { markVisited } = useSetupVisits(area);

    watch(
        () => [route.path, user.value?.uuid],
        () => {
            if (!user.value || !isMenuPage(route.path)) return;
            markVisited(route.path);
        },
        { immediate: true },
    );
}

export const todayKey = () => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};

export function useFirstTimeUser() {
    const user = useAuthUser();
    const { setupOf, complete } = useSetupState();

    const isFinished = (scope: string) => !!setupOf(setupAreaOf(scope)).finished?.[scope];

    const showFor = (scope: string) => !!user.value && !isFinished(scope);

    const skip = (scope: string) => complete(setupAreaOf(scope), ["finished", scope]);

    const markFinished = (scope: string) => {
        if (!isFinished(scope)) complete(setupAreaOf(scope), ["finished", scope], false);
    };

    const tourSeenToday = (scope: string) =>
        setupOf(setupAreaOf(scope)).tour?.[scope]?.slice(0, 10) === todayKey();

    const markTourSeen = (scope: string) => complete(setupAreaOf(scope), ["tour", scope]);

    return { showFor, skip, markFinished, tourSeenToday, markTourSeen };
}
