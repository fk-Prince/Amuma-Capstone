export function scheduleStatusTheme(status?: string | null) {
    switch (status?.toLowerCase()) {
        case "completed":
            return {
                card: "border-l-4 border-l-sky-400 bg-sky-50/70 dark:bg-sky-500/10",
                badge: "bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300",
                accent: "text-sky-700 dark:text-sky-300",
            };

        case "ongoing":
            return {
                card: "border-l-4 border-l-emerald-400 bg-emerald-50/70 dark:bg-emerald-500/10",
                badge: "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300",
                accent: "text-emerald-700 dark:text-emerald-300",
            };

        case "confirmed":
            return {
                card: "border-l-4 border-l-blue-400 bg-blue-50/70 dark:bg-blue-500/10",
                badge: "bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300",
                accent: "text-blue-700 dark:text-blue-300",
            };

        case "missed":
            return {
                card: "border-l-4 border-l-rose-400 bg-rose-50/70 dark:bg-rose-500/10",
                badge: "bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300",
                accent: "text-rose-600 dark:text-rose-300",
            };

        case "cancelled":
            return {
                card: "border-l-4 border-l-slate-300 bg-slate-50/70 opacity-70 dark:bg-white/5",
                badge: "bg-slate-200 text-slate-500 dark:bg-white/15 dark:text-gray-400",
                accent: "text-slate-500 dark:text-gray-400",
            };

        case "pending":
        default:
            return {
                card: "border-l-4 border-l-violet-400 bg-violet-50/70 dark:bg-violet-500/10",
                badge: "bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300",
                accent: "text-violet-700 dark:text-violet-300",
            };
    }
}

export function scheduleStatusLabel(status?: string | null) {
    switch (status?.toLowerCase()) {
        case "completed":
            return "Complete";
        case "ongoing":
            return "Ongoing";
        case "confirmed":
            return "Confirmed";
        case "missed":
            return "Missed";
        case "cancelled":
            return "Cancelled";
        case "pending":
        default:
            return "Pending";
    }
}