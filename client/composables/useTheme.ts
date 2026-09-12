// Dark mode, restored. Persists the user's choice in localStorage and
// toggles the `dark` class on <html>, which Tailwind now reads because
// `darkMode: "class"` is set in tailwind.config.js.

const STORAGE_KEY = "theme";

export const useIsDark = () => useState<boolean>("theme_is_dark", () => false);

function applyThemeClass(dark: boolean) {
    if (!process.client) return;
    document.documentElement.classList.toggle("dark", dark);
}

export const setTheme = (dark: boolean) => {
    const isDark = useIsDark();
    isDark.value = dark;
    applyThemeClass(dark);

    if (process.client) {
        localStorage.setItem(STORAGE_KEY, dark ? "dark" : "light");
    }
};

export const initTheme = () => {
    if (!process.client) return;

    const stored = localStorage.getItem(STORAGE_KEY);

    // Established preference: light mode is the default when there's no
    // stored choice yet — don't fall back to the OS setting here, so a
    // fresh visitor always lands on light.
    const dark = stored === "dark";

    const isDark = useIsDark();
    isDark.value = dark;
    applyThemeClass(dark);
};