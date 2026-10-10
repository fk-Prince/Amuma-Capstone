export function scrollToId(id: string) {
    if (!import.meta.client) return;

    const target = document.getElementById(id);
    if (!target) return;

    const reduceMotion = window.matchMedia(
        "(prefers-reduced-motion: reduce)",
    ).matches;

    target.scrollIntoView({
        behavior: reduceMotion ? "auto" : "smooth",
        block: "start",
    });
}