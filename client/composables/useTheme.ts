const THEME_KEY = "theme";

export const useIsDark = () => useState<boolean>("theme_is_dark", () => true);

export const setTheme = (dark: boolean) => {
    const isDark = useIsDark();
    isDark.value = dark;

    if (process.client) {
        document.documentElement.classList.toggle("dark", dark);
        localStorage.setItem(THEME_KEY, dark ? "dark" : "light");
    }
};

export const initTheme = () => {
    if (!process.client) return;

    const stored = localStorage.getItem(THEME_KEY);
    setTheme(stored ? stored === "dark" : true);
};
