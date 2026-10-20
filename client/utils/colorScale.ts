/**
 * Color helpers for the dashboard palette feature.
 *
 * Takes ONE base color and derives a Tailwind-style 50-900 scale from it, so a
 * user can pick two colors (primary + accent) and get a full theme.
 *
 * Light steps are the base mixed with white; dark steps keep hue/saturation and
 * scale lightness down. The ratios were fitted against the hand-tuned default
 * `primary` scale, so a base of #3182ED lands very close to the original one.
 */

export type Rgb = [number, number, number];
export type Hsl = [number, number, number]; // h: 0-360, s/l: 0-1

export const SCALE_STEPS = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900] as const;
export type ScaleStep = (typeof SCALE_STEPS)[number];

const LIGHT_MIX: Partial<Record<ScaleStep, number>> = {
    50: 0.9,
    100: 0.8,
    200: 0.6,
    300: 0.4,
    400: 0.2,
};

const DARK_LIGHTNESS: Partial<Record<ScaleStep, number>> = {
    600: 0.84,
    700: 0.66,
    800: 0.48,
    900: 0.34,
};

const clamp = (n: number, min: number, max: number) => Math.min(max, Math.max(min, n));

export function isHexColor(value: unknown): value is string {
    return typeof value === "string" && /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(value.trim());
}

export function hexToRgb(hex: string): Rgb | null {
    if (!isHexColor(hex)) return null;
    let h = hex.trim().slice(1);
    if (h.length === 3) h = h.split("").map((c) => c + c).join("");
    return [
        parseInt(h.slice(0, 2), 16),
        parseInt(h.slice(2, 4), 16),
        parseInt(h.slice(4, 6), 16),
    ];
}

export function rgbToHex([r, g, b]: Rgb): string {
    return (
        "#" +
        [r, g, b]
            .map((v) => clamp(Math.round(v), 0, 255).toString(16).padStart(2, "0"))
            .join("")
    );
}

export function rgbToHsl([r, g, b]: Rgb): Hsl {
    const rn = r / 255;
    const gn = g / 255;
    const bn = b / 255;
    const max = Math.max(rn, gn, bn);
    const min = Math.min(rn, gn, bn);
    const l = (max + min) / 2;
    const d = max - min;

    if (d === 0) return [0, 0, l];

    const s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    let h: number;
    if (max === rn) h = (gn - bn) / d + (gn < bn ? 6 : 0);
    else if (max === gn) h = (bn - rn) / d + 2;
    else h = (rn - gn) / d + 4;

    return [h * 60, s, l];
}

export function hslToRgb([h, s, l]: Hsl): Rgb {
    if (s === 0) {
        const v = l * 255;
        return [v, v, v];
    }
    const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
    const p = 2 * l - q;
    const hue = (t: number) => {
        let x = t;
        if (x < 0) x += 1;
        if (x > 1) x -= 1;
        if (x < 1 / 6) return p + (q - p) * 6 * x;
        if (x < 1 / 2) return q;
        if (x < 2 / 3) return p + (q - p) * (2 / 3 - x) * 6;
        return p;
    };
    const hn = h / 360;
    return [hue(hn + 1 / 3) * 255, hue(hn) * 255, hue(hn - 1 / 3) * 255];
}

const mixWithWhite = ([r, g, b]: Rgb, amount: number): Rgb => [
    r + (255 - r) * amount,
    g + (255 - g) * amount,
    b + (255 - b) * amount,
];

/**
 * Keeps a user-picked base color inside a lightness band so text/buttons built
 * from it stay readable (e.g. a pale yellow would make white-on-primary unreadable).
 */
export function normalizeBase(hex: string, minL: number, maxL: number): Rgb | null {
    const rgb = hexToRgb(hex);
    if (!rgb) return null;
    const [h, s, l] = rgbToHsl(rgb);
    return hslToRgb([h, s, clamp(l, minL, maxL)]);
}

export function generateScale(base: Rgb): Record<ScaleStep, Rgb> {
    const [h, s, l] = rgbToHsl(base);
    const out = {} as Record<ScaleStep, Rgb>;

    for (const step of SCALE_STEPS) {
        if (step === 500) out[step] = base;
        else if (LIGHT_MIX[step] !== undefined) out[step] = mixWithWhite(base, LIGHT_MIX[step]!);
        else out[step] = hslToRgb([h, s, l * DARK_LIGHTNESS[step]!]);
    }
    return out;
}

/** Subtle page wash for light mode, tinted with the primary hue. */
export function pageTint(primary: Rgb): Rgb {
    const [h] = rgbToHsl(primary);
    return hslToRgb([h, 0.6, 0.96]);
}

const channels = ([r, g, b]: Rgb) =>
    `${clamp(Math.round(r), 0, 255)} ${clamp(Math.round(g), 0, 255)} ${clamp(Math.round(b), 0, 255)}`;

