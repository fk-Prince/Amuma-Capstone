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
        },
    },
    plugins: [],
};
