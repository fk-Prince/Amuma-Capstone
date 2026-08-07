export function scheduleStatusTheme(status?: string | null) {
    switch (status?.toLowerCase()) {
        case "completed":
            return {
                card: "border-l-4 border-l-sky-400 bg-sky-50/70",
                badge: "bg-sky-100 text-sky-700",
                accent: "text-sky-700",
            };

        case "ongoing":
            return {
                card: "border-l-4 border-l-emerald-400 bg-emerald-50/70",
                badge: "bg-emerald-100 text-emerald-700",
                accent: "text-emerald-700",
            };

        case "confirmed":
            return {
                card: "border-l-4 border-l-blue-400 bg-blue-50/70",
                badge: "bg-blue-100 text-blue-700",
                accent: "text-blue-700",
            };

        case "missed":
            return {
                card: "border-l-4 border-l-rose-400 bg-rose-50/70",
                badge: "bg-rose-100 text-rose-600",
                accent: "text-rose-600",
            };

        case "cancelled":
            return {
                card: "border-l-4 border-l-slate-300 bg-slate-50/70 opacity-70",
                badge: "bg-slate-200 text-slate-500",
                accent: "text-slate-500",
            };

        case "pending":
        default:
            return {
                card: "border-l-4 border-l-violet-400 bg-violet-50/70",
                badge: "bg-violet-100 text-violet-700",
                accent: "text-violet-700",
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