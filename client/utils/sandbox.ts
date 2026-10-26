export const SANDBOX_BLOCKED_PATHS = [
    "/product",
    "/booking",
    "/subscription/payment-complete",
];

export const xsrfCookieName = (config: { public: Record<string, unknown> }) =>
    String(
        config.public.xsrfCookie ||
            (["1", "true"].includes(String(config.public.sandbox).toLowerCase())
                ? "XSRF-TOKEN-SANDBOX"
                : "XSRF-TOKEN"),
    );

export const isSandboxBlockedPath = (path: string) =>
    SANDBOX_BLOCKED_PATHS.some(
        (blocked) => path === blocked || path.startsWith(`${blocked}/`),
    );
