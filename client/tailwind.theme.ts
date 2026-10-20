// Primary/accent are driven by CSS variables (see assets/css/main.css) so users can
// pick their own color combo. `<alpha-value>` keeps opacity modifiers like bg-primary-500/20 working.
const rgbVar = (name: string) => `rgb(var(--${name}) / <alpha-value>)`;
const STEPS = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900] as const;
const scale = (name: string) => ({
    DEFAULT: rgbVar(`${name}-500`),
    ...Object.fromEntries(STEPS.map((step) => [step, rgbVar(`${name}-${step}`)])),
});

export const colors = {
    primary: scale("primary"),
    secondary: {
        DEFAULT: "#1A2133",
        50: "#F4F6FA",
        100: "#E5E9F2",
        200: "#C7CFE0",
        300: "#9AA7C2",
        400: "#6B7A9B",
        500: "#465575",
        600: "#31405D",
        700: "#253248",
        800: "#1F2838",
        900: "#1A2133",
        950: "#0E1420",
    },
    surface: '#0E1420',
    accent: scale("accent"),
    page: rgbVar("page-bg"),
    light: '#ebf2ff',
    muted: {
        DEFAULT: '#6b7280',
        light: '#f3f4f6',
        dark: '#374151',
    },
    danger: '#f87171'
}

export const fontFamily = {
    sans: ['Outfit'],
}