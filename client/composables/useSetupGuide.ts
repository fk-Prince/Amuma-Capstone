import { computed, watch } from "vue";
import { useRoute } from "vue-router";
import { useBranchStore } from "~/stores/branch";
import { useAuthUser } from "~/composables/useAuthUser";

/**
 * Remembers which pages a user has opened, so the setup guide can tick off
 * "go and look at X" steps. Kept in this browser only (best effort).
 */
const KEY = (userUuid: string) => `amuma_setup_visits:${userUuid}`;
const COLLAPSE_KEY = (userUuid: string, scope: string) =>
    `amuma_setup_collapsed:${userUuid}:${scope}`;

function read(key: string): string[] {
    try {
        const raw = localStorage.getItem(key);
        const list = raw ? JSON.parse(raw) : [];
        return Array.isArray(list) ? list : [];
    } catch {
        return [];
    }
}

export function useSetupVisits() {
    const user = useAuthUser();
    const visits = useState<string[]>("setup_visits", () => []);
    // The page just opened for the first time (empty when it had been seen before).
    const freshVisit = useState<string>("setup_fresh_visit", () => "");

    const load = () => {
        if (import.meta.server || !user.value?.uuid) return;
        visits.value = read(KEY(user.value.uuid));
    };

    const markVisited = (path: string) => {
        if (import.meta.server || !user.value?.uuid) return;

        // Branch pages keep their branch id, so each branch is set up on its own.
        const clean = path.split("?")[0]!.replace(/\/+$/, "");
        if (visits.value.includes(clean)) {
            freshVisit.value = "";
            return;
        }

        freshVisit.value = clean;
        visits.value = [...visits.value, clean].slice(-200);

        try {
            localStorage.setItem(KEY(user.value.uuid), JSON.stringify(visits.value));
        } catch {
            // Storage can be blocked; the guide still works for this visit.
        }
    };

    const hasVisited = (path: string) =>
        visits.value.some((visit) => visit === path || visit.startsWith(`${path}/`));

    return { visits, freshVisit, load, markVisited, hasVisited };
}

/**
 * The branch the setup guide is about: from the address on branch pages, and
 * from ?branch= or the open branch on shared pages like My Profile.
 */
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

/** Call once in a layout: records every page the user opens. */
export function useTrackSetupVisits() {
    const route = useRoute();
    const user = useAuthUser();
    const { load, markVisited } = useSetupVisits();

    watch(
        () => user.value?.uuid,
        () => load(),
        { immediate: true },
    );

    watch(
        () => [route.path, user.value?.uuid],
        () => markVisited(route.path),
        { immediate: true },
    );
}

/**
 * The setup guide is only for people who are new here: accounts made in the
 * last two weeks that haven't finished or skipped it. Everyone else never
 * sees the checklist, the walkthrough, the page tips or the menu hints.
 */
const NEW_ACCOUNT_DAYS = 14;
const SKIP_KEY = (userUuid: string) => `amuma_setup_skipped:${userUuid}`;
const FINISHED_KEY = (userUuid: string, scope: string) =>
    `amuma_setup_finished:${userUuid}:${scope}`;

function readFlag(key: string) {
    if (import.meta.server) return false;
    try {
        return localStorage.getItem(key) === "1";
    } catch {
        return false;
    }
}

function writeFlag(key: string) {
    try {
        localStorage.setItem(key, "1");
    } catch {
        // Then it may show again next time; harmless.
    }
}

export function useFirstTimeUser() {
    const user = useAuthUser();
    const flags = useState<Record<string, boolean>>("setup_flags", () => ({}));

    const flag = (key: string) => (key in flags.value ? flags.value[key]! : readFlag(key));

    const setFlag = (key: string) => {
        flags.value = { ...flags.value, [key]: true };
        writeFlag(key);
    };

    const isNewAccount = computed(() => {
        const created = (user.value as { created_at?: string } | null)?.created_at;
        if (!created) return false;

        const time = new Date(created).getTime();
        if (Number.isNaN(time)) return false;

        return Date.now() - time < NEW_ACCOUNT_DAYS * 86_400_000;
    });

    const skipped = computed(() => (user.value?.uuid ? flag(SKIP_KEY(user.value.uuid)) : false));

    const isFinished = (scope: string) =>
        user.value?.uuid ? flag(FINISHED_KEY(user.value.uuid, scope)) : false;

    /** Whether to show the guide at all for this place (a branch, the portal, ...). */
    const showFor = (scope: string) => isNewAccount.value && !skipped.value && !isFinished(scope);

    /** "Don't show again": hides it everywhere for this person. */
    const skip = () => {
        if (user.value?.uuid) setFlag(SKIP_KEY(user.value.uuid));
    };

    /**
     * Once everything is ticked off, it stays gone even if something changes
     * later. Saved for next time only, so the "Setup complete" tip still shows now.
     */
    const markFinished = (scope: string) => {
        if (user.value?.uuid) writeFlag(FINISHED_KEY(user.value.uuid, scope));
    };

    return { isNewAccount, showFor, skip, markFinished };
}

export function useSetupCollapsed(scope: () => string) {
    const user = useAuthUser();
    const collapsed = useState<Record<string, boolean>>("setup_collapsed", () => ({}));

    const key = () => (user.value?.uuid ? COLLAPSE_KEY(user.value.uuid, scope()) : "");

    const isCollapsed = () => {
        const k = key();
        if (!k) return false;
        if (k in collapsed.value) return collapsed.value[k]!;

        try {
            return localStorage.getItem(k) === "1";
        } catch {
            return false;
        }
    };

    const setCollapsed = (value: boolean) => {
        const k = key();
        if (!k) return;

        collapsed.value = { ...collapsed.value, [k]: value };

        try {
            localStorage.setItem(k, value ? "1" : "0");
        } catch {
            // Fine to forget this.
        }
    };

    return { isCollapsed, setCollapsed };
}
