export interface PalettePreset {
    id: string;
    label: string;
    primary: string;
    accent: string;
}

/** `ocean` is the original AMUMA look and is served by the defaults in main.css (no override CSS). */
export const DEFAULT_PALETTE_ID = "ocean";
export const CUSTOM_PALETTE_ID = "custom";

// Calm, muted pairs: softer saturation, readable white text on the primary color.
export const PALETTE_PRESETS: PalettePreset[] = [
    { id: "ocean", label: "Ocean", primary: "#3182ED", accent: "#0E7C7B" },
    { id: "sage", label: "Sage", primary: "#4F8070", accent: "#A67C52" },
    { id: "dusk", label: "Dusk", primary: "#4F76A8", accent: "#70619A" },
    { id: "clay", label: "Clay", primary: "#B5694B", accent: "#5E8A86" },
    { id: "mauve", label: "Mauve", primary: "#A5617A", accent: "#5F8A78" },
    { id: "sand", label: "Sand", primary: "#9A7B52", accent: "#5F7F8C" },
    { id: "stone", label: "Stone", primary: "#5B6B82", accent: "#5E8A84" },
];

export const DEFAULT_PALETTE = PALETTE_PRESETS[0];