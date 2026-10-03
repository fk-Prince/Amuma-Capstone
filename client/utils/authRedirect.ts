const KEY = "auth_redirect";
const TTL_MS = 30 * 60 * 1000;

export const CHECKOUT_PATH = "/product/subscription-details";

export function safeRedirect(value: unknown): string | null {
    if (typeof value !== "string") return null;

    const path = value.trim();

    if (!path.startsWith("/")) return null;
    if (path.startsWith("//") || path.includes("\\")) return null;
    if (path.startsWith("/auth/")) return null;

    return path;
}

export function isCheckoutPath(value: unknown): boolean {
    const path = safeRedirect(value);

    return !!path && path.startsWith(CHECKOUT_PATH);
}

export function isSubscribeFlowPath(value: unknown): boolean {
    const path = safeRedirect(value);

    return !!path && path.startsWith("/product");
}

export function saveAuthRedirect(value: unknown): void {
    if (!import.meta.client) return;

    const path = safeRedirect(value);
    if (!path) return;

    try {
        sessionStorage.setItem(KEY, JSON.stringify({ path, at: Date.now() }));
    } catch {
    }
}

export function clearAuthRedirect(): void {
    if (!import.meta.client) return;

    try {
        sessionStorage.removeItem(KEY);
    } catch {
    }
}

export function peekAuthRedirect(): string | null {
    if (!import.meta.client) return null;

    try {
        const raw = sessionStorage.getItem(KEY);
        if (!raw) return null;

        let path: unknown = raw;
        let at = Date.now();

        try {
            const parsed = JSON.parse(raw);
            path = parsed?.path;
            at = Number(parsed?.at) || 0;
        } catch {
            // older builds stored the bare path
        }

        if (Date.now() - at > TTL_MS) {
            clearAuthRedirect();
            return null;
        }

        return safeRedirect(path);
    } catch {
        return null;
    }
}

export function consumeAuthRedirect(queryValue?: unknown): string | null {
    const fromQuery = safeRedirect(
        Array.isArray(queryValue) ? queryValue[0] : queryValue,
    );
    const stored = peekAuthRedirect();

    clearAuthRedirect();

    return fromQuery ?? stored;
}

// Builds a route location that carries the redirect along.
export function withRedirect(path: string, redirect?: string | null) {
    const safe = safeRedirect(redirect);

    return safe ? { path, query: { redirect: safe } } : { path };
}