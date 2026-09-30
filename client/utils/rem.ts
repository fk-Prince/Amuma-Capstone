export function rem(px: number) {
    return `${px / 16}rem`;
}

export function remScale() {
    if (typeof window === "undefined") return 1;

    return parseFloat(getComputedStyle(document.documentElement).fontSize) / 16 || 1;
}