export const PRIMARY_LIGHTNESS = [0.34, 0.6] as const;
export const ACCENT_LIGHTNESS = [0.22, 0.5] as const;

/**
 * The app's neutral colors are tinted, so they have to follow the palette too, or the
 * dark mode backgrounds (and the teal-gray text/borders in the dashboards) stay put.
 * These are the original colors; we keep each one's lightness and re-tint it to the
 * primary color's hue (dark navy backgrounds and the teal-gray "ink" text/borders alike).
 */
const SECONDARY_DEFAULTS: Record<string, string> = {
    "50": "#F4F6FA",
    "100": "#E5E9F2",
    "200": "#C7CFE0",
    "300": "#9AA7C2",
    "400": "#6B7A9B",
    "500": "#465575",
    "600": "#31405D",
    "700": "#253248",
    "800": "#1F2838",
    "900": "#1A2133",
    "950": "#0E1420",
};

const INK_DEFAULTS: Record<string, string> = {
    "": "#16302E",
    "-muted": "#6B8A87",
    "-subtle": "#9AB3AF",
    "-line": "#E4EFED",
    "-line-alt": "#DDECEC",
    "-soft": "#EDF4F3",
    "-soft-alt": "#F1F7F6",
    "-wash": "#F7FAF9",
    "-faint": "#FAFCFB",
};

function retint(hex: string, hue: number, satMul: number): Rgb {
    const [, s, l] = rgbToHsl(hexToRgb(hex)!);
    return hslToRgb([hue, clamp(s * satMul, 0, 1), l]);
}

/**
 * The AMUMA logo is a PNG: a blue house + left hand and a green right hand.
 * These are the measured average hue/saturation of those two parts, used to work
 * out how far to rotate each part so it matches the chosen primary / accent.
 */
const LOGO_PRIMARY = { hue: 205, sat: 0.71 };
const LOGO_ACCENT = { hue: 102.5, sat: 0.58 };

function logoVars(name: "primary" | "accent", color: Rgb): string[] {
    const ref = name === "primary" ? LOGO_PRIMARY : LOGO_ACCENT;
    const [h, s] = rgbToHsl(color);
    const rotation = Math.round((((h - ref.hue) % 360) + 360) % 360);
    const saturation = clamp(Math.round((s / ref.sat) * 100), 30, 130);

    return [`--logo-${name}-rot:${rotation};`, `--logo-${name}-sat:${saturation};`];
}

/**
 * Builds the CSS that overrides the palette variables.
 *
 * The output format is deliberately rigid (only `--name:R G B;` or `--name:N;`
 * declarations) - the pre-hydration script in nuxt.config.ts validates stored CSS
 * against the same shape before injecting it, so keep them in sync.
 */
export function buildPaletteCss(primaryHex: string, accentHex: string): string | null {
    const primary = normalizeBase(primaryHex, ...PRIMARY_LIGHTNESS);
    const accent = normalizeBase(accentHex, ...ACCENT_LIGHTNESS);
    if (!primary || !accent) return null;

    const decls: string[] = [];
    const primaryScale = generateScale(primary);
    const accentScale = generateScale(accent);

    for (const step of SCALE_STEPS) decls.push(`--primary-${step}:${channels(primaryScale[step])};`);
    for (const step of SCALE_STEPS) decls.push(`--accent-${step}:${channels(accentScale[step])};`);
    decls.push(`--page-bg:${channels(pageTint(primary))};`);
    decls.push(...logoVars("primary", primary), ...logoVars("accent", accent));

    // Tinted neutrals (dark backgrounds + teal-gray "ink") follow the primary hue.
    const [primaryHue, primarySat] = rgbToHsl(primary);
    const tintMul = clamp(primarySat / 0.84, 0.35, 1);

    for (const [step, hex] of Object.entries(SECONDARY_DEFAULTS)) {
        decls.push(`--secondary-${step}:${channels(retint(hex, primaryHue, tintMul))};`);
    }
    for (const [suffix, hex] of Object.entries(INK_DEFAULTS)) {
        decls.push(`--ink${suffix}:${channels(retint(hex, primaryHue, tintMul))};`);
    }

    return `html[data-palette-scope]{${decls.join("")}}`;
}

/** Shape check shared with the boot script: only `--var:R G B;` or `--var:N;` declarations. */
export const PALETTE_CSS_PATTERN =
    /^html\[data-palette-scope\]\{(--[a-z0-9-]+:(\d{1,3}|\d{1,3} \d{1,3} \d{1,3});)+\}$/;

/** Reads a CSS variable holding "R G B" channels and returns a #rrggbb string (for canvas/chart code). */
export function cssVarToHex(name: string, fallback: string): string {
    if (typeof window === "undefined") return fallback;
    const raw = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    const parts = raw.split(/\s+/).map(Number);
    if (parts.length !== 3 || parts.some((n) => Number.isNaN(n))) return fallback;
    return rgbToHex(parts as Rgb);
}