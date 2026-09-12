// Global, app-wide splash screen state. Any component can call show()/hide()
// — the actual overlay is mounted once in app.vue (see AppSplashScreen.vue)
// so it renders above every layout regardless of which page triggers it.
import { useState } from "#imports";

export const useSplashVisible = () => useState<boolean>("splash_visible", () => false);
export const useSplashTitle = () => useState<string>("splash_title", () => "Welcome back");
export const useSplashSubtitle = () =>
    useState<string>("splash_subtitle", () => "Setting things up for you…");

export function useSplashScreen() {
    const visible = useSplashVisible();
    const title = useSplashTitle();
    const subtitle = useSplashSubtitle();

    function show(options?: { title?: string; subtitle?: string }) {
        if (options?.title) title.value = options.title;
        if (options?.subtitle) subtitle.value = options.subtitle;
        visible.value = true;
    }

    function hide() {
        visible.value = false;
    }

    return { visible, title, subtitle, show, hide };
}