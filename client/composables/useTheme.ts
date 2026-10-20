export type ThemeMode = "light" | "dark";

const MODE_KEY = "theme";
const DEFAULT_MODE: ThemeMode = "dark";

export const THEME_MODES: ThemeMode[] = ["light", "dark"];

const isThemeMode = (v: unknown): v is ThemeMode =>
    v === "light" || v === "dark";

export const useThemeMode = () =>
    useState<ThemeMode>("theme_mode", () => DEFAULT_MODE);

export const useIsDark = () => useState<boolean>("theme_is_dark", () => true);

const applyMode = (mode: ThemeMode) => {
    useThemeMode().value = mode;
    useIsDark().value = mode === "dark";
    if (!import.meta.client) return;

    document.documentElement.classList.toggle("dark", mode === "dark");
};

export const setThemeMode = (mode: ThemeMode) => {
    applyMode(mode);
    if (!import.meta.client) return;
    try {
        localStorage.setItem(MODE_KEY, mode);
    } catch {}
};

export const setTheme = (dark: boolean) => setThemeMode(dark ? "dark" : "light");

export const initTheme = () => {
    if (!import.meta.client) return;

    let stored: string | null = null;
    try {
        stored = localStorage.getItem(MODE_KEY);
    } catch {}

    applyMode(isThemeMode(stored) ? stored : DEFAULT_MODE);
};
