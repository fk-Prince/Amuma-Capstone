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

            spacing: {
                4.5: "1.125rem",
            },

            keyframes: {
                floatA: {
                    "0%, 100%": { transform: "translateY(0)" },
                    "50%": { transform: "translateY(-10px)" },
                },
                floatB: {
                    "0%, 100%": { transform: "translateY(0)" },
                    "50%": { transform: "translateY(-14px)" },
                },
                fadeInUp: {
                    from: { opacity: "0", transform: "translateY(18px)" },
                    to: { opacity: "1", transform: "translateY(0)" },
                },
                popIn: {
                    from: { opacity: "0", transform: "translateY(8px) scale(0.96)" },
                    to: { opacity: "1", transform: "translateY(0) scale(1)" },
                },
                progressFill: {
                    from: { transform: "scaleX(0)" },
                    to: { transform: "scaleX(1)" },
                },
            },

            animation: {
                floatA: "floatA 4s ease-in-out infinite",
                floatB: "floatB 4.5s ease-in-out 0.4s infinite",
                fadeInUp: "fadeInUp 0.8s cubic-bezier(0.22, 1, 0.36, 1) both",
                popIn: "popIn 0.22s ease-out both",
            },
        },
    },
    plugins: [],
};