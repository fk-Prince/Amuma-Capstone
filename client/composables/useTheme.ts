import {
    CUSTOM_PALETTE_ID,
    DEFAULT_PALETTE,
    DEFAULT_PALETTE_ID,
    PALETTE_PRESETS,
} from "~/config/palettes";
import {
    PALETTE_CSS_PATTERN,
    buildPaletteCss,
    isHexColor,
} from "~/utils/colorScale";

/* ------------------------------------------------------------------ */
/* Mode: light | dark | system ("Device")                              */
/* ------------------------------------------------------------------ */

export type ThemeMode = "light" | "dark" | "system";

const MODE_KEY = "theme";
const DEFAULT_MODE: ThemeMode = "dark";
const DARK_QUERY = "(prefers-color-scheme: dark)";

export const THEME_MODES: ThemeMode[] = ["light", "dark", "system"];

const isThemeMode = (v: unknown): v is ThemeMode =>
    v === "light" || v === "dark" || v === "system";

/** What the user picked. */
export const useThemeMode = () =>
    useState<ThemeMode>("theme_mode", () => DEFAULT_MODE);

/** What is actually showing right now (system resolved to light/dark). */
export const useIsDark = () => useState<boolean>("theme_is_dark", () => true);

const resolveDark = (mode: ThemeMode) =>
    mode === "system"
        ? window.matchMedia(DARK_QUERY).matches
        : mode === "dark";

const applyMode = (mode: ThemeMode) => {
    useThemeMode().value = mode;
    if (!import.meta.client) return;

    const dark = resolveDark(mode);
    useIsDark().value = dark;
    document.documentElement.classList.toggle("dark", dark);
};

export const setThemeMode = (mode: ThemeMode) => {
    applyMode(mode);
    if (!import.meta.client) return;
    try {
        localStorage.setItem(MODE_KEY, mode);
    } catch {}
};

/** Kept for existing callers. */
export const setTheme = (dark: boolean) => setThemeMode(dark ? "dark" : "light");

let mediaListenerAttached = false;

export const initTheme = () => {
    if (!import.meta.client) return;

    let stored: string | null = null;
    try {
        stored = localStorage.getItem(MODE_KEY);
    } catch {}

    applyMode(isThemeMode(stored) ? stored : DEFAULT_MODE);

    // Follow the OS live while "Device" is selected.
    if (!mediaListenerAttached) {
        mediaListenerAttached = true;
        window.matchMedia(DARK_QUERY).addEventListener("change", () => {
            if (useThemeMode().value === "system") applyMode("system");
        });
    }
};

/* ------------------------------------------------------------------ */
/* Palette: preset or custom primary + accent                          */
/* ------------------------------------------------------------------ */

export interface PaletteSelection {
    id: string;
    primary: string;
    accent: string;
}

const PALETTE_KEY = "palette";
const PALETTE_STYLE_ID = "amuma-palette";

export const usePaletteSelection = () =>
    useState<PaletteSelection>("palette_selection", () => ({
        id: DEFAULT_PALETTE.id,
        primary: DEFAULT_PALETTE.primary,
        accent: DEFAULT_PALETTE.accent,
    }));

/** Bumped on every palette change so canvas/chart code (which can't use CSS vars) can redraw. */
export const usePaletteVersion = () =>
    useState<number>("palette_version", () => 0);

const writePaletteStyle = (css: string | null) => {
    let el = document.getElementById(PALETTE_STYLE_ID);

    if (!css) {
        el?.remove();
        return;
    }
    if (!el) {
        el = document.createElement("style");
        el.id = PALETTE_STYLE_ID;
        document.head.appendChild(el);
    }
    el.textContent = css;
};

const applyPalette = (selection: PaletteSelection) => {
    usePaletteSelection().value = selection;
    if (!import.meta.client) return null;

    const isDefault = selection.id === DEFAULT_PALETTE_ID;
    const css = isDefault
        ? null
        : buildPaletteCss(selection.primary, selection.accent);

    writePaletteStyle(css);
    usePaletteVersion().value++;
    return css;
};

export const setPalette = (selection: PaletteSelection) => {
    const css = applyPalette(selection);
    if (!import.meta.client) return;

    try {
        if (!css) localStorage.removeItem(PALETTE_KEY);
        else localStorage.setItem(PALETTE_KEY, JSON.stringify({ ...selection, css }));
    } catch {}
};

export const setPalettePreset = (id: string) => {
    const preset = PALETTE_PRESETS.find((p) => p.id === id) ?? DEFAULT_PALETTE;
    setPalette({ id: preset.id, primary: preset.primary, accent: preset.accent });
};

export const setCustomPalette = (primary: string, accent: string) => {
    if (!isHexColor(primary) || !isHexColor(accent)) return;
    setPalette({ id: CUSTOM_PALETTE_ID, primary, accent });
};

export const resetPalette = () => setPalettePreset(DEFAULT_PALETTE_ID);

export const initPalette = () => {
    if (!import.meta.client) return;

    try {
        const raw = localStorage.getItem(PALETTE_KEY);
        if (!raw) return;

        const saved = JSON.parse(raw);
        if (
            !isHexColor(saved?.primary) ||
            !isHexColor(saved?.accent) ||
            typeof saved?.id !== "string"
        ) {
            return;
        }

        // The boot script already injected the stored CSS before first paint;
        // this just syncs reactive state (and repairs the tag if it's missing/invalid).
        const selection = { id: saved.id, primary: saved.primary, accent: saved.accent };
        usePaletteSelection().value = selection;

        const existing = document.getElementById(PALETTE_STYLE_ID)?.textContent;
        if (!existing || !PALETTE_CSS_PATTERN.test(existing)) {
            applyPalette(selection);
        }
    } catch {}
};