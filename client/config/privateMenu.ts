import type { LucideIcon } from 'lucide-vue-next';
import { LayoutDashboard, BookOpen } from 'lucide-vue-next';

export interface MenuItem {
    label: string;
    to: string;
    route?: string[];
    icon?: LucideIcon;
}

export const privateMenu: MenuItem[] = [
    {
        label: "Dashboard",
        to: "/app/owner/dashboard",
        icon: LayoutDashboard,
    },
    {
        label: "Subscriptions",
        to: "/app/owner/subscription",
        icon: BookOpen,
    },
    {
        label: "Plans",
        to: "/app/owner/plans",
        icon: BookOpen,
    },
];