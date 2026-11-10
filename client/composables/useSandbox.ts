import { computed } from "vue";
import { useBranchStore } from "~/stores/branch";
import { authService } from "~/api/auth/AuthService";
import { clearStoredAuth } from "~/composables/useAuthUser";

const trimSlash = (url: unknown) => String(url || "").replace(/\/$/, "");

export const useSandbox = () => {
    const config = useRuntimeConfig();
    const branchStore = useBranchStore();

    const isSandbox = computed(() =>
        ["1", "true"].includes(String(config.public.sandbox).toLowerCase()),
    );

    const sandboxUrl = computed(() =>
        isSandbox.value ? "" : trimSlash(config.public.sandboxUrl),
    );

    const liveUrl = computed(() =>
        isSandbox.value ? trimSlash(config.public.liveUrl) : "",
    );

    const switchUrl = computed(() => sandboxUrl.value || liveUrl.value);

    const switchLink = computed(() => {
        const branch = branchStore.activeBranch;

        if (!switchUrl.value || !branch?.uuid || branch.role_name !== "agency_owner") {
            return "";
        }

        const dashboard = `/app/branches/${branch.uuid}/dashboard`;

        return sandboxUrl.value
            ? signOutUrl(sandboxUrl.value, `/auth/signin?redirect=${encodeURIComponent(dashboard)}`)
            : `${liveUrl.value}${dashboard}`;
    });

    return { isSandbox, sandboxUrl, liveUrl, switchUrl, switchLink };
};

export const signOutUrl = (base: string, next: string) =>
    `${base}/auth/signout?next=${encodeURIComponent(next)}`;

export const SIGNED_OUT_MESSAGE = "amuma:signed-out";

const BACKGROUND_SIGN_OUT_TIMEOUT = 4000;

const signOutInBackground = (base: string) =>
    new Promise<void>((resolve) => {
        let origin: string;

        try {
            origin = new URL(base).origin;
        } catch {
            resolve();
            return;
        }

        const frame = document.createElement("iframe");
        let finished = false;

        const finish = () => {
            if (finished) return;
            finished = true;
            window.removeEventListener("message", onMessage);
            clearTimeout(timer);
            frame.remove();
            resolve();
        };

        const onMessage = (event: MessageEvent) => {
            if (event.origin === origin && event.data?.type === SIGNED_OUT_MESSAGE) {
                finish();
            }
        };

        const timer = setTimeout(finish, BACKGROUND_SIGN_OUT_TIMEOUT);

        window.addEventListener("message", onMessage);

        frame.src = `${base}/auth/signout?silent=1`;
        frame.setAttribute("aria-hidden", "true");
        frame.tabIndex = -1;
        frame.style.cssText = "position:absolute;width:0;height:0;border:0;visibility:hidden";

        document.body.appendChild(frame);
    });

export const useSignOut = () => {
    const { switchUrl } = useSandbox();

    return async () => {
        await Promise.all([
            authService.logout().catch((err) => console.error(err)),
            switchUrl.value ? signOutInBackground(switchUrl.value) : null,
        ]);

        clearStoredAuth();

        window.location.href = "/auth/signin";
    };
};
