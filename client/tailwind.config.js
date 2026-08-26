/** @type {import('tailwindcss').Config} */

import { colors, fontFamily } from "./tailwind.theme";
export default {
    darkMode: "class",
    content: [
        "./components/**/*.{vue,js,ts}",
        "./layouts/**/*.vue",
        "./pages/**/*.vue",
        "./pages/**/*.{vue,js,ts}",
        "./app.vue",
        "./plugins/**/*.{js,ts}",
        "./app/*.vue",
        "./nuxt.config.{js,ts}",
    ],
    theme: {
        extend: {
            colors,
            fontFamily,

            // `h-4.5` / `w-4.5` are used in several components but are not in
            // Tailwind's default scale, so those utilities generated no CSS at
            // all and the elements collapsed to zero size.
            spacing: {
                4.5: "1.125rem",
            },
        },
    },
    plugins: [],
};
