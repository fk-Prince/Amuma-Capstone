export const SANDBOX_BLOCKED_PATHS = [
    "/product",
    "/booking",
    "/subscription/payment-complete",
];

export const isSandboxBlockedPath = (path: string) =>
    SANDBOX_BLOCKED_PATHS.some(
        (blocked) => path === blocked || path.startsWith(`${blocked}/`),
    );
