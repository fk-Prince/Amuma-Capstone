

export type SidebarPosition = "left" | "right";

const STORAGE_KEY = "sidebar_position";

export const useSidebarPosition = () =>
    useState<SidebarPosition>("sidebar_position", () => "left");

export const setSidebarPosition = (position: SidebarPosition) => {
    const state = useSidebarPosition();
    state.value = position;

    if (process.client) {
        localStorage.setItem(STORAGE_KEY, position);
    }
};

export const initSidebarPosition = () => {
    if (!process.client) return;

    const stored = localStorage.getItem(STORAGE_KEY);
    const position: SidebarPosition = stored === "right" ? "right" : "left";

    const state = useSidebarPosition();
    state.value = position;
};