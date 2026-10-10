import { formatDate } from "~/utils/time";
export const formatRole = (role: string) => {
    return role
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase())
}

export const roleMeta: Record<string, { label: string; class: string }> = {
    agency_owner: {
        label: 'Agency Owner',
        class: 'border-primary/25 bg-primary/10 text-primary backdrop-blur-md dark:border-primary/30 dark:bg-primary/20 dark:text-primary-300',
    },
    branch_manager: {
        label: 'Branch Manager',
        class: 'bg-indigo-50 text-indigo-600 border-indigo-200',
    },
    cashier: {
        label: 'Cashier',
        class: 'bg-yellow-50 text-yellow-600 border-yellow-200',
    },
    admission: {
        label: 'Admission Staff',
        class: 'bg-blue-50 text-blue-600 border-blue-200',
    },
    nurse: {
        label: 'Nurse',
        class: 'bg-green-50 text-green-600 border-green-200',
    },
    caregiver: {
        label: 'Caregiver',
        class: 'bg-teal-50 text-teal-600 border-teal-200',
    },
}


export function fullName(
    firstName: string | null | undefined,
    middleName: string | null | undefined,
    lastName: string | null | undefined,
    suffix?: string | null,
) {
    return [firstName, middleName, lastName, suffix]
        .map((part) => part?.trim())
        .filter(Boolean)
        .join(" ") || "—";
}

export function calculateAge(date?: string | null, ba = true) {
    if (!date) {
        return "—";
    }

    const birthDate = new Date(date);
    const today = new Date();

    let years = today.getFullYear() - birthDate.getFullYear();
    let months = today.getMonth() - birthDate.getMonth();

    if (today.getDate() < birthDate.getDate()) {
        months--;
    }

    if (months < 0) {
        years--;
        months += 12;
    }

    let ageText = "";

    if (years === 0) {
        if (months === 0) {
            const days = Math.floor(
                (today.getTime() - birthDate.getTime()) /
                (1000 * 60 * 60 * 24),
            );

            ageText = `${days} day${days !== 1 ? "s" : ""} old`;
        } else {
            ageText = `${months} month${months !== 1 ? "s" : ""} old`;
        }
    } else {
        ageText = `${years} year${years !== 1 ? "s" : ""} old`;
    }

    if (ba) {
        return `${formatDate(date)} (${ageText})`;
    }
    return ageText;
}

export function initials(name?: string | null) {
    if (!name) return "?";
    const parts = name.trim().split(/\s+/);
    return ((parts[0]?.[0] ?? "") + (parts[1]?.[0] ?? "")).toUpperCase();
}