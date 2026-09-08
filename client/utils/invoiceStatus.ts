export function statusClasses(status: string | null | undefined) {
    switch (status?.toLowerCase()) {
        case "paid":
            return "bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300";

        case "partial":
            return "bg-accent-50 text-accent-700 dark:bg-accent-500/15 dark:text-accent-300";

        case "pending":
            return "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-400";

        case "admitted":
            return "bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300";

        case "discharged":
            return "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-400";

        case "overdue":
            return "bg-danger/10 text-danger";

        case "cancelled":
            return "bg-danger/10 text-danger";

        case "void":
            return "bg-danger/10 text-danger";

        default:
            return "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-400";
    }
}
