

export type BranchThemeCombo = "ocean" | "forest" | "royal" | "sunset";

export const BRANCH_THEME_COMBOS: { value: BranchThemeCombo; label: string; swatch: string }[] = [
    { value: "ocean", label: "Ocean Blue", swatch: "#3182ED" },
    { value: "forest", label: "Forest Green", swatch: "#1F9D57" },
    { value: "royal", label: "Royal Purple", swatch: "#7728D7" },
    { value: "sunset", label: "Sunset Amber", swatch: "#F2892E" },
];

export const applyBranchTheme = (combo?: string | null) => {
    if (!process.client) return;

    const isOverride = combo === "forest" || combo === "royal" || combo === "sunset";

    if (isOverride) {
        document.documentElement.setAttribute("data-branch-theme", combo as string);
    } else {
        document.documentElement.removeAttribute("data-branch-theme");
    }
};