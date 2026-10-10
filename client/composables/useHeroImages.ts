export type HeroRole = "agency" | "family";
export type HeroMode = "light" | "dark";

const heroModules = import.meta.glob(
    [
        "../assets/images/hero/*.{png,jpg,jpeg,webp}",
        "../assets/images/{agency,family}-*.{png,jpg,jpeg,webp}",
        "../assets/images/{dashboard,lightmode-hero}.{png,jpg,jpeg,webp}",
    ],
    { eager: true, import: "default" },
) as Record<string, string>;

function findHero(name: string): string | null {
    const key = Object.keys(heroModules).find((path) => {
        const file = path.split("/").pop() ?? "";
        return file.replace(/\.[^.]+$/, "") === name;
    });

    return key ? (heroModules[key] ?? null) : null;
}

function pick(...names: string[]): string {
    for (const name of names) {
        const found = findHero(name);
        if (found) return found;
    }

    return Object.values(heroModules)[0] ?? "";
}

const sources: Record<HeroRole, Record<HeroMode, string>> = {
    agency: {
        dark: pick("agency-dark", "dashboard", "agency-light"),
        light: pick("agency-light", "agency-dark", "dashboard"),
    },
    family: {
        light: pick("family-light", "lightmode-hero", "family-dark"),
        dark: pick("family-dark", "family-light", "lightmode-hero"),
    },
};

export function heroImage(role: HeroRole, mode: HeroMode): string {
    return sources[role][mode];
}