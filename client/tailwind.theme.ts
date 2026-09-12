export const colors = {
    primary: {
        DEFAULT: "#3182ED",
        50: "#EAF2FE",
        100: "#D5E5FD",
        200: "#ABCBFB",
        300: "#7FB1F8",
        400: "#5697F3",
        500: "#3182ED",
        600: "#1E68D1",
        700: "#1650A6",
        800: "#0F397B",
        900: "#0A2857",
    },
    // Was a single flat value. Expanded into a full scale so dark mode has
    // distinct surface levels to build on (page bg vs. card vs. border vs.
    // hover state) instead of one color reused everywhere. `900` keeps the
    // original `#0f1623` value so any existing `text-secondary` /
    // `bg-secondary` usages don't change.
    secondary: {
        DEFAULT: "#0f1623",
        50: "#F4F5F7",
        100: "#E7E9ED",
        200: "#C9CDD6",
        300: "#9CA3B2",
        400: "#6B7280",
        500: "#3B4354",
        600: "#232B3A",
        700: "#171E2B",
        800: "#111726",
        900: "#0f1623",
        950: "#0A0F19",
    },
    accent: {
        DEFAULT: "#0E7C7B",
        50: "#E7F5F5",
        100: "#CFEBEA",
        200: "#9FD7D5",
        300: "#6FC3C0",
        400: "#3FAFAB",
        500: "#0E7C7B",
        600: "#0C6564",
        700: "#094E4D",
        800: "#073837",
        900: "#042221",
    },
    light: "#ebf2ff",
    muted: {
        DEFAULT: "#6b7280",
        light: "#f3f4f6",
        dark: "#374151",
    },
    danger: "#f87171",
};

export const fontFamily = {
    sans: [
        "Outfit",
        "ui-sans-serif",
        "system-ui",
        "-apple-system",
        "Segoe UI",
        "Roboto",
        "Helvetica Neue",
        "Arial",
        "sans-serif",
    ],
    // `font-primary` is used in a few components (BaseInput, DatePicker,
    // the auth sections) as if it were a registered font family — it
    // wasn't, so it silently compiled to nothing. Same stack as `sans`,
    // just under the name those components already expect.
    primary: [
        "Outfit",
        "ui-sans-serif",
        "system-ui",
        "-apple-system",
        "Segoe UI",
        "Roboto",
        "Helvetica Neue",
        "Arial",
        "sans-serif",
    ],
};