export interface CategoryStyle {
    bg: string;
    text: string;
    dot: string;
}

// Branches create their own categories, so the palette is keyed by name rather
// than by a fixed enum. The seeded categories get a fixed slot so the common
// ones always look the same; anything custom falls back to a stable hash so a
// given name keeps its colour everywhere it appears.
const NAMED_CATEGORIES: Record<string, number> = {
    "nursing care": 0,
    "wound care": 1,
    "medication services": 2,
    "rehabilitation services": 3,
    "post-surgical care": 4,
    "personal care": 5,
};

const PALETTE: CategoryStyle[] = [
    {
        bg: "bg-primary-50 dark:bg-primary-500/15",
        text: "text-primary-700 dark:text-primary-300",
        dot: "bg-primary-500",
    },
    {
        bg: "bg-accent-50 dark:bg-accent-500/15",
        text: "text-accent-700 dark:text-accent-300",
        dot: "bg-accent-500",
    },
    {
        bg: "bg-violet-50 dark:bg-violet-500/15",
        text: "text-violet-700 dark:text-violet-300",
        dot: "bg-violet-500",
    },
    {
        bg: "bg-emerald-50 dark:bg-emerald-500/15",
        text: "text-emerald-700 dark:text-emerald-300",
        dot: "bg-emerald-500",
    },
    {
        bg: "bg-amber-50 dark:bg-amber-500/15",
        text: "text-amber-700 dark:text-amber-300",
        dot: "bg-amber-500",
    },
    {
        bg: "bg-sky-50 dark:bg-sky-500/15",
        text: "text-sky-700 dark:text-sky-300",
        dot: "bg-sky-500",
    },
];

const UNCATEGORIZED: CategoryStyle = {
    bg: "bg-gray-100 dark:bg-white/10",
    text: "text-gray-600 dark:text-gray-300",
    dot: "bg-gray-400",
};

export function categoryLabel(name?: string | null) {
    return name?.trim() || "Uncategorized";
}

export function categoryStyle(name?: string | null): CategoryStyle {
    const key = name?.trim().toLowerCase();

    if (!key) return UNCATEGORIZED;

    const named = NAMED_CATEGORIES[key];
    if (named !== undefined) return PALETTE[named]!;

    let hash = 0;
    for (let i = 0; i < key.length; i++) {
        hash = (hash * 31 + key.charCodeAt(i)) >>> 0;
    }

    return PALETTE[hash % PALETTE.length]!;
}
