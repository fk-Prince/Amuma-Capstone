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
        label: 'Accounting',
        class: 'bg-yellow-50 text-yellow-600 border-yellow-200',
    },
    admission: {
        label: 'Admission',
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