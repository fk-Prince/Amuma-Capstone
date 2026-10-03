import { Building2, Home, Layers } from "lucide-vue-next";

const PLAN_ICONS: Record<string, any> = {
    A: Home,
    B: Building2,
    C: Layers,
};

export const planIcon = (planCode?: string | null) =>
    PLAN_ICONS[planCode ?? ""] ?? Home;
