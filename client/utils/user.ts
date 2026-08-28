import { formatDate } from "~/utils/time";
export const formatRole = (role: string) => {
    return role
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase())
}

export const roleMeta: Record<string, { label: string; class: string }> = {
    owner: {
        label: 'Owner',
        class: 'bg-purple-50 text-purple-600 border-purple-200',
    },
    branch_owner: {
        label: 'Branch Owner',
        class: 'bg-indigo-50 text-indigo-600 border-indigo-200',
    },
    administrator: {
        label: 'Administrator',
        class: 'bg-red-50 text-red-600 border-red-200',
    },
    accounting: {
        label: 'Accounting Staff',
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
    firstName: string,
    middleName: string | null | undefined,
    lastName: string
) {

    return `${firstName ?? ""} ${middleName ?? ""} ${lastName ?? ""}`
        .trim()
        || "—";
}

export function calculateAge(date?: string, ba = true) {
    if (!date) {
        return "—";
    }

    const birthDate = new Date(date);
    const today = new Date();

    let years = today.getFullYear() - birthDate.getFullYear();
    let months = today.getMonth() - birthDate.getMonth();

    if (
        months < 0 ||
        (months === 0 && today.getDate() < birthDate.getDate())
    ) {
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